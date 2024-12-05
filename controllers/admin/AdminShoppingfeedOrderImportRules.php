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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedAddon\OrderImport\RulesManager;
use ShoppingfeedAddon\OrderImport\SFOrderState;
use ShoppingfeedAddon\OrderImport\SinceDate;

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedOrderImportRulesController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    public $override_folder;

    /** @var ShoppingfeedOrderImportSpecificRulesManager */
    protected $specificRulesManager;

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css',
        ]);

        $this->addJS($this->module->getPathUri() . 'views/js/form_config.js');
        $this->content = $this->welcomeForm();
        $id_shop = $this->context->shop->id;
        $this->identifier = 'className';

        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        if (empty($tokens)) {
            Tools::redirectAdmin(
                Context::getContext()->link->getAdminLink('AdminShoppingfeedAccountSettings')
            );
        }
        $order_sync_available = Shoppingfeed::isOrderSyncAvailable();
        $order_import_available = Shoppingfeed::isOrderImportAvailable();
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
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Rules Configuration', 'AdminShoppingfeedOrderImportRules'),
                'icon' => 'icon-cogs',
            ],
            'input' => [],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminShoppingfeedOrderImportRules'),
                'name' => 'saveRulesConfiguration',
                // PS hides the button if this is not set
                'id' => 'shoppingfeed_saveRulesConfiguration-submit',
            ],
        ];

        $rulesInformation = $this->specificRulesManager->getRulesInformation();
        if (empty($rulesInformation)) {
            return '';
        }

        $fields_value = [];
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

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->submit_action = 'saveRulesConfiguration';
        $helper->fields_value = $fields_value;

        return $helper->generateForm([['form' => $fields_form]]);
    }

    public function renderRulesList()
    {
        $rulesInformation = $this->specificRulesManager->getRulesInformation();
        if (empty($rulesInformation)) {
            return '';
        }

        $fieldsList = [
            'className' => [
                'title' => $this->module->l('Class name', 'AdminShoppingfeedOrderImportRules'),
                'search' => false,
            ],
            'conditions' => [
                'title' => $this->module->l('Conditions', 'AdminShoppingfeedOrderImportRules'),
                'search' => false,
            ],
            'description' => [
                'title' => $this->module->l('Description', 'AdminShoppingfeedOrderImportRules'),
                'search' => false,
            ],
        ];

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

        $orderShippedState = [];
        $orderDeliveredState = [];
        $orderCancelledState = [];
        $orderRefundedState = [];

        $ids_shipped_status_selected = json_decode(Configuration::get(Shoppingfeed::SHIPPED_ORDERS));
        $ids_cancelled_status_selected = json_decode(Configuration::get(Shoppingfeed::CANCELLED_ORDERS));
        $ids_refunded_status_selected = json_decode(Configuration::get(Shoppingfeed::REFUNDED_ORDERS));
        $ids_delivered_status_selected = json_decode(Configuration::get(Shoppingfeed::DELIVERED_ORDERS));

        if (!is_array($ids_refunded_status_selected)) {
            $ids_refunded_status_selected = [$ids_refunded_status_selected];
        }
        if (!is_array($ids_delivered_status_selected)) {
            $ids_delivered_status_selected = [];
        }

        $orderShippedState['selected'] = [];
        $orderCancelledState['selected'] = [];
        $orderRefundedState['selected'] = [];
        $orderDeliveredState['selected'] = [];
        $orderShippedState['unselected'] = [];
        $orderCancelledState['unselected'] = [];
        $orderRefundedState['unselected'] = [];
        $orderDeliveredState['unselected'] = [];

        foreach ($allState as $state) {
            $orderShippedState[in_array($state['id_order_state'], $ids_shipped_status_selected) ? 'selected' : 'unselected'][] = [
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            ];

            $orderCancelledState[in_array($state['id_order_state'], $ids_cancelled_status_selected) ? 'selected' : 'unselected'][] = [
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            ];

            $orderRefundedState[in_array($state['id_order_state'], $ids_refunded_status_selected) ? 'selected' : 'unselected'][] = [
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            ];

            $orderDeliveredState[in_array($state['id_order_state'], $ids_delivered_status_selected) ? 'selected' : 'unselected'][] = [
                'value' => $state['id_order_state'],
                'label' => $state['name'],
            ];
        }

        $sfCarriers = ShoppingfeedCarrier::getAllCarriers();

        $fields_form = [
            'form' => [
                'form' => [
                    'id_form' => 'order-sync-form',
                    'legend' => [
                        'title' => $this->module->l('Orders import and synchronization settings (All shop)', 'AdminShoppingfeedOrderImportRules'),
                    ],
                    'input' => [
                        [
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'condition' => !$order_import_available,
                            'message' => $this->module->l('The Shopping Feed module (shoppingfluxexport) is installed on your shop for enabling the orders import synchronization. The “Order importation” option must be disabled in the module for enabling this type of synchronization in this module. If you disable the options in the shoppingfluxexport\'s module and you enable it again later the button "New orders import" will be disabled automatically in the Shopping feed 15 min module.', 'AdminShoppingfeedOrderImportRules'),
                        ],
                        [
                            'type' => 'shoppingfeed_switch_with_date',
                            'label' => $this->module->l('New orders import', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_ENABLED,
                            'date' => Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE,
                            'id' => 'shoppingfeed_order-import-switch',
                            'is_bool' => true,
                            'disabled' => !$order_import_available,
                            'values' => [
                                [
                                    'id' => 'ok',
                                    'value' => 1,
                                ],
                                [
                                    'id' => 'ko',
                                    'value' => 0,
                                ],
                            ],
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->module->l('Allow import testing order', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_TEST,
                            'id' => 'shoppingfeed_order-import-switch',
                            'is_bool' => true,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => [
                                [
                                    'id' => 'shoppingfeed_order-import-switch-1',
                                    'value' => 1,
                                ],
                                [
                                    'id' => 'shoppingfeed_order-import-switch-0',
                                    'value' => 0,
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_switch_with_date',
                            'label' => $this->module->l('Import orders already in \'shipped\' status on Shopping Feed, except orders shipped by market places', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_SHIPPED,
                            'id' => 'shoppingfeed_order-import-switch',
                            'desc' => $this->module->l('Let\'s import order with status ”shipped” order on Shopping feed. Your stock won\'t decrease for these orders.', 'AdminShoppingfeedOrderImportRules'),
                            'hint' => $this->module->l('By default, a ”shipped” order will be imported as ”delivered” on PrestaShop. This can be configured.', 'AdminShoppingfeedOrderImportRules'),
                            'is_bool' => true,
                            'date' => Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => [
                                [
                                    'id' => 'ok',
                                    'value' => 1,
                                ],
                                [
                                    'id' => 'ko',
                                    'value' => 0,
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_switch_with_date',
                            'label' => $this->module->l('Allow import orders shipped by Marketplaces', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE,
                            'id' => 'shoppingfeed_order-import-switch',
                            'hint' => $this->module->l('Order will be imported regardless of its status on Shopping Feed side', 'AdminShoppingfeedOrderImportRules'),
                            'is_bool' => true,
                            'date' => Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE,
                            'disabled' => !Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
                            'values' => [
                                [
                                    'id' => 'shoppingfeed_order-import-switch-1',
                                    'value' => 1,
                                ],
                                [
                                    'id' => 'shoppingfeed_order-import-switch-0',
                                    'value' => 0,
                                ],
                            ],
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->module->l('Order Tracking', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_TRACKING,
                            'id' => 'shoppingfeed_order-import-switch',
                            'hint' => $this->module->l('You\'ll be able to list orders from price comparators', 'AdminShoppingfeedOrderImportRules'),
                            'is_bool' => true,
                            'values' => [
                                [
                                    'id' => Shoppingfeed::ORDER_TRACKING . '-1',
                                    'value' => 1,
                                ],
                                [
                                    'id' => Shoppingfeed::ORDER_TRACKING . '-0',
                                    'value' => 0,
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_open-section',
                            'id' => 'shoppingfeed_carriers-matching',
                            'title' => $this->module->l('Carriers matching', 'AdminShoppingfeedOrderImportRules'),
                            'desc' => $this->module->l('Match carriers from marketplaces to your Prestashop\'s carriers', 'AdminShoppingfeedOrderImportRules'),
                        ],
                        [
                            'type' => 'shoppingfeed_carrier-matching',
                            'marketplace_filter_options' => array_map(
                                function ($m) {
                                    return [
                                        'value' => $m,
                                        'label' => $m,
                                    ];
                                },
                                ShoppingfeedCarrier::getAllMarketplaces()
                            ),
                            'default_carrier_field_name' => Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE,
                            'carriers_matching_field' => [
                                'name' => 'shoppingfeed_carrier_matching',
                                'labels' => array_map(
                                    function ($c) {
                                        return str_replace(' ', '_', $c->name_marketplace) .
                                            '_' .
                                            str_replace(' ', '_', $c->name_carrier);
                                    },
                                    $sfCarriers
                                ),
                            ],
                            'carriers' => $this->getAvailableCarriers(),
                            'shoppingfeed_carriers' => $sfCarriers,
                        ],
                        [
                            'type' => 'shoppingfeed_close-section',
                        ],
                        [
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'condition' => !$order_sync_available,
                            'message' => $this->module->l('The Shopping Feed Official module (shoppingfluxexport) should be installed on your shop for enabling the post-import synchronization. The “Order shipment” & “Order cancellation” options must be disabled in the official module for enabling this type of synchronization in the new module. If you disable these options in the official module and you enable them again later the “Orders post-import synchronization” will be disabled automatically in the Shopping feed 15 min module.', 'AdminShoppingfeedOrderImportRules'),
                        ],
                        [
                            'type' => 'switch',
                            'id' => Shoppingfeed::ORDER_SYNC_ENABLED . '-switch',
                            'is_bool' => true,
                            'disabled' => !$order_sync_available,
                            'hint' => $this->module->l('The order post-import synchronization allows you to manage the following order statuses : shipped, cancelled, refunded.', 'AdminShoppingfeedOrderImportRules'),
                            'values' => [
                                [
                                    'id' => 'ok',
                                    'value' => 1,
                                ],
                                [
                                    'id' => 'ko',
                                    'value' => 0,
                                ],
                            ],
                            'label' => $this->module->l('Orders post-import synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::ORDER_SYNC_ENABLED,
                        ],
                        [
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'info',
                            'message' => sprintf(
                                $this->module->l('You should set the frequency of synchronization via a %s Cron job %s for updating your orders status', 'AdminShoppingfeedOrderImportRules'),
                                '<a href="' . $cronLink . '">',
                                '</a>'
                            ),
                        ],
                        [
                            'type' => 'shoppingfeed_open-section',
                            'id' => 'shoppingfeed_orders-status',
                        ],
                        [
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_shipped_order',
                            'label' => $this->module->l('Shipped orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => [
                                'id' => 'status_shipped_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderShippedState['unselected'],
                                'btn' => [
                                    'id' => 'status_shipped_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                            'selected' => [
                                'id' => 'status_shipped_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderShippedState['selected'],
                                'btn' => [
                                    'id' => 'status_shipped_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->module->l('Time shift for tracking numbers synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'name' => 'tracking_timeshift',
                            'hint' => $this->module->l('In some cases, the tracking number can be sent to your shop after the order status update. For being sure and always sending the tracking numbers to the marketplaces you can set a shift time (in minutes). By default, the sending of the tracking number will be delayed by 5 minutes. Please note that the synchronization will be done after x minutes of the Time shift by the next Cron task.', 'AdminShoppingfeedOrderImportRules'),
                            'suffix' => $this->module->l('minutes', 'AdminShoppingfeedOrderImportRules'),
                            'class' => 'col-lg-6',
                        ],
                        [
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_delivered_order',
                            'label' => $this->module->l('Delivery orders synchronization + Status mapping', 'AdminShoppingfeedOrderImportRules'),
                            'hint' => $this->module->l('When the order has been delivered to the customer, for platforms managing this status', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => [
                                'id' => 'status_delivered_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderDeliveredState['unselected'],
                                'btn' => [
                                    'id' => 'status_delivered_order_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                            'selected' => [
                                'id' => 'status_delivered_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderDeliveredState['selected'],
                                'btn' => [
                                    'id' => 'status_delivered_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_cancelled_order',
                            'label' => $this->module->l('Cancelled orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => [
                                'id' => 'status_cancelled_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderCancelledState['unselected'],
                                'btn' => [
                                    'id' => 'status_cancelled_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                            'selected' => [
                                'id' => 'status_cancelled_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderCancelledState['selected'],
                                'btn' => [
                                    'id' => 'status_cancelled_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_double-list',
                            'name' => 'status_refunded_order',
                            'label' => $this->module->l('Refunded orders synchronization', 'AdminShoppingfeedOrderImportRules'),
                            'unselected' => [
                                'id' => 'status_refunded_order_add',
                                'label' => $this->module->l('Unselected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderRefundedState['unselected'],
                                'btn' => [
                                    'id' => 'status_refunded_order_add_btn',
                                    'label' => $this->module->l('Add', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                            'selected' => [
                                'id' => 'status_refunded_order_remove',
                                'label' => $this->module->l('Selected order status', 'AdminShoppingfeedOrderImportRules'),
                                'options' => $orderRefundedState['selected'],
                                'btn' => [
                                    'id' => 'status_refunded_order_remove_btn',
                                    'label' => $this->module->l('Remove', 'AdminShoppingfeedOrderImportRules'),
                                ],
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_alert',
                            'severity' => 'warning',
                            'message' => $this->module->l('The Max order update parameter is reserved for experts (100 by default). You can configure the number of orders to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.', 'AdminShoppingfeedOrderImportRules'),
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->module->l('Max. order update per request', 'AdminShoppingfeedOrderImportRules'),
                            'name' => 'max_order_update',
                            'class' => 'number_require',
                        ],
                        [
                            'type' => 'select',
                            'label' => $this->module->l('First order state after import', 'AdminShoppingfeedOrderImportRules'),
                            'name' => Shoppingfeed::IMPORT_ORDER_STATE,
                            'options' => [
                                'query' => OrderState::getOrderStates($this->context->language->id),
                                'id' => 'id_order_state',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'shoppingfeed_close-section',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->module->l('Save', 'AdminShoppingfeedOrderImportRules'),
                        'name' => 'saveOrdersConfig',
                        // PS hides the button if this is not set
                        'id' => 'shoppingfeed_saveOrderSync-submit',
                    ],
                ],
            ],
        ];

        if ($this->module->isUploadOrderDocumentReady()) {
            $fields_form['form']['form']['input'][] = [
                'type' => 'shoppingfeed_marketplace_switch_list',
                'marketplaces' => \ShoppingfeedAddon\OrderInvoiceSync\Hub::getInstance()->getMarketplaces(),
            ];
        }

        $helper = new HelperForm();
        $helper->fields_value = [
            Shoppingfeed::ORDER_IMPORT_ENABLED => !$order_import_available ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED),
            Shoppingfeed::ORDER_IMPORT_TEST => !$order_import_test ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_TEST),
            Shoppingfeed::ORDER_IMPORT_SHIPPED => !$order_import_shipped ? false : Configuration::get(Shoppingfeed::ORDER_IMPORT_SHIPPED),
            Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE => Configuration::get(Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE),
            Shoppingfeed::ORDER_SYNC_ENABLED => !$order_sync_available ? false : Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED),
            Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE => Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE),
            'tracking_timeshift' => Configuration::get(Shoppingfeed::ORDER_STATUS_TIME_SHIFT),
            'max_order_update' => Configuration::get(Shoppingfeed::ORDER_STATUS_MAX_ORDERS),
            Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE => $this->getSinceDateService()->get(),
            Shoppingfeed::IMPORT_ORDER_STATE => $this->initSfOrderState()->get()->id,
            Shoppingfeed::ORDER_TRACKING => (int) Configuration::get(Shoppingfeed::ORDER_TRACKING),
            Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE => $this->getSinceDateService()->getForShipped(),
            Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE => $this->getSinceDateService()->getForShippedByMarketplace(),
        ];

        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;

        if (version_compare(_PS_VERSION_, '1.7.8', '>=')) {
            $helper->base_tpl = 'order_status_syncro_178.tpl';
        } else {
            $helper->base_tpl = 'order_status_syncro.tpl';
        }

        return $helper->generateForm($fields_form);
    }

    public function welcomeForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedOrderImportRules'),
            ],
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . 'views/img/';
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'welcome.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * {@inheritdoc}
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
            Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
            json_encode($rulesConfiguration),
            false,
            null,
            $this->context->shop->id
        );
    }

    /**
     * Save the post-import for the module
     *
     * @return bool
     */
    public function saveOrdersConfig()
    {
        $order_import_enabled = Tools::getValue(Shoppingfeed::ORDER_IMPORT_ENABLED);
        $order_sync_enabled = Tools::getValue(Shoppingfeed::ORDER_SYNC_ENABLED);
        $order_sync_test = Tools::getValue(Shoppingfeed::ORDER_IMPORT_TEST);
        $order_sync_shipped = Tools::getValue(Shoppingfeed::ORDER_IMPORT_SHIPPED);
        $order_sync_shipped_marketplace = Tools::getValue(Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE);
        $order_tracking = Tools::getValue(Shoppingfeed::ORDER_TRACKING);

        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_ENABLED, ($order_import_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_TEST, ($order_sync_test ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_SYNC_ENABLED, ($order_sync_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_IMPORT_SHIPPED, ($order_sync_shipped ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(
                Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE,
                ($order_sync_shipped_marketplace ? true : false),
                false,
                null,
                $shop['id_shop']
            );
            Configuration::updateValue(
                Shoppingfeed::ORDER_TRACKING,
                ($order_tracking ? true : false),
                false,
                null,
                $shop['id_shop']
            );
        }

        if (Tools::getValue('order_invoice_sync_marketplace')) {
            foreach (Tools::getValue('order_invoice_sync_marketplace') as $id => $isEnabled) {
                if ((int) $isEnabled) {
                    \ShoppingfeedAddon\OrderInvoiceSync\Hub::getInstance()->enable($id);
                } else {
                    \ShoppingfeedAddon\OrderInvoiceSync\Hub::getInstance()->disable($id);
                }
            }
        }

        $orderStatusesShipped = Tools::getValue('status_shipped_order');
        if (!$orderStatusesShipped) {
            $orderStatusesShipped = [];
        }

        $orderStatusesDelivered = Tools::getValue('status_delivered_order');
        if (!is_array($orderStatusesDelivered)) {
            $orderStatusesDelivered = [];
        }

        $orderStatusesCancelled = Tools::getValue('status_cancelled_order');
        if (!$orderStatusesCancelled) {
            $orderStatusesCancelled = [];
        }

        $orderStatusRefunded = Tools::getValue('status_refunded_order');
        if (!$orderStatusRefunded) {
            $orderStatusRefunded = [];
        }

        $tracking_timeshift = Tools::getValue('tracking_timeshift');
        $max_orders = Tools::getValue('max_order_update');

        if (!is_numeric($tracking_timeshift) || (int) $tracking_timeshift <= 0) {
            $this->errors[] = $this->module->l('You must specify a valid \'Time shift\' number (greater than 0).', 'AdminShoppingfeedOrderImportRules');
        } elseif (!is_numeric($max_orders) || $max_orders > 200 || $max_orders <= 0) {
            $this->errors[] = $this->module->l('You must specify a valid \'Max Order update\' number (between 1 and 200 included).', 'AdminShoppingfeedOrderImportRules');
        } else {
            Configuration::updateValue(Shoppingfeed::SHIPPED_ORDERS, json_encode($orderStatusesShipped));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_TIME_SHIFT, (int) $tracking_timeshift);
            Configuration::updateValue(Shoppingfeed::CANCELLED_ORDERS, json_encode($orderStatusesCancelled));
            Configuration::updateValue(Shoppingfeed::REFUNDED_ORDERS, json_encode($orderStatusRefunded));
            Configuration::updateValue(Shoppingfeed::DELIVERED_ORDERS, json_encode($orderStatusesDelivered));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_MAX_ORDERS, $max_orders);
            Configuration::updateValue(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE, Tools::getValue(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }

        // Update carriers matching
        $carriersMatching = Tools::getValue('shoppingfeed_carrier_matching');
        if ($carriersMatching) {
            foreach ($carriersMatching as $id_shoppingfeed_carrier => $id_carrier_reference) {
                $sfCarrier = new ShoppingfeedCarrier($id_shoppingfeed_carrier);
                if (Validate::isLoadedObject($sfCarrier)) {
                    $sfCarrier->id_carrier_reference = (int) $id_carrier_reference;
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

        if (Tools::isSubmit(Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE)) {
            $sinceDate = DateTime::createFromFormat(
                SinceDate::DATE_FORMAT_PS,
                Tools::getValue(Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE)
            );

            if ($sinceDate instanceof DateTime) {
                $this->getSinceDateService()->setForShipped($sinceDate);
            }
        }

        if (Tools::isSubmit(Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE)) {
            $sinceDate = DateTime::createFromFormat(
                SinceDate::DATE_FORMAT_PS,
                Tools::getValue(Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE)
            );

            if ($sinceDate instanceof DateTime) {
                $this->getSinceDateService()->setForShippedByMarketplace($sinceDate);
            }
        }

        if (Tools::isSubmit(Shoppingfeed::IMPORT_ORDER_STATE)) {
            $this->initSfOrderState()->set((int) Tools::getValue(Shoppingfeed::IMPORT_ORDER_STATE));
        }

        return true;
    }

    protected function getSinceDateService()
    {
        return new SinceDate();
    }

    protected function getAvailableCarriers()
    {
        $carriers = [
            [
                'value' => 0,
                'label' => $this->l('Select carrier', 'AdminShoppingfeedOrderImportRules'),
            ],
        ];

        foreach (Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS) as $carrier) {
            $carriers[] = [
                'value' => $carrier['id_reference'],
                'label' => $carrier['name'],
            ];
        }

        return $carriers;
    }

    protected function initSfOrderState()
    {
        return new SFOrderState();
    }
}
