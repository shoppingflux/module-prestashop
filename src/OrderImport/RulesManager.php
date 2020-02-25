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
