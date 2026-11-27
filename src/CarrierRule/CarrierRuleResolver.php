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
 * Finds the carrier selection rule that applies to an order
 */
class CarrierRuleResolver
{
    /** @var ConditionEvaluator */
    protected $evaluator;

    /** @var \ShoppingfeedCarrierRule[] Matching rules whose carrier could not be loaded */
    protected $skippedRules = [];

    public function __construct(ConditionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * Evaluates the rules of the token in order; the first matching rule
     * whose carrier exists wins.
     *
     * @param int $idToken
     * @param OrderCriteria $criteria
     *
     * @return array|null ['rule' => \ShoppingfeedCarrierRule, 'carrier' => \Carrier, 'number' => int (1-based, as displayed to the merchant)]
     */
    public function resolve($idToken, OrderCriteria $criteria)
    {
        $this->skippedRules = [];

        foreach (\ShoppingfeedCarrierRule::getByToken((int) $idToken) as $index => $rule) {
            if (!$this->evaluator->matches($rule->getConditions(), $criteria)) {
                continue;
            }

            $carrier = \Carrier::getCarrierByReference((int) $rule->id_carrier_reference);
            if (!\Validate::isLoadedObject($carrier)) {
                $this->skippedRules[] = $rule;
                continue;
            }

            return [
                'rule' => $rule,
                'carrier' => $carrier,
                'number' => $index + 1,
            ];
        }

        return null;
    }

    /**
     * @return \ShoppingfeedCarrierRule[] matching rules skipped by the last resolve() because their carrier does not exist
     */
    public function getSkippedRules()
    {
        return $this->skippedRules;
    }
}
