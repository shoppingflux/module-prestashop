<?php
/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\OrderImport;

use Configuration;
use Db;
use DbQuery;
use Language;
use OrderState;
use Validate;

class SFOrderState
{
    const SF_ORDER_STATE = 'SHOPPINGFEED_ORDER_STATE';

    protected $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function get()
    {
        $sfOrderState = new OrderState(Configuration::getGlobalValue(self::SF_ORDER_STATE));

        if (Validate::isLoadedObject($sfOrderState)) {
            return $sfOrderState;
        }

        $sfOrderState = $this->create();

        if (Validate::isLoadedObject($sfOrderState)) {
            return $sfOrderState;
        }

        return $this->getDefault();
    }

    protected function getDefault()
    {
        return new OrderState(Configuration::get('PS_OS_PAYMENT'));
    }

    protected function create()
    {
        //check if the order state exists already
        $query = (new DbQuery())
            ->from('order_state')
            ->where('module_name = "shoppingfeed"')
            ->select('id_order_state');

        $orderState = new OrderState($this->db->getValue($query));
        $orderState->module_name = 'shoppingfeed';
        $orderState->logable = true;
        $orderState->name = [];

        foreach (Language::getLanguages(false) as $lang) {
            $orderState->name[$lang['id_lang']] = 'Imported from Shoppingfeed';
        }

        $orderState->save();
        Configuration::updateGlobalValue(self::SF_ORDER_STATE, $orderState->id);

        return $orderState;
    }
}
