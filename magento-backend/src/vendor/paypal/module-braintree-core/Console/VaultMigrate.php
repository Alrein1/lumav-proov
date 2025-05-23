<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Console;

use Braintree\Exception\NotFound;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use PayPal\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection\ConnectionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * This class aims to migrate Magento 1 stored cards to Magento 2
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultMigrate extends Command
{
    public const HOST = 'host';
    private const DBNAME = 'dbname';
    private const USERNAME = 'username';
    private const PASSWORD = 'password';
    private const EAV_ATTRIBUTE_TABLE = 'eav_attribute';
    private const ATTRIBUTE_ID = 'attribute_id';
    private const ATTRIBUTE_CODE = 'attribute_code';
    private const CUSTOMER_ENTITY_TABLE = 'customer_entity_varchar';
    private const VALUE = 'value';
    private const CC_MAPPER = [
        'american-express' => 'AE',
        'discover' => 'DI',
        'jcb' => 'JCB',
        'mastercard' => 'MC',
        'master-card' => 'MC',
        'visa' => 'VI',
        'maestro' => 'MI',
        'diners-club' => 'DN',
    ];

    /**
     * @var array
     */
    private array $customers;
    /**
     * @var ConnectionFactory
     */
    private ConnectionFactory $connectionFactory;
    /**
     * @var BraintreeAdapter
     */
    private BraintreeAdapter $braintreeAdapter;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var PaymentTokenFactory
     */
    private PaymentTokenFactory $paymentToken;
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private PaymentTokenRepositoryInterface $paymentTokenRepository;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $json;
    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * VaultMigrate constructor.
     *
     * @param ConnectionFactory $connectionFactory
     * @param BraintreeAdapter $braintreeAdapter
     * @param CustomerRepositoryInterface $customerRepository
     * @param PaymentTokenFactory $paymentToken
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $json
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConnectionFactory $connectionFactory,
        BraintreeAdapter $braintreeAdapter,
        CustomerRepositoryInterface $customerRepository,
        PaymentTokenFactory $paymentToken,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor,
        SerializerInterface $json,
        StoreManagerInterface $storeManager
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->customerRepository = $customerRepository;
        $this->paymentToken = $paymentToken;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->json = $json;
        $this->storeManager = $storeManager;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('braintree:migrate');
        $this->setDescription('Migrate stored cards from a Magento 1 database');
        $this->setDefinition($this->getOptionsList());

        parent::configure();
    }

    /**
     * Interact with DB
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        if (!$input->getOption(self::HOST)) {
            $question = new Question('<question>Database host/IP address:</question>', null);
            $input->setOption(self::HOST, $questionHelper->ask($input, $output, $question));
        }

        if (!$input->getOption(self::DBNAME)) {
            $question = new Question('<question>Database name:</question>', null);
            $input->setOption(self::DBNAME, $questionHelper->ask($input, $output, $question));
        }

        if (!$input->getOption(self::USERNAME)) {
            $question = new Question('<question>Database username:</question>', null);
            $question->setHidden(true);
            $input->setOption(self::USERNAME, $questionHelper->ask($input, $output, $question));
        }

        if (!$input->getOption(self::PASSWORD)) {
            $question = new Question('<question>Database user password:</question>', null);
            $question->setHidden(true);
            $input->setOption(self::PASSWORD, $questionHelper->ask($input, $output, $question));
        }
    }

    /**
     * Execute to get tokens
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws NotFound
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getOption(self::HOST);
        $databaseName = $input->getOption(self::DBNAME);
        $username = $input->getOption(self::USERNAME);
        $password = $input->getOption(self::PASSWORD);

        // Create connection to Magento 1 database
        $db = $this->getDbConnection($host, $databaseName, $username, $password ?? '');

        // Get the `braintree_customer_id` attribute ID
        $eavAttributeId = $this->getEavAttributeId($db);

        if (!$eavAttributeId) {
            $output->writeln('<error>Could not find `braintree_customer_id` attribute.</error>');
            return;
        }

        // Find all instances of `braintree_customer_id` in the customer entity table
        $storedCards = $this->getStoredCards($db, $output, $eavAttributeId);

        if (!$storedCards) {
            $output->writeln('<error>Could not find any stored cards.</error>');
            return;
        }

        // For each record, look up the Braintree ID
        $braintreeCustomers = $this->findBraintreeCustomers($output, $storedCards);

        if (!$braintreeCustomers) {
            $output->writeln('<error>Could not find any matching customers in Magento 2.</error>');
            return;
        }

        $this->customers = $this->remapCustomerData($braintreeCustomers);

        if (!$this->customers) {
            $output->writeln('<error>Could not remap Customer data.</error>');
            return;
        }

        // For each customer, locate them in the M2 database and save their stored cards
        $this->migrateStoredCards($output, $this->customers);

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Get options list
     *
     * @return array
     */
    public function getOptionsList(): array
    {
        return [
            new InputOption(self::HOST, null, InputOption::VALUE_REQUIRED, 'Hostname/IP. Port is optional'),
            new InputOption(self::DBNAME, null, InputOption::VALUE_REQUIRED, 'Database name'),
            new InputOption(
                self::USERNAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Database username. Must have read access'
            ),
            new InputOption(self::PASSWORD, null, InputOption::VALUE_REQUIRED, 'Password')
        ];
    }

    /**
     * Get DB connection
     *
     * @param string $host
     * @param string $databaseName
     * @param string $username
     * @param string $password
     * @return AdapterInterface
     */
    private function getDbConnection(
        string $host,
        string $databaseName,
        string $username,
        string $password
    ): AdapterInterface {
        return $this->connectionFactory->create([
            'host' => $host,
            'dbname' => $databaseName,
            'username' => $username,
            'password' => $password
        ]);
    }

    /**
     * Get Eav attribute id
     *
     * @param AdapterInterface $db
     * @return string
     */
    private function getEavAttributeId(AdapterInterface $db): string
    {
        $select = $db->select()
            ->where('attribute_code = ?', 'braintree_customer_id')
            ->from(self::EAV_ATTRIBUTE_TABLE, self::ATTRIBUTE_ID);
        return $db->fetchOne($select);
    }

    /**
     * Get stored cards
     *
     * @param AdapterInterface $db
     * @param OutputInterface $output
     * @param string $eavAttributeId
     * @return array|false
     */
    private function getStoredCards(AdapterInterface $db, OutputInterface $output, string $eavAttributeId): false|array
    {
        $select = $db->select()
            ->join('customer_entity', 'customer_entity.entity_id = customer_entity_varchar.entity_id')
            ->where(self::ATTRIBUTE_ID . ' = ?', $eavAttributeId)
            ->from(self::CUSTOMER_ENTITY_TABLE, self::VALUE . ' as braintree_id');
        $result = $db->fetchAll($select);

        if ($result) {
            $output->writeln('<info>'. count($result) .' stored cards found</info>');
            return $result;
        }

        return false;
    }

    /**
     * Find braintree customers
     *
     * @param OutputInterface $output
     * @param array $storedCards
     * @return array
     * @throws NotFound
     */
    private function findBraintreeCustomers(OutputInterface $output, array $storedCards): array
    {
        $customers = [];
        foreach ($storedCards as $storedCard) {
            $output->writeln('<info>Search Braintree for Customer ID ' . $storedCard['braintree_id'] . '...</info>');
            $customers[] = $this->braintreeAdapter->getCustomerById($storedCard['braintree_id']);
        }

        return $customers;
    }

    /**
     * Remap customer data
     *
     * @param array $customers
     * @return array
     */
    public function remapCustomerData(array $customers): array
    {
        $remappedCustomerData = [];

        foreach ($customers as $customer) {
            $customerData = [
                'braintree_id' => $customer->id,
                'email' => $customer->email
            ];

            if ($customer->creditCards) {
                // grab each stored credit card
                foreach ($customer->creditCards as $creditCard) {
                    $customerData['storedCards'][] = [
                        'token' => $creditCard->token,
                        'expirationMonth' => $creditCard->expirationMonth,
                        'expirationYear' => $creditCard->expirationYear,
                        'last4' => $creditCard->last4,
                        'cardType' => self::CC_MAPPER[str_replace(' ', '-', strtolower($creditCard->cardType))]
                    ];
                }
            }

            // Add customer data to the main customer array
            $remappedCustomerData[] = $customerData;
        }

        return $remappedCustomerData;
    }

    /**
     * Migrate stored cards
     *
     * @param OutputInterface $output
     * @param array $customers
     */
    private function migrateStoredCards(OutputInterface $output, array $customers): void
    {
        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
            foreach ($customers as $customer) {
                try {
                    $m2Customer = $this->customerRepository->get($customer['email'], $website->getId());

                    $output->write(
                        "<info>Customer {$customer['braintree_id']} found in {$website->getName()}...</info>"
                    );

                    foreach ($customer['storedCards'] as $storedCard) {
                        // Create new vault payment token.
                        $vaultPaymentToken = $this->paymentToken->create(
                            PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD
                        );
                        $vaultPaymentToken->setCustomerId($m2Customer->getId());
                        $vaultPaymentToken->setPaymentMethodCode('braintree');
                        $vaultPaymentToken->setExpiresAt(
                            sprintf(
                                '%s-%s-01 00:00:00',
                                $storedCard['expirationYear'],
                                $storedCard['expirationMonth']
                            )
                        );
                        $vaultPaymentToken->setGatewayToken($storedCard['token']);
                        $vaultPaymentToken->setTokenDetails($this->json->serialize([
                            'type' => $storedCard['cardType'],
                            'maskedCC' => $storedCard['last4'],
                            'expirationDate' => $storedCard['expirationMonth'] . '/' . $storedCard['expirationYear']
                        ]));
                        $vaultPaymentToken->setPublicHash(
                            $this->encryptor->getHash(
                                $m2Customer->getId()
                                . $vaultPaymentToken->getPaymentMethodCode()
                                . $vaultPaymentToken->getType()
                                . $vaultPaymentToken->getTokenDetails()
                            )
                        );

                        if ($this->paymentTokenRepository->save($vaultPaymentToken)) {
                            $output->writeln('<info>Card stored successfully!</info>');
                        }
                    }
                } catch (NoSuchEntityException $e) {
                    $output->writeln(
                        "<error>Customer {$customer['braintree_id']} not found in {$website->getName()}.</error>"
                    );
                } catch (LocalizedException $e) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }
            }
        }

        $output->writeln('<info>Migration complete!</info>');
    }
}
