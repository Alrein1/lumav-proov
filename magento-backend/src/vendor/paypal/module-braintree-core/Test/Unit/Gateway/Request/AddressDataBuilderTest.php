<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);
namespace PayPal\Braintree\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use PayPal\Braintree\Gateway\Request\AddressDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use PayPal\Braintree\Gateway\Data\AddressAdapterInterface;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class AddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private PaymentDataObjectInterface|MockObject $paymentDOMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private OrderAdapterInterface|MockObject $orderMock;

    /**
     * @var AddressDataBuilder
     */
    private AddressDataBuilder $builder;

    /**
     * @var SubjectReader|MockObject
     */
    private MockObject|SubjectReader $subjectReaderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new AddressDataBuilder($this->subjectReaderMock);
    }

    /**
     */
    public function testBuildReadPaymentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $buildSubject = [
            'payment' => null,
        ];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willThrowException(new InvalidArgumentException());

        $this->builder->build($buildSubject);
    }

    public function testBuildNoAddresses()
    {
        $this->paymentDOMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn(null);
        $this->orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn(null);

        $buildSubject = [
            'payment' => $this->paymentDOMock,
        ];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDOMock);

        static::assertEquals([], $this->builder->build($buildSubject));
    }

    /**
     * @param array $addressData
     * @param array $expectedResult
     *
     * @dataProvider dataProviderBuild
     */
    public function testBuild(array $addressData, array $expectedResult)
    {
        $addressMock = $this->getAddressMock($addressData);

        $this->paymentDOMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn($addressMock);
        $this->orderMock->expects(static::once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $buildSubject = [
            'payment' => $this->paymentDOMock,
        ];

        $this->subjectReaderMock->expects(self::once())
            ->method('readPayment')
            ->with($buildSubject)
            ->willReturn($this->paymentDOMock);

        self::assertEquals($expectedResult, $this->builder->build($buildSubject));
    }

    /**
     * @return array
     */
    public static function dataProviderBuild(): array
    {
        return [
            [
                [
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'company' => 'Magento',
                    'street' => ['street1', 'street2', 'street3', 'street4'],
                    'city' => 'Chicago',
                    'region_code' => 'IL',
                    'country_id' => 'US',
                    'post_code' => '00000'
                ],
                [
                    AddressDataBuilder::SHIPPING_ADDRESS => [
                        AddressDataBuilder::FIRST_NAME => 'John',
                        AddressDataBuilder::LAST_NAME => 'Smith',
                        AddressDataBuilder::COMPANY => 'Magento',
                        AddressDataBuilder::STREET_ADDRESS => 'street1',
                        AddressDataBuilder::EXTENDED_ADDRESS => 'street2, street3, street4',
                        AddressDataBuilder::LOCALITY => 'Chicago',
                        AddressDataBuilder::REGION => 'IL',
                        AddressDataBuilder::POSTAL_CODE => '00000',
                        AddressDataBuilder::COUNTRY_CODE => 'US'

                    ],
                    AddressDataBuilder::BILLING_ADDRESS => [
                        AddressDataBuilder::FIRST_NAME => 'John',
                        AddressDataBuilder::LAST_NAME => 'Smith',
                        AddressDataBuilder::COMPANY => 'Magento',
                        AddressDataBuilder::STREET_ADDRESS => 'street1',
                        AddressDataBuilder::EXTENDED_ADDRESS => 'street2, street3, street4',
                        AddressDataBuilder::LOCALITY => 'Chicago',
                        AddressDataBuilder::REGION => 'IL',
                        AddressDataBuilder::POSTAL_CODE => '00000',
                        AddressDataBuilder::COUNTRY_CODE => 'US'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $addressData
     * @return AddressAdapterInterface|MockObject
     * @throws Exception
     */
    private function getAddressMock(array $addressData): MockObject|AddressAdapterInterface
    {
        $addressMock = $this->createMock(AddressAdapterInterface::class);

        $addressMock->expects(static::exactly(2))
            ->method('getFirstname')
            ->willReturn($addressData['first_name']);
        $addressMock->expects(static::exactly(2))
            ->method('getLastname')
            ->willReturn($addressData['last_name']);
        $addressMock->expects(static::exactly(2))
            ->method('getCompany')
            ->willReturn($addressData['company']);
        $addressMock->expects(static::exactly(2))
            ->method('getStreet')
            ->willReturn($addressData['street']);
        $addressMock->expects(static::exactly(2))
            ->method('getCity')
            ->willReturn($addressData['city']);
        $addressMock->expects(static::exactly(2))
            ->method('getRegionCode')
            ->willReturn($addressData['region_code']);
        $addressMock->expects(static::exactly(2))
            ->method('getPostcode')
            ->willReturn($addressData['post_code']);
        $addressMock->expects(static::exactly(2))
            ->method('getCountryId')
            ->willReturn($addressData['country_id']);

        return $addressMock;
    }
}
