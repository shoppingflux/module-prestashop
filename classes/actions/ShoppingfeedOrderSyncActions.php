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
                $transaction_id = $message[1];
            }

            if (!$transaction_id) {
                throw new PrestaShopException('Fail: id_order_marketplace not found');
            }

            $currentOrder = new ShoppingfeedOrder();
            $currentOrder->id_order_marketplace = $transaction_id;
            $currentOrder->name_marketplace = $order->module;
            $currentOrder->id_order = $this->conveyor['id_order'];
            $currentOrder->payment = $order->payment;

            $currentOrder->save();
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
        $date = $date + (60 * Configuration::get(ShoppingFeed::STATUS_TIME_SHIT));
        $date = date("Y-m-d H:i:s", $date);
        $currentTaskOrder->update_at = $date;

        $currentTaskOrder->save(true);
    }

    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }

    public function sendOrder()
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
                $operation->refuse($orderObj->payment, $orderObj->id_order_marketplace);
            }
        }

        $tickets = $orderApi->execute($operation);

        var_dump($tickets);die();
    }
}