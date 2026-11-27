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
 * Carrier chosen for an imported order, with what the caller needs to log it
 */
class CarrierSelection
{
    /** @var \Carrier|false|null */
    protected $carrier;

    /** @var bool */
    protected $rulesEngine;

    /** @var int|null */
    protected $ruleNumber;

    /** @var \ShoppingfeedCarrierRule[] */
    protected $skippedRules;

    /**
     * @param \Carrier|false|null $carrier
     * @param bool $rulesEngine true when the carrier selection rules engine was used
     * @param int|null $ruleNumber 1-based number of the applied rule, null when no rule applied
     * @param \ShoppingfeedCarrierRule[] $skippedRules matching rules whose carrier no longer exists
     */
    public function __construct($carrier, $rulesEngine, $ruleNumber = null, array $skippedRules = [])
    {
        $this->carrier = $carrier;
        $this->rulesEngine = (bool) $rulesEngine;
        $this->ruleNumber = $ruleNumber === null ? null : (int) $ruleNumber;
        $this->skippedRules = $skippedRules;
    }

    /**
     * @return \Carrier|false|null
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @return bool
     */
    public function isRulesEngine()
    {
        return $this->rulesEngine;
    }

    /**
     * @return int|null
     */
    public function getRuleNumber()
    {
        return $this->ruleNumber;
    }

    /**
     * @return \ShoppingfeedCarrierRule[]
     */
    public function getSkippedRules()
    {
        return $this->skippedRules;
    }
}
