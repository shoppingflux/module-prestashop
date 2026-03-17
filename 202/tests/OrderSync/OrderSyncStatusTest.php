<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Tests\OrderSync;

use PHPUnit\Framework\TestCase;
use ShoppingFeed\Sdk\Api\Order\OrderOperationResult;
use ShoppingFeed\Sdk\Hal\HalResource;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;

class OrderSyncStatusTest extends TestCase
{
    /**
     * @desc getTaskOrders
     */
    public function testGetTaskOrders()
    {
        Registry::set('syncStatusErrors', 0);
        $orderStatusHandler = new ActionsHandler();
        $orderStatusHandler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'order_action' => \ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
            ]
        );
        $orderStatusHandler->addActions(
            'getTaskOrders',
            'prepareTaskOrdersSyncStatus'
        );
        $orderStatusHandler->process('ShoppingfeedOrderSync');
        $processData = $orderStatusHandler->getConveyor();
        $this->assertEquals(8, count($processData['taskOrders']));
        $this->assertEquals(2, count($processData['preparedTaskOrders']));
        $this->assertEquals(0, Registry::get('syncStatusErrors', 0));

        // we update state like after a successful API call in `sendTaskOrdersSyncStatus`
        foreach ($processData['preparedTaskOrders'] as $operation => $preparedTaskOrders) {
            foreach ($preparedTaskOrders as $preparedTaskOrder) {
                $preparedTaskOrder['taskOrder']->action = \ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS;
                $preparedTaskOrder['taskOrder']->batch_id = $operation;
                $preparedTaskOrder['taskOrder']->save();
            }
        }
    }

    /**
     * @desc getTicketsStatus
     *
     * @depends testGetTaskOrders
     */
    public function testGetTicketsStatus()
    {
        Registry::set('syncStatusErrors', 0);
        $ticketsHandler = new ActionsHandler();
        $ticketsHandler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'order_action' => \ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS,
            ]
        );
        $ticketsHandler->addActions(
            'getTaskOrders',
            'prepareTaskOrdersCheckTicketsSyncStatus'
        );
        $ticketsHandler->process('ShoppingfeedOrderSync');
        $processData = $ticketsHandler->getConveyor();
        $this->assertEquals(8, count($processData['preparedTaskOrders']));
    }

    public function testHandlingReportInResponseSyncStatus()
    {
        $taskOrderIgnored = new \ShoppingfeedTaskOrder();
        $taskOrderIgnored->action = \ShoppingfeedTaskOrder::ACTION_SYNC_STATUS;
        $taskOrderIgnored->id_order = 1;
        $taskOrderIgnored->save();

        $taskOrderToSync = new \ShoppingfeedTaskOrder();
        $taskOrderToSync->action = \ShoppingfeedTaskOrder::ACTION_SYNC_STATUS;
        $taskOrderToSync->id_order = 1;
        $taskOrderToSync->save();

        $id_internal_shoppingfeed = '123456';

        $this->assertTrue(\Validate::isLoadedObject($taskOrderIgnored));

        $orderOperationResult = $this->createOrderOperationResultMock($this->getResponseWithReport($id_internal_shoppingfeed));
        $mockOrderSyncActions = $this->createSyncActionMock($orderOperationResult);

        $mockOrderSyncActions->setConveyor([
            'id_shop' => 1,
            'id_token' => 1,
            'preparedTaskOrders' => [
                [
                    [
                        'taskOrder' => $taskOrderIgnored,
                        'id_internal_shoppingfeed' => $id_internal_shoppingfeed,
                    ],
                ],
                [
                    [
                        'taskOrder' => $taskOrderToSync,
                        'id_internal_shoppingfeed' => '',
                    ],
                ],
            ],
        ]);

        $mockOrderSyncActions->sendTaskOrdersSyncStatus();

        $this->assertFalse(\Validate::isLoadedObject(new \ShoppingfeedTaskOrder($taskOrderIgnored->id)));
        $this->assertEquals(\ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS, (new \ShoppingfeedTaskOrder($taskOrderToSync->id))->action);
    }

    public function testHandlingReportInResponseSyncInvoice()
    {
        $taskOrderIgnored = new \ShoppingfeedTaskOrder();
        $taskOrderIgnored->action = \ShoppingfeedTaskOrder::ACTION_UPLOAD_INVOICE;
        $taskOrderIgnored->id_order = 1;
        $taskOrderIgnored->save();

        $taskOrderToSync = new \ShoppingfeedTaskOrder();
        $taskOrderToSync->action = \ShoppingfeedTaskOrder::ACTION_UPLOAD_INVOICE;
        $taskOrderToSync->id_order = 1;
        $taskOrderToSync->save();

        $id_internal_shoppingfeed = '123456';

        $this->assertTrue(\Validate::isLoadedObject($taskOrderIgnored));

        $orderOperationResult = $this->createOrderOperationResultMock($this->getResponseWithReport($id_internal_shoppingfeed));
        $mockOrderSyncActions = $this->createSyncActionMock($orderOperationResult);

        $mockOrderSyncActions->setConveyor([
            'id_shop' => 1,
            'id_token' => 1,
            'preparedTaskOrders' => [
                [
                    [
                        'taskOrder' => $taskOrderIgnored,
                        'id_internal_shoppingfeed' => $id_internal_shoppingfeed,
                    ],
                ],
                [
                    [
                        'taskOrder' => $taskOrderToSync,
                        'id_internal_shoppingfeed' => '',
                    ],
                ],
            ],
        ]);

        $mockOrderSyncActions->sendTaskOrdersSyncInvoice();

        $this->assertFalse(\Validate::isLoadedObject(new \ShoppingfeedTaskOrder($taskOrderIgnored->id)));
        $this->assertEquals(\ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_UPLOAD_INVOICE, (new \ShoppingfeedTaskOrder($taskOrderToSync->id))->action);
    }

    public function testHandlingReportInResponsePartialRefund()
    {
        $taskOrderIgnored = new \ShoppingfeedTaskOrder();
        $taskOrderIgnored->action = \ShoppingfeedTaskOrder::ACTION_PARTIAL_REFUND;
        $taskOrderIgnored->id_order = 1;
        $taskOrderIgnored->save();

        $taskOrderToSync = new \ShoppingfeedTaskOrder();
        $taskOrderToSync->action = \ShoppingfeedTaskOrder::ACTION_PARTIAL_REFUND;
        $taskOrderToSync->id_order = 1;
        $taskOrderToSync->save();

        $id_internal_shoppingfeed = '123456';

        $this->assertTrue(\Validate::isLoadedObject($taskOrderIgnored));

        $orderOperationResult = $this->createOrderOperationResultMock($this->getResponseWithReport($id_internal_shoppingfeed));
        $mockOrderSyncActions = $this->createSyncActionMock($orderOperationResult);

        $mockOrderSyncActions->setConveyor([
            'id_shop' => 1,
            'id_token' => 1,
            'preparedTaskOrders' => [
                [
                    [
                        'taskOrder' => $taskOrderIgnored,
                        'id_internal_shoppingfeed' => $id_internal_shoppingfeed,
                    ],
                ],
                [
                    [
                        'taskOrder' => $taskOrderToSync,
                        'id_internal_shoppingfeed' => '',
                    ],
                ],
            ],
        ]);

        $mockOrderSyncActions->sendTaskOrdersSyncPartialRefund();

        $this->assertFalse(\Validate::isLoadedObject(new \ShoppingfeedTaskOrder($taskOrderIgnored->id)));
        $this->assertEquals(\ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_PARTIAL_REFUND, (new \ShoppingfeedTaskOrder($taskOrderToSync->id))->action);
    }

    protected function createOrderOperationResultMock(string $response)
    {
        $data = json_decode($response, true);
        $client = $this->createMock(\ShoppingFeed\Sdk\Hal\HalClient::class);
        $halResource = $this
            ->getMockBuilder(HalResource::class)
            ->setConstructorArgs([$client, $data, $data['_links']])
            ->addMethods(['getFirstResources'])
            ->getMock();

        return new OrderOperationResult([$halResource]);
    }

    protected function getResponseWithReport($id_internal_shoppingfeed)
    {
        $response = file_get_contents(__DIR__ . '/dataset/order_sync_status_report_response.json');
        $response = str_replace('{{id_internal_shoppingfeed}}', $id_internal_shoppingfeed, $response);

        return $response;
    }

    protected function createSyncActionMock(OrderOperationResult $orderOperationResult)
    {
        $mockShoppingfeedApi = $this->createMock(\ShoppingfeedApi::class);
        $mockShoppingfeedApi
            ->expects($this->any())
            ->method('updateMainStoreOrdersStatus')
            ->willReturn($orderOperationResult);

        $mockOrderSyncActions = $this->getMockBuilder(\ShoppingfeedOrderSyncActions::class)
            ->onlyMethods(['getShoppingfeedApiInstance'])
            ->enableOriginalConstructor()
            ->getMock();
        $mockOrderSyncActions
            ->expects($this->any())
            ->method('getShoppingfeedApiInstance')
            ->willReturn($mockShoppingfeedApi);

        return $mockOrderSyncActions;
    }
}
