<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);
namespace PayPal\Braintree\Model;

use InvalidArgumentException;
use PayPal\Braintree\Gateway\Response\PaymentDetailsHandler;
use PayPal\Braintree\Model\Ui\ConfigProvider;
use Magento\Payment\Api\PaymentVerificationInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Processes AVS codes mapping from Braintree transaction to
 * electronic merchant systems standard.
 *
 * @see https://developers.braintreepayments.com/reference/response/transaction
 * @see http://www.emsecommerce.net/avs_cvv2_response_codes.htm
 */
class AvsEmsCodeMapper implements PaymentVerificationInterface
{
    /**
     * Default code for mismatching mapping.
     *
     * @var string
     */
    private static $unavailableCode = 'U';

    /**
     * List of mapping AVS codes
     *
     * @var array
     */
    private static $avsMap = [
        'MM' => 'Y',
        'NM' => 'A',
        'MN' => 'Z',
        'NN' => 'N',
        'UU' => 'U',
        'II' => 'U',
        'AA' => 'E'
    ];

    /**
     * Gets payment AVS verification code.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     * @throws InvalidArgumentException If specified order payment has different payment method code.
     */
    public function getCode(OrderPaymentInterface $orderPayment): string
    {
        if ($orderPayment->getMethod() !== ConfigProvider::CODE) {
            throw new InvalidArgumentException(
                'The "' . $orderPayment->getMethod() . '" does not supported by Braintree AVS mapper.'
            );
        }

        $additionalInfo = $orderPayment->getAdditionalInformation();
        if (empty($additionalInfo[PaymentDetailsHandler::AVS_POSTAL_RESPONSE_CODE]) ||
            empty($additionalInfo[PaymentDetailsHandler::AVS_STREET_ADDRESS_RESPONSE_CODE])
        ) {
            return self::$unavailableCode;
        }

        $streetCode = $additionalInfo[PaymentDetailsHandler::AVS_STREET_ADDRESS_RESPONSE_CODE];
        $zipCode = $additionalInfo[PaymentDetailsHandler::AVS_POSTAL_RESPONSE_CODE];
        $key = $zipCode . $streetCode;
        return self::$avsMap[$key] ?? self::$unavailableCode;
    }
}
