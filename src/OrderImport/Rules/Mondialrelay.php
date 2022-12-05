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

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use Tools;

class Mondialrelay extends AbstractMondialrelay
{
    /** @var Module */
    protected $module_mondialRelay;

    public function isApplicable(OrderResource $apiOrder)
    {
        if (parent::isApplicable($apiOrder) === false) {
            return false;
        }
        $apiOrderShipment = $apiOrder->getShipment();

        // Check only for name presence in the string; the relay ID could be appended
        // to the field (see ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay)
        if (preg_match('#mondial relay#', Tools::strtolower($apiOrderShipment['carrier']))) {
            return true;
        }

        if (preg_match('#Livraison Magasin produit jusqu\'à 30 kg#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#Livraison Point MR 24R#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#^rel$#i', trim($apiOrderShipment['carrier']))) {
            return true;
        }

        return false;
    }

    public function getRelayId($orderData)
    {
        if ($relayId = parent::getRelayId($orderData)) {
            return $relayId;
        }

        return $orderData->shippingAddress['other'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order has \'Mondial Relay\' in its carrier name.', 'Mondialrelay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
