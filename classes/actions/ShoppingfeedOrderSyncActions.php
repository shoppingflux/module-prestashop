<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from 202 ecommerce is strictly forbidden.
 *
 * @author    202 ecommerce <contact@202-ecommerce.com>
 * @copyright Copyright (c) 202 ecommerce 2017
 * @license   Commercial license
 *
 * Support <support@202-ecommerce.com>
 */

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingFeed\Sdk\Api\Session\SessionResource;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingFeed\Sdk\Api\Order\OrderOperation;

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedOrder.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedTaskOrder.php');


class ShoppingfeedOrderSyncActions extends DefaultActions
{
    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }
    
    public function saveOrder()
    {
        if (empty($this->conveyor['id_order'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('SF Order not imported; no ID order found', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $id_order = $this->conveyor['id_order'];
        $logPrefix = static::getLogPrefix($id_order);
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('SF Order not imported; Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );
            return false;
        }
        
        $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($id_order);
        if (Validate::isLoadedObject($shoppingfeedOrder)) {
            return true;
        }

        $newShoppingFeedOrder = new ShoppingfeedOrder();
        $newShoppingFeedOrder->name_marketplace = $order->payment;
        $newShoppingFeedOrder->id_order = $id_order;
        $newShoppingFeedOrder->save();

        ProcessLoggerHandler::logSuccess(
            $logPrefix . ' ' .
                $this->l('SF Order successfully saved', 'ShoppingfeedOrderSyncActions'),
            'Order',
            $id_order
        );

        return true;
    }

    public function saveTaskOrder()
    {
        if (empty($this->conveyor['id_order'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('Order not registered for synchronization; no ID order found', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $id_order = $this->conveyor['id_order'];
        $logPrefix = static::getLogPrefix($id_order);
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );
            return false;
        }
        
        if (empty($this->conveyor['order_action'])) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; no Action found', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );
            return false;
        }
        $action = $this->conveyor['order_action'];

        // Before saving a task, we need to check if the SF order reference has
        // been retrieved
        $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($id_order);
        if (!Validate::isLoadedObject($shoppingfeedOrder)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; SF Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );
            return false;
        }
        if (!$shoppingfeedOrder->setReferenceFromOrder()) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Could not retrieve SF Order reference.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );
            return false;
        }
        
        // Check if task order already exists
        $shoppingfeedTaskOrder = ShoppingfeedTaskOrder::getFromOrderAction(
            $id_order,
            $action
        );
        
        if (false === $shoppingfeedTaskOrder || !Validate::isLoadedObject($shoppingfeedTaskOrder)) {
            $shoppingfeedTaskOrder = new ShoppingfeedTaskOrder();
            $shoppingfeedTaskOrder->action = $action;
            $shoppingfeedTaskOrder->id_order = (int)$id_order;
        }

        // When setting a status to "shipped", the synchronization must be
        // delayed to give the merchant time to fill the tracking number
        $updateAt = time();
        $shippedStatuses = json_decode(Configuration::get(ShoppingFeed::SHIPPED_ORDERS));
        if (in_array($order->current_state, $shippedStatuses)) {
            $updateAt += (60 * Configuration::get(ShoppingFeed::ORDER_STATUS_TIME_SHIFT));
        }

        // Save the task order
        $shoppingfeedTaskOrder->update_at = date('Y-m-d H:i:s', $updateAt);
        $shoppingfeedTaskOrder->save();

        ProcessLoggerHandler::logSuccess(
            sprintf(
                $logPrefix . ' ' .
                    $this->l('Order registered for synchronization; action %s', 'ShoppingfeedOrderSyncActions'),
                $action
            ),
            'Order',
            $id_order
        );
        
        return true;
    }
    
    public function getTaskOrders()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $id_shop = (int)$this->conveyor['id_shop'];
        
        if (empty($this->conveyor['order_action'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('Could not retrieve Task Orders; no order action found', 'ShoppingfeedProductSyncActions'),
                'Order'
            );
            return false;
        }
        $action = $this->conveyor['order_action'];
        
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_task_order', 'sto')
            ->innerJoin('orders', 'o', 'o.id_order = sto.id_order')
            ->where('action = "' . pSQL($action) . '"')
            ->where('update_at IS NOT NULL')
            ->where('update_at <= "' . pSQL(date('Y-m-d H:i:s')) . '"')
            ->where('id_shop = ' . $id_shop)
            ->orderBy('sto.date_upd ASC')
            ->limit((int)Configuration::get(ShoppingFeed::ORDER_STATUS_MAX_ORDERS, null, null, $id_shop));
        $taskOrdersData = DB::getInstance()->executeS($query);
        if (false === $taskOrdersData) {
            ProcessLoggerHandler::logInfo(
                $this->l('Could not retrieve Task Orders.', 'ShoppingfeedProductSyncActions'),
                'Order'
            );
            return false;
        }
        
        $this->conveyor['taskOrders'] = array();
        foreach($taskOrdersData as $taskOrderData) {
            $taskOrder = new ShoppingfeedTaskOrder();
            $taskOrder->hydrate($taskOrderData);
            $this->conveyor['taskOrders'][] = $taskOrder;
        }
        
        return true;
    }
    
    public function prepareTaskOrdersSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $id_shop = (int)$this->conveyor['id_shop'];
        
        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No Task Orders to prepare.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $taskOrders = $this->conveyor['taskOrders'];
            
        $shipped_status = json_decode(Configuration::get(Shoppingfeed::SHIPPED_ORDERS, null, null, $id_shop));
        $cancelled_status = json_decode(Configuration::get(Shoppingfeed::CANCELLED_ORDERS, null, null, $id_shop));
        $refunded_status = json_decode(Configuration::get(Shoppingfeed::REFUNDED_ORDERS, null, null, $id_shop));
        
        $this->conveyor['preparedTaskOrders'] = array();
        foreach ($taskOrders as $taskOrder) {
            /** @var $taskOrder ShoppingfeedTaskOrder */
            $logPrefix = self::getLogPrefix($taskOrder->id_order);
            $order = new Order($taskOrder->id_order);
            if (!Validate::isLoadedObject($order)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                continue;
            }
            
            $sfOrder = ShoppingfeedOrder::getByIdOrder($taskOrder->id_order);
            if (empty($sfOrder->id_order_marketplace)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('No SF Order reference set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                continue;
            }
            
            if (empty($sfOrder->name_marketplace)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('No SF Order marketplace set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                continue;
            }
            
            $taskOrderOperation = null;
            $taskOrderPayload = array();
            if (in_array($order->current_state, $shipped_status)) {
                $taskOrderOperation = OrderOperation::TYPE_SHIP;
                
                // Default values...
                $taskOrderPayload = array(
                    'carrier_name' => '',
                    'tracking_number' => '',
                    'tracking_url' => '',
                );
                
                $carrier = new Carrier((int)$order->id_carrier);
                if (!Validate::isLoadedObject($carrier)) {
                    ProcessLoggerHandler::logWarning(
                        $logPrefix . ' ' .
                            $this->l('Carrier could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                        'Order',
                        $taskOrder->id_order
                    );
                }
            
                // We don't support multi-shipping; use the first shipping method
                $orderShipping = $order->getShipping();
                if (!empty($orderShipping[0])) {
                    // From the old module. Not sure if it's all useful, but
                    // we'll suppose it knows better.
                    // Build the tracking url.
                    $orderTrackingUrl = str_replace('http://http://', 'http://', $carrier->url);
                    $orderTrackingUrl = str_replace('@', $orderShipping[0]['tracking_number'], $orderTrackingUrl);
                    
                    $taskOrderPayload = array(
                        // "state_name" is indeed the carrier's name...
                        'carrier_name' => $orderShipping[0]['state_name'],
                        'tracking_number' => $orderShipping[0]['tracking_number'],
                        'tracking_url' => $orderTrackingUrl,
                    );
                }
            } elseif (in_array($order->current_state, $cancelled_status)) {
                $taskOrderOperation = OrderOperation::TYPE_CANCEL;
                // The "reason" field is not supported (at least for now)
            } elseif (in_array($order->current_state, $refunded_status)) {
                $taskOrderOperation = OrderOperation::TYPE_REFUND;
                // No partial refund (at least for now), so no optional
                // parameters to set.
            } else {
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('No matching operation for Order State %d. Deleting Task Order.', 'ShoppingfeedOrderSyncActions'),
                        $order->current_state
                    ),
                    'Order'
                );
                $taskOrder->delete();
                continue;
            }
            
            $this->conveyor['preparedTaskOrders'][] = array(
                'reference_marketplace' => $sfOrder->id_order_marketplace,
                'marketplace' => $sfOrder->name_marketplace,
                'taskOrder' => $taskOrder,
                'operation' => $taskOrderOperation,
                'payload' => $taskOrderPayload,
            );
        }
        
        return true;
    }

    public function sendTaskOrdersSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        if (empty($this->conveyor['preparedTaskOrders'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No prepared Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        $result = $shoppingfeedApi->updateMainStoreOrdersStatus($this->conveyor['preparedTaskOrders']);
        
        if (!$result) {
            return false;
        }
        
        // Index each task order with its order reference
        $preparedTaskOrders = array();
        foreach ($this->conveyor['preparedTaskOrders'] as $preparedTaskOrder) {
            $preparedTaskOrders[$preparedTaskOrder['reference_marketplace']] = $preparedTaskOrder;
        }
        unset($preparedTaskOrder);
        
        // Check each ticket, and match it with its task order
        $this->conveyor['successfulTaskOrders'] = array();
        foreach($result->getTickets() as $ticket) {
            $ticketOrderReference = $ticket->getPayloadProperty('reference');
            $taskOrder = $preparedTaskOrders[$ticketOrderReference]['taskOrder'];
            
            $taskOrder->ticket_number = $ticket->getId();
            $taskOrder->batch_id = $ticket->getBatchId();
            $taskOrder->action = ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS;
            $taskOrder->save();
            
            ProcessLoggerHandler::logInfo(
                sprintf(
                    static::getLogPrefix($taskOrder->id_order) . ' ' .
                        $this->l('Ticket created for Order %s Status %s', 'ShoppingfeedOrderSyncActions'),
                    $ticketOrderReference,
                    $preparedTaskOrders[$ticketOrderReference]['operation']
                ),
                'Order',
                $taskOrder->id_order
            );
            
            $this->conveyor['successfulTaskOrders'] = $taskOrder;
            unset($preparedTaskOrders[$ticketOrderReference]);
        }
        
        // Any remaining task order is an error
        $this->conveyor['failedTaskOrders'] = array();
        foreach ($preparedTaskOrders as $preparedTaskOrder) {
            ProcessLoggerHandler::logError(
                sprintf(
                    static::getLogPrefix($taskOrder->id_order) . ' ' .
                        $this->l('No ticket could be created for Order %s Status %s', 'ShoppingfeedOrderSyncActions'),
                    $preparedTaskOrder['reference_marketplace'],
                    $preparedTaskOrder['operation']
                ),
                'Order',
                $preparedTaskOrder['taskOrder']->id_order
            );
            $this->conveyor['failedTaskOrders'] = $preparedTaskOrder['taskOrder'];
        }
        
        return true;
    }
    
    public function prepareTaskOrdersCheckTicketsSyncStatus()
    {
        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No Task Orders to prepare.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        $taskOrders = $this->conveyor['taskOrders'];
        
        // We don't have much to prepare; just make sure every task order has a
        // ticket number, and link each task to its SF order
        $this->conveyor['preparedTaskOrders'] = array();
        foreach ($taskOrders as $taskOrder) {
            /** @var $taskOrder ShoppingfeedTaskOrder */
            $logPrefix = self::getLogPrefix($taskOrder->id_order);
            
            if (!$taskOrder->ticket_number) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('No ticket number set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                continue;
            }
            
            $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($taskOrder->id_order);
            if (!Validate::isLoadedObject($shoppingfeedOrder)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('Could not check ticket status; SF Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                continue;
            }
            
            $this->conveyor['preparedTaskOrders'][] = array(
                'taskOrder' => $taskOrder,
                'ticket_number' => $taskOrder->ticket_number,
                'reference_marketplace' => $shoppingfeedOrder->id_order_marketplace,
            );
        }
        
        return true;
    }
    
    public function sendTaskOrdersCheckTicketsSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logError(
                    $this->l('No Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        $tickets = $shoppingfeedApi->getTicketsByReference($this->conveyor['preparedTaskOrders']);
        if (!$tickets) {
            return false;
        }
        
        // Index each task order with its order reference
        $preparedTaskOrders = array();
        foreach ($this->conveyor['preparedTaskOrders'] as $preparedTaskOrder) {
            $preparedTaskOrders[$preparedTaskOrder['reference_marketplace']] = $preparedTaskOrder;
        }
        unset($preparedTaskOrder);
        
        // Check each ticket, and match it with its task order
        // Don't delete task orders here, we may need them later !
        $this->conveyor['successfulTaskOrders'] = array();
        $this->conveyor['failedTaskOrders'] = array();
        foreach($tickets as $ticket) {
            $ticketOrderReference = $ticket->getPayloadProperty('reference');
            $taskOrder = $preparedTaskOrders[$ticketOrderReference]['taskOrder'];
            
            // Check ticket status.
            // Basically, we're just sorting them. We'll decide what to do with
            // them later...
            switch ($ticket->getStatus()) {
                case 'failed':
                case 'canceled':
                    $this->conveyor['failedTaskOrders'][] = $taskOrder;
                    break;
                case 'scheduled':
                case 'running':
                    // Don't do anything, we'll just send them again later
                    break;
                case 'succeed':
                    $this->conveyor['successfulTaskOrders'][] = $taskOrder;
                    break;
            }
        }
        
        return true;
    }
}
