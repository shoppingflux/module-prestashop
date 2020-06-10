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
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedCarrier.php');

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

        $price_sync = Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED);
        $stock_sync = Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED);
        if ($price_sync || $stock_sync) {
            $this->content .= $this->renderSynchroConfigForm();
        }

        $id_shop = $this->context->shop->id;
        $token = Configuration::get(shoppingfeed::AUTH_TOKEN, null, null, $id_shop);
        if ($token) {
            $this->content .= $this->renderGlobalConfigForm();
            $this->content .= $this->renderFeedConfigForm();
        }

        $this->module->setBreakingChangesNotices();

        parent::initContent();
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
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'products_feeds.tpl';

        $this->context->smarty->assign('count_products', $this->countProducts());


        return $helper->generateForm(array(array('form' => $fields_form)));
    }


    protected function countProducts()
    {
        $getPack = '';
        if ((int) Configuration::get(Shoppingfeed::PRODUCT_FEED_SYNC_PACK) !== 1) {
            $getPack = ' AND p.`cache_is_pack` = 0 ';
        }

        $context = Context::getContext();

        if (!in_array($context->controller->controller_type, array('front', 'modulefront'))) {
            $front = false;
        } else {
            $front = true;
        }

        $sql_association = Shop::addSqlAssociation('product', 'p');
        $table = $sql_association ? 'product'.'_shop' : 'p';

        $sql = 'SELECT COUNT(p.`id_product`)
            FROM `'._DB_PREFIX_.'product` p
            '.$sql_association.'
            WHERE '.$table.'.`active`= 1 AND '.$table.'.`available_for_order`= 1
            ' . $getPack . '
            '.($front ? ' AND '.$table.'.`visibility` IN ("both", "catalog")' : '');


        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Renders the HTML for the global configuration form
     * @return string the rendered form's HTML
     */
    public function renderGlobalConfigForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Stocks and prices 15 min updates (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    '.$this->module->l('If you disabled stocks and prices synchronisation, your data will be sync only once a day with your products feed.', 'AdminShoppingfeedGeneralSettings').'</div>',
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
                    'label' => $this->module->l('Products Stock synchronization', 'AdminShoppingfeedGeneralSettings'),
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
                    'label' => $this->module->l('Products Price synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRICE_SYNC_ENABLED,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveGlobalConfig'
            )
        );

        $fields_value = array(
            Shoppingfeed::STOCK_SYNC_ENABLED => Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED),
            Shoppingfeed::PRICE_SYNC_ENABLED => Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the product feed configuration form
     * @return string the rendered form's HTML
     */
    public function renderFeedConfigForm()
    {
        $sfCarriers = ShoppingfeedCarrier::getAllCarriers();
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Products feed (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    '.$this->module->l('This let configure product feed feed.', 'AdminShoppingfeedGeneralSettings').'</div>',
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
                    'label' => $this->module->l('Export packs', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_SYNC_PACK,
                ),
                array(
                    'type' => 'select',
                    'options' => array(
                        'query' => array_map(
                            function ($c) {
                                return array(
                                    'id' => $c['id_reference'],
                                    'name' => $c['name'],
                                );
                            },
                            Carrier::getCarriers(Context::getContext()->language->id, true)
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'label' => $this->module->l('Shipping cost based on carrier', 'AdminShoppingfeedGeneralSettings'),
                    'desc' => $this->module->l('Each product are computed according to this carrier.', 'AdminShoppingfeedGeneralSettings'),
                    'shoppingfeed_carriers' => $sfCarriers,
                    'name' => Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE,
                ),
                array(
                    'type' => 'select',
                    'options' => array(
                        'query' => array_map(
                            function ($c) {
                                return array(
                                    'id' => $c['name'],
                                    'name' => $c['name'],
                                );
                            },
                            ImageType::getImagesTypes('products')
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'label' => $this->module->l('Image format', 'AdminShoppingfeedGeneralSettings'),
                    'hint' => $this->module->l('Send image according to a specific image format.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT,
                ),
                array(
                    'type' => 'select',
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 'breadcrumb',
                                'name' => $this->module->l('Breadcrumb format with all parents categories', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'default_category',
                                'name' => $this->module->l('Only the default category', 'AdminShoppingfeedGeneralSettings'),
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'label' => $this->module->l('Category display', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveFeedConfig'
            )
        );

        $fields_value = array(
            Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE => Configuration::get(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE),
            Shoppingfeed::PRODUCT_FEED_SYNC_PACK => Configuration::get(Shoppingfeed::PRODUCT_FEED_SYNC_PACK),
            Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT => Configuration::get(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT),
            Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY => Configuration::get(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY),
        );

        $customFields = $this->getOverrideFields();
        if (empty($customFields) === false) {
            $fields_form['input'][] = array(
                'type' => 'select',
                'multiple' => true,
                'options' => array(
                    'query' => array_map(
                        function ($field) {
                            return array(
                                'id' => $field,
                                'name' => $field,
                            );
                        },
                        $customFields
                    ),
                    'id' => 'id',
                    'name' => 'name',
                ),
                'label' => $this->module->l('Custom fields', 'AdminShoppingfeedGeneralSettings'),
                'name' => Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS . '[]',
                'desc' => $this->module->l('Select products fields to include on the feed.', 'AdminShoppingfeedGeneralSettings'),
            );
            $customFieldsValues = json_decode(Configuration::get(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS), true);
            $fields_value[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS. '[]'] = $customFieldsValues;
        }

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
                $message_realtime = $this->module->l('You have less than 100 products, the RealTime parameter on YES is recommended. You have little stock for each reference and for you the stock precision is fundamental. Moreover, no need to set up any cron job. Sending real-time inventory updates to the Feed API makes it easy for you to sync inventory in less than 15 minutes. However, this multiplies the calls to the Shopping API stream which can slow the loading time of pages that decrement or increment the stock, especially during order status updates.', 'AdminShoppingfeedGeneralSettings');
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
        } elseif (Tools::isSubmit('saveFeedConfig')) {
            return $this->saveFeedConfig();
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

        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::STOCK_SYNC_ENABLED, ($stock_sync_enabled ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRICE_SYNC_ENABLED, ($price_sync_enabled ? true : false), false, null, $shop['id_shop']);
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
     * Saves the synchro configuration for the module
     * @return bool
     */
    public function saveFeedConfig()
    {
        $sync_pack = Tools::getValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK);
        $carrierReference = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE);
        $imageFormat = Tools::getValue(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT);
        $categoryDisplay = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY);
        $customFields = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS);


        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            Configuration::updateValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK, ($sync_pack ? true : false), false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE, $carrierReference, false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT, $imageFormat, false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY, $categoryDisplay, false, null, $shop['id_shop']);
            Configuration::updateValue(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS, json_encode($customFields));
        }

        return true;
    }

    /**
     * Get additional fields from Product.php override
     */
    private function getOverrideFields()
    {
        // Load core Product info

        static $definition;

        // Load override Product info
        $overrideProductFields = Product::$definition['fields'];

        $newFields = array();

        $productCoreFields = ProductCore::$definition['fields'];
        $coreFields = array();

        foreach ($productCoreFields as $key => $value) {
            $coreFields[] = $key;
        }

        foreach ($overrideProductFields as $key => $value) {
            if (!in_array($key, $coreFields)) {
                $newFields[] = $key;
            }
        }

        return $newFields;
    }
}
