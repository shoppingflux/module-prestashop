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

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingfeedAddon\CarrierRule\CarrierRuleResolver;
use ShoppingfeedAddon\CarrierRule\CarrierSelection;
use ShoppingfeedAddon\CarrierRule\ConditionEvaluator;
use ShoppingfeedAddon\CarrierRule\OrderCriteria;
use ShoppingfeedAddon\CarrierRule\OrderWeightCalculator;
use ShoppingfeedAddon\OrderImport\OrderData;

class CarrierFinder
{
    protected $db;

    /** @var CarrierRuleResolver */
    protected $carrierRuleResolver;

    /** @var OrderWeightCalculator */
    protected $orderWeightCalculator;

    public function __construct(?CarrierRuleResolver $carrierRuleResolver = null, ?OrderWeightCalculator $orderWeightCalculator = null)
    {
        $this->db = \Db::getInstance();
        $this->carrierRuleResolver = $carrierRuleResolver ? $carrierRuleResolver : new CarrierRuleResolver(new ConditionEvaluator());
        $this->orderWeightCalculator = $orderWeightCalculator ? $orderWeightCalculator : new OrderWeightCalculator();
    }

    /**
     * Carrier of an imported order: the carrier selection rules when the
     * advanced engine is enabled (first matching rule, then the default
     * carrier; the marketplace mapping is not used), otherwise the mapping
     * then the default carrier.
     *
     * @param OrderData $orderData
     * @param int $idToken
     * @param string $channelName
     * @param string|null $apiCarrierName
     * @param \Product[] $prestashopProducts mapped products by order item reference
     *
     * @return CarrierSelection
     */
    public function selectCarrierForOrderImport(OrderData $orderData, $idToken, $channelName, $apiCarrierName, array $prestashopProducts)
    {
        if (!\Configuration::getGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES)) {
            return new CarrierSelection($this->getCarrierForOrderImport($channelName, $apiCarrierName), false);
        }

        $criteria = new OrderCriteria(
            (string) $channelName,
            isset($orderData->shippingAddress['country']) ? $orderData->shippingAddress['country'] : '',
            (string) $apiCarrierName,
            $this->orderWeightCalculator->calculate($orderData->items, $prestashopProducts)
        );
        $match = $this->carrierRuleResolver->resolve((int) $idToken, $criteria);
        $skippedRules = $this->carrierRuleResolver->getSkippedRules();

        if ($match === null) {
            return new CarrierSelection($this->getDefaultCarrierForOrderImport(), true, null, $skippedRules);
        }

        return new CarrierSelection($match['carrier'], true, $match['number'], $skippedRules);
    }

    public function findByOrder(\Order $order)
    {
        return new \Carrier($this->getIdCarrierByOrder($order));
    }

    protected function getIdCarrierByOrder(\Order $order)
    {
        try {
            return $this->db->getValue(
                (new \DbQuery())
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
        $carrier = \Carrier::getCarrierByReference((int) \Configuration::get(\Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE));
        $carrier = is_object($carrier) ? $carrier : new \Carrier();

        return $carrier;
    }

    public function getCarrierForOrderImport($channelName, $apiCarrierName)
    {
        $carrier = null;
        $sfCarrier = \ShoppingfeedCarrier::getByMarketplaceAndName(
            $channelName,
            $apiCarrierName
        );

        if (\Validate::isLoadedObject($sfCarrier)) {
            $carrier = \Carrier::getCarrierByReference($sfCarrier->id_carrier_reference);
        }

        if (!\Validate::isLoadedObject($carrier)) {
            $carrier = $this->getDefaultCarrierForOrderImport();
        }

        return $carrier;
    }

    /**
     * Module default carrier, then the shop default carrier
     *
     * @return \Carrier|false|null
     */
    public function getDefaultCarrierForOrderImport()
    {
        $carrier = null;

        if (!empty(\Configuration::get(\Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE))) {
            $carrier = \Carrier::getCarrierByReference(\Configuration::get(\Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }

        if (!\Validate::isLoadedObject($carrier) && !empty(\Configuration::get('PS_CARRIER_DEFAULT'))) {
            $carrier = new \Carrier((int) \Configuration::get('PS_CARRIER_DEFAULT'));
        }

        return $carrier;
    }
}
