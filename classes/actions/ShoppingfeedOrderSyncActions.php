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
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_task_order')
            ->where('update_at < "' . date('Y-m-d H:i:s') . '"')
            ->orderBy('date_upd ASC')
            ->limit((int)Configuration::get(ShoppingFeed::ORDER_STATUS_MAX_ORDERS));
        $taskOrdersData = DB::getInstance()->executeS($query);
        if (empty($taskOrdersData)) {
            return false;
        }
        
        $taskOrders = array();
        foreach($taskOrdersData as $taskOrderData) {
            $taskOrder = new ShoppingfeedTaskOrder();
            $taskOrder->hydrate($taskOrderData);
            $taskOrders[] = $taskOrder;
        }
        
        $this->conveyor['taskOrders'] = $taskOrders;
        return true;
    }
    
    public function prepareTaskOrders()
    {
        $taskOrders = $this->conveyor['taskOrders'];
            
        $shipped_status = json_decode(Configuration::get(self::SHIPPED_ORDERS));
        $cancelled_status = json_decode(Configuration::get(self::CANCELLED_ORDERS));
        $refunded_status = json_decode(Configuration::get(self::REFUNDED_ORDERS));
        $order_action = null;

        if (in_array($params['newOrderStatus']->id, $shipped_status)) {
            $order_action = "shipped";
        } elseif (in_array($params['newOrderStatus']->id, $cancelled_status)) {
            $order_action = "cancelled";
        } elseif (in_array($params['newOrderStatus']->id, $refunded_status)) {
            $order_action = "refunded";
        }
    }

    public function sendTaskOrders()
    {
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        $taskOrders = $this->conveyor['taskOrders'];

        $result = $shoppingfeedApi->updateMainStoreOrdersStatus($taskOrders);
        
        if (!$result) {
            return false;
        }
        
        // Index each task order by the order reference
        
        foreach($result->getTickets() as $ticket) {
            $ticketOrderReference = $ticket->getPayloadProperty('reference');
        }
        
        foreach ($taskOrders as $taskOrder) {
            $shoppingfeedOrder = new ShoppingfeedOrder($taskOrder['id_order']);
            try {
                // If we don't pass the order reference, all tickets matching the
                // action are returned; but we can't link the tickets to their
                // matching order this way
                //
                // We *must* use the action *and* the order reference
                // to get an order's associated ticket number
                //
                // Also, it seems that a ticket number may be linked to multiple
                // order references. An order always seems to have only one
                // ticket though...
                // see ShoppingFeed\Sdk\Api\Order\OrderTicketCollection::findTickets
                switch ($taskOrder['action']) {
                    case ShoppingFeed\Sdk\Api\Order\OrderOperation::TYPE_SHIP:
                        $ticket = $ordersTicketsCollection->getShipped($shoppingfeedOrder->id_order_marketplace);
                        break;
                    case ShoppingFeed\Sdk\Api\Order\OrderOperation::TYPE_CANCEL:
                        $ticket = $ordersTicketsCollection->getCanceled($shoppingfeedOrder->id_order_marketplace);
                        break;
                    case ShoppingFeed\Sdk\Api\Order\OrderOperation::TYPE_REFUND:
                        $ticket = $ordersTicketsCollection->getRefunded($shoppingfeedOrder->id_order_marketplace);
                        break;
                    default:
                       // TODO Logger
                        echo "No action for order " . $shoppingfeedOrder->id_order_marketplace . "\n";
                        // break the switch and continue the foreach
                        continue 2;
                }
            } catch (Exception $e) {
                // TODO Logger
                echo "No ticket for order " . $shoppingfeedOrder->id_order_marketplace . "\n";
                continue;
            }
            
            $shoppingfeedTaskOrder = new ShoppingfeedTaskOrder();
            $shoppingfeedTaskOrder->hydrate($taskOrder);
            // There's always exactly one ticket in the result when using
            // the order reference
            $shoppingfeedTaskOrder->ticket_number = $ticket[0]->getId();
            $shoppingfeedTaskOrder->save();
        }
        return true;
    }

    public function getTicketsFeedback()
    {
        $session = new SessionResource();



        $orders = $this->conveyor['orders'];
        $ticketApi = $session->getMainStore()->getTicketApi();

        $operation = new \ShoppingFeed\Sdk\Api\Ticket\TicketOperations();
        foreach ($orders as $order) {
            //$operation->getTicketStatus();
        }

        $ticketsCollection = $ticketApi->execute($operation);

        /*  // en cour de dev
        $i = 0;
        foreach ($ticketsCollection->getAll() as $ticket) {
            $orderObj = new ShoppingfeedTaskOrder();
            $ordersObj->hydrate($orders[$i]);
            $orderObj->remove();
            $i++;
        }
        */
    }

    public function sendMailOrderSynchroFail($orders)
    {
        $error = "";
        foreach ($orders as $order) {
            $error.= "<p><a href=\"https://app.shopping-feed.com/v3/fr/api\">Order id " . $order['reference'] . " - [" . $order['status'] . "]</a></p>";
        }

        Mail::Send(
            $this->context->language->id,
            'error_synchro',
            Mail::l('Shopping Feed synchronization errors'),
            array(
                '{order_error}' => $error,
                '{cron_task_url}' => Context::getContext()->link->getAdminLink('AdminShoppingfeedProcessMonitor'),
                '{log_page_url}' =>  Context::getContext()->link->getAdminLink('AdminShoppingfeedProcessLogger'),
            ),
            Configuration::get('PS_SHOP_EMAIL'),
            null,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            dirname(__FILE__) . '/mails/'
        );
    }
}
