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
}
