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

use Configuration;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

/**
 * This class will manage a list of specific rules, and the execution of hooks
 * during the order import process
 */
class RulesManager
{
    /** @var ShoppingFeed\Sdk\Api\Order\OrderResource */
    protected $apiOrder;

    /** @var array The rules to be applied */
    protected $rules = [];

    /** @var array The rules configuration */
    protected $rulesConfiguration = [];

    /**
     * If no OrderResource is specified, the manager will retrieve all rules but
     * never execute them.
     *
     * @param int $id_shop
     * @param ?OrderResource $apiOrder
     */
    public function __construct($id_shop, ?OrderResource $apiOrder = null)
    {
        $this->apiOrder = $apiOrder;
        $this->rulesConfiguration = json_decode(
            \Configuration::get(
                \Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
                null,
                null,
                $id_shop
            ),
            true
        );

        $rulesClassNames = [];

        \Hook::exec(
            'actionShoppingfeedOrderImportRegisterSpecificRules',
            [
                'specificRulesClassNames' => &$rulesClassNames,
            ]
        );

        foreach ($rulesClassNames as $ruleClassName) {
            $this->addRule(
                new $ruleClassName(
                    isset($this->rulesConfiguration[$ruleClassName]) ? $this->rulesConfiguration[$ruleClassName] : [],
                    $id_shop
                )
            );
        }
    }

    /**
     * Adds a rule to the manager. If an OrderResource was given, checks if the
     * rule matches the order.
     *
     * @param RuleInterface $ruleObject
     *
     * @return bool
     */
    protected function addRule(RuleAbstract $ruleObject)
    {
        if (!$this->apiOrder || $ruleObject->isApplicable($this->apiOrder)) {
            $this->rules[get_class($ruleObject)] = $ruleObject;

            return true;
        }

        return false;
    }

    /**
     * Applies all rules for a given event. If a rule should stop the process,
     * an exception should be thrown. No rules will be applied if no
     * OrderResource was given.
     *
     * @param string $eventName
     * @param array $params
     */
    public function applyRules($eventName, $params)
    {
        if (!$this->apiOrder) {
            return;
        }
        $availableEvents = [
            'onPreProcess',
            'onCarrierRetrieval',
            'onVerifyOrder',
            'onCustomerCreation',
            'onCustomerRetrieval',
            'beforeBillingAddressCreation',
            'beforeBillingAddressSave',
            'beforeShippingAddressCreation',
            'beforeShippingAddressSave',
            'checkProductStock',
            'onCartCreation',
            'afterCartCreation',
            'afterOrderCreation',
            'onPostProcess',
            'beforeRecalculateOrderPrices',
            'afterRecalculateOrderPrices',
            'onValidateOrder',
        ];
        if (in_array($eventName, $availableEvents) === false) {
            return;
        }

        foreach ($this->rules as $rule) {
            if (is_callable([$rule, $eventName])) {
                $rule->{$eventName}($params);
            }
        }
    }

    public function getRulesInformation()
    {
        $rulesInformation = [];
        foreach ($this->rules as $rule) {
            $rulesInformation[] = [
                'className' => get_class($rule),
                'conditions' => $rule->getConditions(),
                'description' => $rule->getDescription(),
                'configurationSubform' => $rule->getConfigurationSubform(),
                'configuration' => $rule->getConfiguration(),
            ];
        }

        return $rulesInformation;
    }
}
