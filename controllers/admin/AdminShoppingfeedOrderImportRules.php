<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedAddon\OrderImport\RulesManager;
use ShoppingfeedAddon\OrderImport\SinceDate;

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedOrderImportRulesController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    /** @var ShoppingfeedOrderImportSpecificRulesManager $specificRulesManager */
    protected $specificRulesManager;

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $this->addCSS(array(
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css'
        ));

        $this->addJS($this->module->getPathUri() . 'views/js/form_config.js');
        $this->content = $this->welcomeForm();
        $id_shop = $this->context->shop->id;

        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        if (empty($tokens)) {
            Tools::redirectAdmin(
                Context::getContext()->link->getAdminLink('AdminShoppingfeedAccountSettings')
            );
        }
        $order_sync_available = ShoppingFeed::isOrderSyncAvailable();
        $order_import_available = ShoppingFeed::isOrderImportAvailable();
        $order_import_test = Configuration::get(Shoppingfeed::ORDER_IMPORT_TEST);
        $order_import_shipped = Configuration::get(Shoppingfeed::ORDER_IMPORT_SHIPPED);

        $this->content .= $this->renderOrderSyncForm($order_sync_available, $order_import_available, $order_import_test, $order_import_shipped);

        $this->specificRulesManager = new RulesManager($this->context->shop->id);
        $this->content .= $this->renderRulesConfigurationForm();
        $this->content .= $this->renderRulesList();

        parent::initContent();
    }

    public function renderRulesConfigurationForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Rules Configuration', 'AdminShoppingfeedOrderImportRules'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedOrderImportRules'),
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

                $fields_form['input'][] = $field;
            }
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
                'search' => false,
            ),
            'conditions' => array(
                'title' => $this->module->l('Conditions', 'AdminShoppingfeedOrderImportRules'),
                'search' => false,
            ),
            'description' => array(
                'title' => $this->module->l('Description', 'AdminShoppingfeedOrderImportRules'),
                'search' => false,
            ),
        );

        $helper = new HelperList();
        $helper->title = 'Helper List';
        $helper->toolbar_btn = [];
        $this->setHelperDisplay($helper);
        $helper->module = $this->module;
        $helper->listTotal = count($rulesInformation);

        return $helper->generateList($rulesInformation, $fieldsList);
    }

    /**
     * @return string
     */
    public function renderOrderSyncForm($order_sync_available, $order_import_available, $order_import_test = false, $order_import_shipped = false)
    {
        $cronLink = $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor');

        $allState = OrderState::getOrderStates($this->context->language->id);

        $orderShippedState = array();
        $orderCancelledState = array();
        $orderRefundedState = array();

        $ids_shipped_status_selected = json_decode(Configuration::get(ShoppingFeed::SHIPPED_ORDERS));
        $ids_cancelled_status_selected = json_decode(Configuration::get(ShoppingFeed::CANCELLED_ORDERS));

        $ids_refunded_status_selected = json_decode(Configuration::get(ShoppingFeed::REFUNDED_ORDERS));
        if (!is_array($ids_refunded_status_selected)) {
            $ids_refunded_status_selected = [$ids_refunded_status_selected];
        }

        $orderShippedState['selected'] = array();
        $orderCancelledState['selected'] = array();
        $orderRefundedState['selected'] = array();
        $orderShippedState['unselected'] = array();
        $orderCancelledState['unselected'] = array();
        $orderRefundedState['unselected'] = array();

        foreach ($allState as $state) {
            $orderShippedState[in_array($state['id_order_state'], $ids_shipped_status_selected) ? 'selected' : 'unselected'][] = array(
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            );

            $orderCancelledState[in_array($state['id_order_state'], $ids_cancelled_status_selected) ? 'selected' : 'unselected'][] = array(
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            );

            $orderRefundedState[in_array($state['id_order_state'], $ids_refunded_status_selected) ? 'selected' : 'unselected'][] = array(
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            );
        }

        $sfCarriers = ShoppingfeedCarrier::getAllCarriers();

        $fields_form = array(
            'form' => array(
                'form' => array(
                    'id_form' => 'order-sync-form',
                    'legend' => array(
                        'title' => $this->module->l('Orders import and synchronization settings (All shop)', 'AdminShoppingfeedOrderImportRules'),
                    ),
                    'input' => array(
                        array(
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'condition' => !$order_import_available,
                            'message' => $this->module->l('The Shopping Feed module (shoppingfluxexport) is installed on your shop for enabling the orders import synchronization. The “Order importation” option must be disabled in the module for enabling this type of synchronization in this module. If you disable the options in the shoppingfluxexport\'s module and you enable it again later the button "New orders import" will be disabled automatically in the Shopping feed 15 min module.', 'AdminShoppingfeedOrderImportRules')
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->module->l('New orders import', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_ENABLED,
                            'id' => 'shoppingfeed_order-import-switch',
                            'is_bool' => true,
                            'disabled' => !$order_import_available,
                            'values' => array(
                                array(
                                    'id' => 'ok',
                                    'value' => 1,
                                ),
                                array(
                                    'id' => 'ko',
                                    'value' => 0,
                                )
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->module->l('Allow import testing order', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_TEST,
                            'id' => 'shoppingfeed_order-import-switch',
                            'is_bool' => true,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => array(
                                array(
                                    'value' => 1,
                                ),
                                array(
                                    'value' => 0,
                                )
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->module->l('Import orders already in "shipped" status on Shopping Feed, except orders shipped by market places', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_SHIPPED,
                            'id' => 'shoppingfeed_order-import-switch',
                            'desc' => $this->module->l('Let\'s import order with status ”shipped” order on Shopping feed. Your stock won\'t decrease for these orders.', 'AdminShoppingfeedOrderImportRules'),
                            'hint' => $this->module->l('By default, a ”shipped” order will be imported as ”delivered” on PrestaShop. This can be configured.', 'AdminShoppingfeedOrderImportRules'),
                            'is_bool' => true,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => array(
                                array(
                                    'id' => 'ok',
                                    'value' => 1,
                                ),
                                array(
                                    'id' => 'ko',
                                    'value' => 0,
                                )
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->module->l('Allow import orders shipped by Marketplaces Amazon, CDiscount and Manomano', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE,
                            'id' => 'shoppingfeed_order-import-switch',
                            'hint' => $this->module->l('Order will be imported regardless of its status on Shopping Feed side', 'AdminShoppingfeedOrderImportRules'),
                            'is_bool' => true,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => array(
                                array(
                                    'value' => 1,
                                ),
                                array(
                                    'value' => 0,
                                )
                            ),
                        ),
                        array(
                            'type' => 'shoppingfeed_open-section',
                            'id' => 'shoppingfeed_carriers-matching',
                            'title' => $this->module->l('Carriers matching', 'AdminShoppingfeedOrderImportRules'),
                            'desc' => $this->module->l('Match carriers from marketplaces to your Prestashop\'s carriers', 'AdminShoppingfeedOrderImportRules'),
                        ),
                        array(
                            'type' => 'shoppingfeed_carrier-matching',
                            'marketplace_filter_options' => array_map(
                                function ($m) {
                                    return array(
                                        'value' => $m,
                                        'label' => $m,
                                    );
                                },
                                ShoppingfeedCarrier::getAllMarketplaces()
                            ),
                            'default_carrier_field_name' => ShoppingFeed::ORDER_DEFAULT_CARRIER_REFERENCE,
                            'carriers_matching_field' => array(
                                'name' => 'shoppingfeed_carrier_matching',
                                'labels' => array_map(
                                    function ($c) {
                                        return str_replace(' ', '_', $c->name_marketplace) .
                                            '_' .
                                            str_replace(' ', '_', $c->name_carrier);
                                    },
                                    $sfCarriers
                                ),
                            ),
                            'carriers' => array_map(
                                function ($c) {
                                    return array(
                                        'value' => $c['id_reference'],
                                        'label' => $c['name'],
                                    );
                                },
                                Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS)
                            ),
                            'shoppingfeed_carriers' => $sfCarriers,
                        ),
                        array(
                            'type' => 'shoppingfeed_close-section',
                        ),
                        array(
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'condition' => !$order_sync_available,
                            'message' => $this->module->l('The Shopping Feed Official module (shoppingfluxexport) should be installed on your shop for enabling the post-import synchronization. The “Order shipment” & “Order cancellation” options must be disabled in the official module for enabling this type of synchronization in the new module. If you disable these options in the official module and you enable them again later the “Orders post-import synchronization” will be disabled automatically in the Shopping feed 15 min module.', 'AdminShoppingfeedOrderImportRules')
                        ),
                        array(
                            'type' => 'switch',
                            'is_bool' => true,
                            'disabled' => !$order_sync_available,
                            'hint' => $this->module->l('The order post-import synchronization allows you to manage the following order statuses : shipped, cancelled, refunded.', 'AdminShoppingfeedOrderImportRules'),
                            'values' => array(
                                array(
                                    'id' => 'ok',
                                    'value' => 1,
                                ),
                                array(
                                    'id' => 'ko',
                                    'value' => 0,
                                )
                            ),
                            'label' => $this->module->l('Orders post-import synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_SYNC_ENABLED,
                        ),
                        array(
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'info',
                            'message' => sprintf(
                                $this->module->l('You should set the frequency of synchronization via a %s Cron job %s for updating your orders status', 'AdminShoppingfeedOrderImportRules'),
                                '<a href="' . $cronLink . '">',
                                '</a>'
                            )
                        ),
                        array(
                            'type' => 'shoppingfeed_open-section',
                            'id' => 'shoppingfeed_orders-status',
                        ),
                        array(
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_shipped_order',
                            'label' => $this->module->l('Shipped orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => array(
                                'id' => 'status_shipped_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderShippedState['unselected'],
                                'btn' => array(
                                    'id' => 'status_shipped_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                            'selected' => array(
                                'id' => 'status_shipped_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderShippedState['selected'],
                                'btn' => array(
                                    'id' => 'status_shipped_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->module->l('Time shift for tracking numbers synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'name' => 'tracking_timeshift',
                            'hint' => $this->module->l('In some cases, the tracking number can be sent to your shop after the order status update. For being sure and always sending the tracking numbers to the marketplaces you can set a shift time (in minutes). By default, the sending of the tracking number will be delayed by 5 minutes. Please note that the synchronization will be done after x minutes of the Time shift by the next Cron task.', 'AdminShoppingfeedOrderImportRules'),
                            'suffix' => $this->module->l('minutes', 'AdminShoppingfeedOrderImportRules'),
                            'class' => 'col-lg-6',
                        ),
                        array(
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_cancelled_order',
                            'label' => $this->module->l('Cancelled orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => array(
                                'id' => 'status_cancelled_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderCancelledState['unselected'],
                                'btn' => array(
                                    'id' => 'status_cancelled_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                            'selected' => array(
                                'id' => 'status_cancelled_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderCancelledState['selected'],
                                'btn' => array(
                                    'id' => 'status_cancelled_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_refunded_order',
                            'label' => $this->module->l('Refunded orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => array(
                                'id' => 'status_refunded_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderRefundedState['unselected'],
                                'btn' => array(
                                    'id' => 'status_refunded_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                            'selected' => array(
                                'id' => 'status_refunded_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderRefundedState['selected'],
                                'btn' => array(
                                    'id' => 'status_refunded_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'message' => $this->module->l('The Max order update parameter is reserved for experts (100 by default). You can configure the number of orders to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.', 'AdminShoppingfeedOrderImportRules')
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->module->l('Max. order update per request', 'AdminShoppingfeedOrderImportRules'),
                            'name' => 'max_order_update',
                            'class' => 'number_require',
                        ),
                        array(
                            'type' => 'date',
                            'label' => $this->module->l('Import the orders since', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE,
                        ),
                        array(
                            'type' => 'shoppingfeed_close-section',
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->module->l('Save', 'AdminShoppingfeedOrderImportRules'),
                        'name' => 'saveOrdersConfig'
                    ),
                ),
            ),
        );

        $helper = new HelperForm($this);
        $helper->fields_value = array(
            Shoppingfeed::ORDER_IMPORT_ENABLED => !$order_import_available ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
            Shoppingfeed::ORDER_IMPORT_TEST => !$order_import_test ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_TEST),
            Shoppingfeed::ORDER_IMPORT_SHIPPED => !$order_import_shipped ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_SHIPPED),
            Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE => !$order_import_shipped ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE),
            Shoppingfeed::ORDER_SYNC_ENABLED => !$order_sync_available ? false : Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED),
            Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE => Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE),
            'tracking_timeshift' => Configuration::get(Shoppingfeed::ORDER_STATUS_TIME_SHIFT),
            'max_order_update' => Configuration::get(Shoppingfeed::ORDER_STATUS_MAX_ORDERS),
            Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE => $this->getSinceDateService()->get()
        );

        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'order_status_syncro.tpl';

        return $helper->generateForm($fields_form);
    }

    public function welcomeForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedOrderImportRules'),
            )
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . "views/img/";
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'welcome.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveRulesConfiguration')) {
            $this->saveRulesConfiguration();
        } elseif (Tools::isSubmit('saveOrdersConfig')) {
            return $this->saveOrdersConfig();
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
    /**
     * Save the post-import for the module
     * @return bool
     */
    public function saveOrdersConfig()
    {
        $order_import_enabled = Tools::getValue(Shoppingfeed::ORDER_IMPORT_ENABLED);
        $order_sync_enabled = Tools::getValue(Shoppingfeed::ORDER_SYNC_ENABLED);
        $order_sync_test = Tools::getValue(Shoppingfeed::ORDER_IMPORT_TEST);
        $order_sync_shipped = Tools::getValue(Shoppingfeed::ORDER_IMPORT_SHIPPED);

        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_ENABLED, ($order_import_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_TEST, ($order_sync_test ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_SYNC_ENABLED, ($order_sync_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_SHIPPED, ($order_sync_shipped ? true : false), false, null, $shop['id_shop']);
        }

        $orderStatusesShipped = Tools::getValue('status_shipped_order');
        if (!$orderStatusesShipped) {
            $orderStatusesShipped = array();
        }

        $orderStatusesCancelled = Tools::getValue('status_cancelled_order');
        if (!$orderStatusesCancelled) {
            $orderStatusesCancelled = array();
        }

        $orderStatusRefunded = Tools::getValue('status_refunded_order');
        if (!$orderStatusRefunded) {
            $orderStatusRefunded = array();
        }

        $tracking_timeshift = Tools::getValue('tracking_timeshift');
        $max_orders = Tools::getValue('max_order_update');

        if (!is_numeric($tracking_timeshift) || (int)$tracking_timeshift <= 0) {
            $this->errors[] = $this->module->l('You must specify a valid \'Time shift\' number (greater than 0).', 'AdminShoppingfeedOrderImportRules');
        } elseif (!is_numeric($max_orders) || $max_orders > 200 || $max_orders <= 0) {
            $this->errors[] = $this->module->l('You must specify a valid \'Max Order update\' number (between 1 and 200 included).', 'AdminShoppingfeedOrderImportRules');
        } else {
            Configuration::updateValue(Shoppingfeed::SHIPPED_ORDERS, json_encode($orderStatusesShipped));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_TIME_SHIFT, (int)$tracking_timeshift);
            Configuration::updateValue(Shoppingfeed::CANCELLED_ORDERS, json_encode($orderStatusesCancelled));
            Configuration::updateValue(Shoppingfeed::REFUNDED_ORDERS, json_encode($orderStatusRefunded));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_MAX_ORDERS, $max_orders);
            Configuration::updateValue(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE, Tools::getValue(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }

        // Update carriers matching
        $carriersMatching = Tools::getValue('shoppingfeed_carrier_matching');
        if ($carriersMatching) {
            foreach ($carriersMatching as $id_shoppingfeed_carrier => $id_carrier_reference) {
                $sfCarrier = new ShoppingfeedCarrier($id_shoppingfeed_carrier);
                if (Validate::isLoadedObject($sfCarrier)) {
                    $sfCarrier->id_carrier_reference = (int)$id_carrier_reference;
                    $sfCarrier->is_new = 0;
                    $sfCarrier->update();
                }
            }
        }

        if (Tools::isSubmit(Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE)) {
            $sinceDate = DateTime::createFromFormat(
                SinceDate::DATE_FORMAT_PS,
                Tools::getValue(Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE)
            );

            if ($sinceDate instanceof DateTime) {
                $this->getSinceDateService()->set($sinceDate);
            }
        }

        return true;
    }

    protected function getSinceDateService()
    {
        return new SinceDate();
    }
}
