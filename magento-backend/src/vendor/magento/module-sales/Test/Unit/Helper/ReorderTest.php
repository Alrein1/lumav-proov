<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sales\Test\Unit\Helper;

use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReorderTest extends TestCase
{
    /**
     * @var Reorder
     */
    protected $helper;

    /**
     * @var MockObject|ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject|\Magento\Store\Model\Store
     */
    protected $storeParam;

    /**
     * @var MockObject|\Magento\Sales\Model\Order
     */
    protected $orderMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $repositoryMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->repositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->helper = new Reorder(
            $contextMock,
            $this->repositoryMock
        );

        $this->storeParam = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests that the store config is checked if orders can be reordered.
     *
     * @dataProvider getScopeConfigValue
     * @return void
     */
    public function testIsAllowedScopeConfigReorder($scopeConfigValue)
    {
        $this->setupScopeConfigMock($scopeConfigValue);
        $this->assertEquals($scopeConfigValue, $this->helper->isAllowed($this->storeParam));
    }

    /**
     * Tests that the store config is still checked with a null store.
     *
     * @dataProvider getScopeConfigValue
     * @return void
     */
    public function testIsAllowScopeConfigReorderNotAllowWithStore($scopeConfigValue)
    {
        $this->storeParam = null;
        $this->setupScopeConfigMock($scopeConfigValue);
        $this->assertEquals($scopeConfigValue, $this->helper->isAllow());
    }

    /**
     * @return array
     */
    public static function getScopeConfigValue()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Sets up the scope config mock with a specified return value.
     *
     * @param bool $returnValue
     * @return void
     */
    protected function setupScopeConfigMock($returnValue)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Reorder::XML_PATH_SALES_REORDER_ALLOW,
                ScopeInterface::SCOPE_STORE,
                $this->storeParam
            )
            ->willReturn($returnValue);
    }

    /**
     * Tests that if the store does not allow reorders, it does not matter what the Order returns.
     *
     * @return void
     */
    public function testCanReorderStoreNotAllowed()
    {
        $this->setupOrderMock(false);
        $this->repositoryMock->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->orderMock);
        $this->assertFalse($this->helper->canReorder(1));
    }

    /**
     * Tests what happens if the customer is logged in and the order does or does not allow reorders.
     *
     * @param bool $orderCanReorder
     * @return void
     * @dataProvider getOrderCanReorder
     */
    public function testCanReorder($orderCanReorder)
    {
        $this->setupOrderMock(true);

        $this->orderMock->expects($this->once())
            ->method('canReorder')
            ->willReturn($orderCanReorder);
        $this->repositoryMock->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->orderMock);
        $this->assertEquals($orderCanReorder, $this->helper->canReorder(1));
    }

    /**
     * Sets up the order mock to return a store config which will return a specified value on a getValue call.
     *
     * @param bool $storeScopeReturnValue
     * @return void
     */
    protected function setupOrderMock($storeScopeReturnValue)
    {
        $this->setupScopeConfigMock($storeScopeReturnValue);
        $this->orderMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeParam);
    }

    /**
     * @return array
     */
    public static function getOrderCanReorder()
    {
        return [
            [true],
            [false]
        ];
    }
}
