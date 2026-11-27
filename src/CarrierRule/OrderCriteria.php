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

namespace ShoppingfeedAddon\CarrierRule;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * The characteristics of an imported order that carrier selection rules can test
 */
class OrderCriteria
{
    /** @var string */
    protected $marketplace;

    /** @var string ISO code of the shipping country */
    protected $country;

    /** @var string Marketplace delivery mode (shipment carrier) */
    protected $deliveryMode;

    /** @var float In PS_WEIGHT_UNIT */
    protected $weight;

    /**
     * @param string $marketplace
     * @param string $country
     * @param string $deliveryMode
     * @param float $weight
     */
    public function __construct($marketplace, $country, $deliveryMode, $weight)
    {
        $this->marketplace = (string) $marketplace;
        $this->country = (string) $country;
        $this->deliveryMode = (string) $deliveryMode;
        $this->weight = (float) $weight;
    }

    /**
     * @return string
     */
    public function getMarketplace()
    {
        return $this->marketplace;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getDeliveryMode()
    {
        return $this->deliveryMode;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param string $criterion one of the ConditionEvaluator::CRITERION_* constants
     *
     * @return string|float|null null for an unknown criterion
     */
    public function getValue($criterion)
    {
        switch ($criterion) {
            case ConditionEvaluator::CRITERION_MARKETPLACE:
                return $this->marketplace;
            case ConditionEvaluator::CRITERION_COUNTRY:
                return $this->country;
            case ConditionEvaluator::CRITERION_DELIVERY_MODE:
                return $this->deliveryMode;
            case ConditionEvaluator::CRITERION_WEIGHT:
                return $this->weight;
            default:
                return null;
        }
    }
}
