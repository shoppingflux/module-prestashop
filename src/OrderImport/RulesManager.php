<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommence
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommence is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommence
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

namespace ShoppingfeedAddon\OrderImport;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;
use Hook;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use Tools;

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
     * @param OrderResource $apiOrder
     */
    public function __construct($id_shop, OrderResource $apiOrder = null)
    {
        $this->apiOrder = $apiOrder;
        $this->rulesConfiguration = Tools::jsonDecode(
            Configuration::get(
                \Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
                null,
                null,
                $id_shop
            ),
            true
        );

        $rulesClassNames = [];

        Hook::exec(
            'actionShoppingfeedOrderImportRegisterSpecificRules',
            [
                'specificRulesClassNames' => &$rulesClassNames,
            ]
        );

        foreach ($rulesClassNames as $ruleClassName) {
            $this->addRule(
                new $ruleClassName(
                isset($this->rulesConfiguration[$ruleClassName]) ? $this->rulesConfiguration[$ruleClassName] : []
            )
            );
        }
    }

    /**
     * Adds a rule to the manager. If an OrderResource was given, checks if the
     * rule matches the order.
     *
     * @param \ShoppingfeedAddon\OrderImport\RuleInterface $ruleObject
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
