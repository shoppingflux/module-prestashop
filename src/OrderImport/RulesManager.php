<?php
/**
 *
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
 *
 */

namespace ShoppingfeedAddon\OrderImport;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Tools;
use Hook;
use Configuration;

use ShoppingFeed\Sdk\Api\Order\OrderResource;

/**
 * This class will manage a list of specific rules, and the execution of hooks
 * during the order import process
 */
class RulesManager
{

    /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
    protected $apiOrder;

    /** @var array $rules The rules to be applied */
    protected $rules = array();

    /** @var array $rulesConfiguration The rules configuration */
    protected $rulesConfiguration = array();

    /**
     * If no OrderResource is specified, the manager will retrieve all rules but
     * never execute them.
     *
     * @param OrderResource $apiOrder
     */
    public function __construct($id_shop, OrderResource $apiOrder = null)
    {
        $this->apiOrder = $apiOrder;
        $this->rulesConfiguration = Tools::jsonDecode(
            Configuration::get(
                \ShoppingFeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
                null,
                null,
                $id_shop
            ),
            true
        );

        $rulesClassNames = array();

        Hook::exec(
            'actionShoppingfeedOrderImportRegisterSpecificRules',
            array(
                'specificRulesClassNames' => &$rulesClassNames
            )
        );

        foreach ($rulesClassNames as $ruleClassName) {
            $this->addRule(
                new $ruleClassName(
                isset($this->rulesConfiguration[$ruleClassName]) ? $this->rulesConfiguration[$ruleClassName] : array()
            )
            );
        }
    }

    /**
     * Adds a rule to the manager. If an OrderResource was given, checks if the
     * rule matches the order.
     *
     * @param \ShoppingfeedAddon\OrderImport\RuleInterface $ruleObject
     * @return boolean
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
        ];
        if (in_array($eventName, $availableEvents) === false) {
            return;
        }

        foreach ($this->rules as $rule) {
            if (is_callable(array($rule, $eventName))) {
                $rule->{$eventName}($params);
            }
        }
    }

    public function getRulesInformation()
    {
        $rulesInformation = array();
        foreach ($this->rules as $rule) {
            $rulesInformation[] = array(
                'className' => get_class($rule),
                'conditions' => $rule->getConditions(),
                'description' => $rule->getDescription(),
                'configurationSubform' => $rule->getConfigurationSubform(),
                'configuration' => $rule->getConfiguration(),
            );
        }

        return $rulesInformation;
    }
}
