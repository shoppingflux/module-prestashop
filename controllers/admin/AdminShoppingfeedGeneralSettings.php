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

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedGeneralSettingsController extends ModuleAdminController
{
    public $bootstrap = true;

    public $nbr_products;
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

        $order_sync = Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED);
        $block_order_btn = false;
        if (!ShoppingFeed::isOrderSyncAvailable()) {
            $order_sync = false;
            $block_order_btn = true;
        }

        $id_shop = $this->context->shop->id;
        $token = Configuration::get(shoppingfeed::AUTH_TOKEN, null, null, $id_shop);
        if ($token) {
            $this->content .= $this->renderGlobalConfigForm($block_order_btn);
        }

        $price_sync = Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED);
        $stock_sync = Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED);
        if ($price_sync || $stock_sync) {
            $this->content .= $this->renderSynchroConfigForm();
        }

        if ($order_sync) {
            $this->content .= $this->renderOrderSyncForm();
        }

        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    /**
     * @return string
     */
    public function renderOrderSyncForm()
    {
        $fields_form = array(
            'form' => array(
                'form' => array(
                    'id_form' => 'order-sync-form',
                    'legend' => array(
                        'title' => $this->module->l('Post-import orders synchronization settings (All shop)', 'AdminShoppingfeedGeneralSettings'),
                    ),
                    'submit' => array(
                        'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                        'name' => 'savePostImport'
                    ),
                ),
            ),
        );

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
            if (in_array($state['id_order_state'], $ids_shipped_status_selected)) {
                $orderShippedState['selected'][] = $state;
            } else {
                $orderShippedState['unselected'][] = $state;
            }

            if (in_array($state['id_order_state'], $ids_cancelled_status_selected)) {
                $orderCancelledState['selected'][] = $state;
            } else {
                $orderCancelledState['unselected'][] = $state;
            }

            if (in_array($state['id_order_state'], $ids_refunded_status_selected)) {
                $orderRefundedState['selected'][] = $state;
            } else {
                $orderRefundedState['unselected'][] = $state;
            }
        }

        $cronLink = $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor', false);

        $helper = new HelperForm($this);
        $helper->tpl_vars['order_shipped'] = $orderShippedState;
        $helper->tpl_vars['order_cancelled'] = $orderCancelledState;
        $helper->tpl_vars['order_refunded'] = $orderRefundedState;
        $helper->tpl_vars['cron_link'] = $cronLink;
        $helper->tpl_vars['time_shift'] = Configuration::get(Shoppingfeed::ORDER_STATUS_TIME_SHIFT);
        $helper->tpl_vars['max_orders'] = Configuration::get(Shoppingfeed::ORDER_STATUS_MAX_ORDERS);
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'order_status_syncro.tpl';

        return $helper->generateForm($fields_form);
    }

    public function welcomeForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('15 min Marketplace Updates - Shopping', 'AdminShoppingfeedGeneralSettings'),
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
     * Renders the HTML for the global configuration form
     * @return string the rendered form's HTML
     */
    public function renderGlobalConfigForm($block_order_btn)
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Configuration (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'value' => 1,
                        ),
                        array(
                            'value' => 0,
                        )
                    ),
                    'label' => $this->module->l('Stock synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::STOCK_SYNC_ENABLED,
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'value' => 1,
                        ),
                        array(
                            'value' => 0,
                        )
                    ),
                    'label' => $this->module->l('Price synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRICE_SYNC_ENABLED,
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'disabled' => $block_order_btn,
                    'hint' => "The order post-import synchronization allows you to manage the following order statuses : shipped, cancelled, refunded.",
                    'values' => array(
                        array(
                            'value' => 1,
                        ),
                        array(
                            'value' => 0,
                        )
                    ),
                    'label' => $this->module->l('Orders post-import synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::ORDER_SYNC_ENABLED,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveGlobalConfig'
            )
        );

        if ($block_order_btn) {
            $fields_form['input'][] = array(
                'type' => 'html',
                'name' => 'for_real',
                'html_content' => '<div id="for_real" class="alert alert-warning">
                    '.$this->module->l('The Shopping Feed Official module (shoppingfluxexport) should be installed on your shop for enabling the post-import synchronization. The “Order shipment” & “Order cancellation” options must be disabled in the official module for enabling this type of synchronization in the new module. If you disable these options in the official module and you enable them again later the “Orders post-import synchronization” will be disabled automatically in the Shopping feed 15 min module.', 'AdminShoppingfeedGeneralSettings').'</div>',
            );
        }

        $fields_value = array(
            Shoppingfeed::STOCK_SYNC_ENABLED => Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED),
            Shoppingfeed::PRICE_SYNC_ENABLED => Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED),
            Shoppingfeed::ORDER_SYNC_ENABLED => Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the synchro configuration form
     * @return string the rendered form's HTML
     */
    public function renderSynchroConfigForm()
    {
        switch (true) {
            case ($this->nbr_products <= 100):
                $message_realtime = $this->module->l('You have less than 100 products, the RealTime parameter on YES is recommended. You have little stock for each reference and for you the stock precision is fundamental. Moreover, no need to set up any cron job. Sending real-time inventory updates to the Feed API makes it easy for you to sync inventory in less than 15 minutes. However, this multiplies the calls to the Shopping API stream wchich can slow the loading time of pages that decrement or increment the stock, especially during order status updates.', 'AdminShoppingfeedGeneralSettings');
                break;
            case ($this->nbr_products < 1000 && $this->nbr_products > 100):
                $message_realtime = $this->module->l('You have between 100 and 1000 products, the Realtime parameter on NO is recommended. Updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances.', 'AdminShoppingfeedGeneralSettings');
                break;
            case ($this->nbr_products > 1000):
                $message_realtime = $this->module->l('You have more than 1000 products, Realtime parameter NO is required. You probably use an external tool (like an ERP) to manage your inventory which can lead to many updates at the same time. In this case, the updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances', 'AdminShoppingfeedGeneralSettings');
                break;
        }

        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Products synchronization type (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'real_synch',
                    'html_content' => '<div id="real_synch_notice" class="alert alert-info">
                    '.sprintf(
                            $this->module->l('You should select the type of synchronization (in real time or via a %s Cron job %s) for updating your product stocks and / or prices.', 'AdminShoppingfeedGeneralSettings'),
                            '<a href="' . $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor') . '">',
                            '</a>'
                        ).'</div>',
                ),
                array(
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    '.$message_realtime.'</div>',
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'value' => 1,
                        ),
                        array(
                            'value' => 0,
                        )
                    ),
                    'label' => $this->module->l('Real-time synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'hint' => $this->module->l('If checked, no CRON will be needed. Synchronization will occur as soon as the changes are made. This may impact user performance.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::REAL_TIME_SYNCHRONIZATION,
                ),
                array(
                    'type' => 'html',
                    'name' => 'for_real',
                    'html_content' => '<div id="for_real" class="alert alert-warning">
                    '.$this->module->l('The Max product update parameter is reserved for experts (100 by default). You can configure the number of products to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.', 'AdminShoppingfeedGeneralSettings').'</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Max. product update per request', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS,
                    'required' => true,
                    'class' => 'for_real'
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveSynchroConfig'
            )
        );

        $fields_value = array(
            Shoppingfeed::REAL_TIME_SYNCHRONIZATION => Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION),
            Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS => Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveGlobalConfig')) {
            return $this->saveGlobalConfig();
        } elseif (Tools::isSubmit('saveSynchroConfig')) {
            return $this->saveSynchroConfig();
        } elseif (Tools::isSubmit('savePostImport')) {
            return $this->savePostImport();
        }
    }

    /**
     * Saves the global configuration for the module
     * @return bool
     */
    public function saveGlobalConfig()
    {
        $stock_sync_enabled = Tools::getValue(Shoppingfeed::STOCK_SYNC_ENABLED);
        $price_sync_enabled = Tools::getValue(Shoppingfeed::PRICE_SYNC_ENABLED);
        $order_sync_enabled = Tools::getValue(Shoppingfeed::ORDER_SYNC_ENABLED);

        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::STOCK_SYNC_ENABLED, ($stock_sync_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRICE_SYNC_ENABLED, ($price_sync_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::ORDER_SYNC_ENABLED, ($order_sync_enabled ? true : false), false, null, $shop['id_shop']);
        }

        return true;
    }

    /**
     * Saves the synchro configuration for the module
     * @return bool
     */
    public function saveSynchroConfig()
    {
        $realtime_sync = Tools::getValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION);
        $stock_sync_max_products = (int)Tools::getValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);

        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, ($realtime_sync ? true : false), false, null, $shop['id_shop']);

            if (!is_numeric($stock_sync_max_products) || $stock_sync_max_products > 200 || $stock_sync_max_products <= 0) {
                $this->errors[] = $this->module->l('You must specify a \'Max. product update per request\' number (between 1 and 200 included).', 'AdminShoppingfeedGeneralSettings');
            } else {
                Configuration::updateValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, $stock_sync_max_products, false, null, $shop['id_shop']);
            }
        }

        return true;
    }

    /**
     * Save the post-import for the module
     * @return bool
     */
    public function savePostImport()
    {
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
            $this->errors[] = $this->module->l('You must specify a valid \'Time shift\' number (greater than 0).', 'AdminShoppingfeedGeneralSettings');
        } elseif (!is_numeric($max_orders) || $max_orders > 200 || $max_orders <= 0) {
            $this->errors[] = $this->module->l('You must specify a valid \'Max Order update\' number (between 1 and 200 included).', 'AdminShoppingfeedGeneralSettings');
        } else {
            Configuration::updateValue(Shoppingfeed::SHIPPED_ORDERS, json_encode($orderStatusesShipped));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_TIME_SHIFT, (int)$tracking_timeshift);
            Configuration::updateValue(Shoppingfeed::CANCELLED_ORDERS, json_encode($orderStatusesCancelled));
            Configuration::updateValue(Shoppingfeed::REFUNDED_ORDERS, json_encode($orderStatusRefunded));
            Configuration::updateValue(Shoppingfeed::ORDER_STATUS_MAX_ORDERS, $max_orders);
        }

        return true;
    }
}
