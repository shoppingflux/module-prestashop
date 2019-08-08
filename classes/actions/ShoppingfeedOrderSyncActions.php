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
            $sql = "INSERT INTO " . _DB_PREFIX_ . "shoppingfeed_task_order (action, id_order, ticket_number, update_at, date_add, date_upd)
                    VALUES ('" . pSQL($this->conveyor['order_action']) . "', " . (int)$this->conveyor['id_order'] . ", null, '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "')";

        } else {
            $sql = "UPDATE " . _DB_PREFIX_ . "shoppingfeed_task_order
                    SET action = '" . pSQL($this->conveyor['order_action']) . "', update_at = '" . date("Y-m-d H:i:s") . "', date_upd = '" . date("Y-m-d H:i:s") . "' 
                    WHERE id_order = " . (int)$this->conveyor['id_order'] . " AND ticket_number IS NULL";
        }

        DB::getInstance()->execute($sql);
    }

    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }
}