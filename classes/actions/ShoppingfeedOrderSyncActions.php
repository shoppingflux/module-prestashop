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
                'Shoppingfeed Order (id: ' . $this->conveyor['id_order'] . ') save successfully',
                'order',
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
            'Shoppingfeed Task Order (id: ' . $this->conveyor['id_order'] . ', action: ' . $this->conveyor['order_action'] . ') save successfully',
            'order',
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
        $session = new SessionResource();
        $orders = $this->conveyor['orders'];
        $orderApi = $session->getMainStore()->getOrderApi();
        $operation = new \ShoppingFeed\Sdk\Api\Order\OrderOperation();

        foreach ($orders as $order) {
            $orderObj = new ShoppingfeedOrder($order['id_order']);

            if ($order['action'] == 'shipped') {
                $operation->ship($orderObj->payment, $orderObj->id_order_marketplace);
            } elseif ($order['action'] == 'cancelled') {
                $operation->cancel($orderObj->payment, $orderObj->id_order_marketplace);
            } elseif ($order['action'] == 'refunded') {
                //$operation->refund($orderObj->payment, $orderObj->id_order_marketplace); //en cour de dev
            }
        }

        $ticketsCollection = $orderApi->execute($operation);

        var_dump($ticketsCollection);
        die();

        /*  // en cour de dev
        $i = 0;
        foreach ($ticketsCollection->getAll() as $ticket) {
            if ($ticket->number != null) {
                $orderObj = new ShoppingfeedTaskOrder();
                $ordersObj->hydrate($orders[$i]);
                $orderObj->ticket_number = $tickets->getId();
                $orderObj->save();
                $i++;
            }


        }
        */
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