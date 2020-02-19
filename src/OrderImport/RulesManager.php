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

use ShoppingFeed\Sdk\Api\Order\OrderResource;

/**
 * This class will manage a list of specific rules, and the execution of hooks
 * during the process
 */
class RulesManager {
    
    /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
    protected $apiOrder;
    
    /** @var array $rules The rules to be applied */
    protected $rules = array();
    
    public function __construct(OrderResource $apiOrder)
    {
        $this->apiOrder = $apiOrder;
        
        $rulesClassNames = $this->getDefaultRulesClasses();
        
        Hook::exec(
            'actionShoppingfeedOrderImportRegisterSpecificRules',
            array(
                'specificRulesClassNames' => &$rulesClassNames
            )
        );
        
        foreach($rulesClassNames as $ruleClassName) {
            $this->addRule(new $ruleClassName());
        }
    }
    
    public function addRule(RuleInterface $ruleObject)
    {
        if ($ruleObject->isApplicable($this->apiOrder)) {
            $this->rules[get_class($ruleObject)] = $ruleObject;
            return true;
        }
        return false;
    }
    
    protected function getDefaultRulesClasses()
    {
        $defaultRulesPath = _PS_MODULE_DIR_ . 'shoppingfeed/src/OrderImport/Rules';
        $files = scandir($defaultRulesPath);
        $classNames = array();
        
        foreach($files as $filename) {
            if (Tools::strpos($filename, '.') === 0) {
                continue;
            }
            
            $className = "ShoppingfeedAddon\OrderImport\Rules\\" . substr($filename, 0, strrpos($filename, "."));
            if (class_exists($className)) {
                $classNames[] = $className;
            }
        }
        
        return $classNames;
    }
    
    /**
     * Applies all rules for a given event. If a rule should stop the process,
     * an exception should be thrown.
     * 
     * @param string $eventName
     * @param array $params
     */
    public function applyRules($eventName, $params)
    {
        foreach ($this->rules as $rule) {
            if (is_callable(array($rule, $eventName))) {
                $rule->{$eventName}($params);
            }
        }
    }
    
}
