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

if (!defined('_PS_VERSION_')) {
    exit;
}

class SFOrderState
{
    protected $idShop;

    public function __construct($idShop = null)
    {
        $this->idShop = $idShop;
    }

    public function get()
    {
        $sfOrderState = new \OrderState((int) \Configuration::get(\Shoppingfeed::IMPORT_ORDER_STATE, null, null, $this->idShop));

        if (\Validate::isLoadedObject($sfOrderState)) {
            return $sfOrderState;
        }

        return $this->getDefault();
    }

    public function set($idOrderState)
    {
        \Configuration::updateValue(\Shoppingfeed::IMPORT_ORDER_STATE, $idOrderState, false, null, $this->idShop);

        return $this;
    }

    protected function getDefault()
    {
        return new \OrderState((int) \Configuration::get('PS_OS_PAYMENT'));
    }
}
