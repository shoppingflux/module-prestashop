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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedTaskOrder.php');

class ShoppingfeedOrderSyncActions extends DefaultActions
{
    public function saveOrder()
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_task_order')
            ->where("id_order = '" . (int)$this->conveyor['id_order'] . "'")
            ->where('ticket_number IS NULL');
        $exist = DB::getInstance()->executeS($query);

        if (empty($exist)) {
            $currentTaskOrder = new ShoppingfeedTaskOrder();
            $currentTaskOrder->action = $this->conveyor['order_action'];
            $currentTaskOrder->id_order = $this->conveyor['id_order'];
            $currentTaskOrder->ticket_number = null;
            $currentTaskOrder->update_at = date("Y-m-d H:i:s");
            $currentTaskOrder->date_add = date("Y-m-d H:i:s");
            $currentTaskOrder->date_upd = date("Y-m-d H:i:s");
        } else {
            $currentTaskOrder = new ShoppingfeedTaskOrder();
            $currentTaskOrder->hydrate($exist[0]);
            $currentTaskOrder->action = $this->conveyor['order_action'];
            $currentTaskOrder->update_at = date("Y-m-d H:i:s");
            $currentTaskOrder->date_upd = date("Y-m-d H:i:s");
        }

        $currentTaskOrder->save();
    }

    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }
}