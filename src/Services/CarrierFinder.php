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

namespace ShoppingfeedAddon\Services;

use Carrier;
use Configuration;
use Db;
use DbQuery;
use Order;
use Shoppingfeed;
use ShoppingfeedCarrier;
use Validate;

class CarrierFinder
{
    protected $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function findByOrder(Order $order)
    {
        return new Carrier($this->getIdCarrierByOrder($order));
    }

    protected function getIdCarrierByOrder(Order $order)
    {
        try {
            return $this->db->getValue(
                (new DbQuery())
                    ->select('id_carrier')
                    ->from('order_carrier')
                    ->where('id_order = ' . (int) $order->id)
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function findProductFeedCarrier()
    {
        $carrier = Carrier::getCarrierByReference((int) Configuration::get(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE));
        $carrier = is_object($carrier) ? $carrier : new Carrier();

        return $carrier;
    }

    public function getCarrierForOrderImport($channelName, $apiCarrierName)
    {
        $carrier = null;
        $sfCarrier = ShoppingfeedCarrier::getByMarketplaceAndName(
            $channelName,
            $apiCarrierName
        );

        if (Validate::isLoadedObject($sfCarrier)) {
            $carrier = Carrier::getCarrierByReference($sfCarrier->id_carrier_reference);
        }

        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE))) {
            $carrier = Carrier::getCarrierByReference(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }

        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get('PS_CARRIER_DEFAULT'))) {
            $carrier = new Carrier(Configuration::get('PS_CARRIER_DEFAULT'));
        }

        return $carrier;
    }
}
