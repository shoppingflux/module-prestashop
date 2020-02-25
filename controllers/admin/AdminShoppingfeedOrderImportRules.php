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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/shoppingfeed.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');

use ShoppingfeedAddon\OrderImport\RulesManager;

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedOrderImportRulesController extends ModuleAdminController
{
    public $bootstrap = true;
    
    /** @var ShoppingfeedOrderImportSpecificRulesManager $specificRulesManager */
    protected $specificRulesManager;

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $this->specificRulesManager = new RulesManager($this->context->shop->id);
        $this->content = $this->renderRulesConfigurationForm();
        $this->content .= $this->renderRulesList();
        
        $this->module->setBreakingChangesNotices();
        
        parent::initContent();
    }
    
    public function renderRulesConfigurationForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Rules Configuration', 'AdminShoppingfeedOrderImportRules'),
                'icon' => 'icon-user'
            ),
            'input' => array(),
            'submit' => array(
                'title' => $this->l('Save', 'AdminShoppingfeedOrderImportRules'),
                'name' => 'saveRulesConfiguration',
                // PS hides the button if this is not set
                'id' => 'shoppingfeed_saveRulesConfiguration-submit'
            )
        );
        
        $rulesInformation = $this->specificRulesManager->getRulesInformation();
        if (empty($rulesInformation)) {
            return '';
        }
        
        $fields_value = array();
        foreach ($rulesInformation as $ruleInformation) {
            if (empty($ruleInformation['configurationSubform'])) {
                continue;
            }
            
            $ruleConfiguration = $ruleInformation['configuration'];
            foreach ($ruleInformation['configurationSubform'] as &$field) {
                $fieldName = 'rulesConfiguration[' .
                    $ruleInformation['className'] .
                    '][' .
                    $field['name'] .
                    ']';
                
                if (is_array($ruleConfiguration) && isset($ruleConfiguration[$field['name']])) {
                    $fields_value[$fieldName] = $ruleConfiguration[$field['name']];
                } else {
                    $fields_value[$fieldName] = null;
                }
                
                $field['name'] = $fieldName;
            }
            
            $fields_form['input'] = array_merge($ruleInformation['configurationSubform']);
        }
        
        if (empty($fields_form['input'])) {
            return '';
        }

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->submit_action = 'saveRulesConfiguration';
        $helper->fields_value = $fields_value;

        return $helper->generateForm(array(array('form' => $fields_form)));
    }
    
    public function renderRulesList()
    {
        $rulesInformation = $this->specificRulesManager->getRulesInformation();
        if (empty($rulesInformation)) {
            return '';
        }
        
        $fieldsList = array(
            'className' => array(
                'title' => $this->module->l('Class name', 'AdminShoppingfeedOrderImportRules'),
            ),
            'conditions' => array(
                'title' => $this->module->l('Conditions', 'AdminShoppingfeedOrderImportRules'),
            ),
            'description' => array(
                'title' => $this->module->l('Description', 'AdminShoppingfeedOrderImportRules'),
            ),
        );
        
        $helper = new HelperList();
        $this->setHelperDisplay($helper);
        $helper->listTotal = count($rulesInformation);

        return $helper->generateList($rulesInformation, $fieldsList);
    }

    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveRulesConfiguration')) {
            $this->saveRulesConfiguration();
        }
    }
    
    public function saveRulesConfiguration()
    {
        $rulesConfiguration = Tools::getValue('rulesConfiguration');
        Configuration::updateValue(
            ShoppingFeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
            Tools::jsonEncode($rulesConfiguration),
            false,
            null,
            $this->context->shop->id
        );
    }
}
