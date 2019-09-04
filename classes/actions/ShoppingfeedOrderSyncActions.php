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
    public function saveOrder()
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_order')
            ->where("id_order = '" . (int)$this->conveyor['id_order'] . "'");
        $shoppingfeed_order = DB::getInstance()->getRow($query);

        if (!$shoppingfeed_order) {
            $order = new Order($this->conveyor['id_order']);

            $query = new DbQuery();
            $query->select('message')
                ->from('message')
                ->where("id_order = '" . (int)$this->conveyor['id_order'] . "'")
                ->orderBy("date_add ASC");
            $message = DB::getInstance()->getValue($query);

            $message = explode(':', $message);

            $transaction_id = null;
            if (isset($message[1])) {
                $transaction_id = trim($message[1]);
            }

            if (!$transaction_id) {
                throw new PrestaShopException('Fail: id_order_marketplace not found');
            }

            $currentOrder = new ShoppingfeedOrder();
            $currentOrder->id_order_marketplace = $transaction_id;
            $currentOrder->name_marketplace = $order->payment;
            $currentOrder->id_order = $this->conveyor['id_order'];
            $currentOrder->payment = $order->module;

            $currentOrder->save();

            ProcessLoggerHandler::logSuccess(
                'Shoppingfeed Order save successfully',
                'Order',
                $this->conveyor['id_order']
            );
        }

        return true;
    }

    public function saveTaskOrder()
    {
        $query = new DbQuery();
        $query->select('*')
              ->from('shoppingfeed_task_order')
              ->where("id_order = '" . (int)$this->conveyor['id_order'] . "'")
              ->where('ticket_number IS NULL');
        $exist = DB::getInstance()->getRow($query);

        $currentTaskOrder = new ShoppingfeedTaskOrder();
        if (empty($exist)) {
            $currentTaskOrder->id_order = $this->conveyor['id_order'];
            $currentTaskOrder->ticket_number = null;
        } else {
            $currentTaskOrder->hydrate($exist);
        }
        $currentTaskOrder->action = $this->conveyor['order_action'];

        $date = time("Y-m-d H:i:s");

        if ($currentTaskOrder->action == 'shipped' && $currentTaskOrder->ticket_number == null) {
            $date = $date + (60 * Configuration::get(ShoppingFeed::STATUS_TIME_SHIT));
        }
        $date = date("Y-m-d H:i:s", $date);
        $currentTaskOrder->update_at = $date;

        $currentTaskOrder->save(true);

        ProcessLoggerHandler::logSuccess(
            'Shoppingfeed Task Order (action: ' . $this->conveyor['order_action'] . ') save successfully',
            'Order',
            $this->conveyor['id_order']
        );
    }

    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }

    public function sendOrderWithoutTicket()
    {
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        $taskOrders = $this->conveyor['orders'];

        $ordersTicketsCollection = $shoppingfeedApi->updateMainStoreOrdersStatus($taskOrders);
        
        if (!$ordersTicketsCollection) {
            return false;
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