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

use ShoppingfeedClasslib\Actions\ActionsHandler;

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
        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        if (empty($tokens)) {
            Tools::redirectAdmin(
                Context::getContext()->link->getAdminLink('AdminShoppingfeedAccountSettings')
            );
        }
        $this->addCSS(array(
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css'
        ));
        $this->nbr_products = $this->module->countProductsOnFeed();
        $this->addJS($this->module->getPathUri() . 'views/js/form_config.js');
        $this->addJS($this->module->getPathUri() . 'views/js/form_config_filter.js');
        Media::addJsDef(['url_product_selection_form' => $this->context->link->getAdminLink('AdminShoppingfeedGeneralSettings')]);
        $this->content = $this->welcomeForm();
        $this->content .= $this->renderSynchroConfigForm();
        $this->content .= $this->renderGlobalConfigForm();
        $this->content .= $this->renderFeedConfigForm();
        $this->content .= $this->renderProductSelectionConfigForm();
        $this->content .= $this->renderFactoryConfigForm();

        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    public function welcomeForm()
    {
        $product_feed_rule_filters = Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS);
        $product_filters = Tools::jsonDecode($product_feed_rule_filters, true);

        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedGeneralSettings'),
            )
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . "views/img/";
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'products_feeds.tpl';

        $this->context->smarty->assign('count_products', $this->nbr_products);
        $this->context->smarty->assign('hasAFilter', $product_filters !== null);
        $shoppingfeedPreloading = new ShoppingfeedPreloading();
        $token = (new ShoppingfeedToken())->getDefaultToken();
        $this->context->smarty->assign('count_preloading', $shoppingfeedPreloading->getPreloadingCount($token['id_shoppingfeed_token']));

        $crons = new ShoppingfeedClasslib\Extensions\ProcessMonitor\Classes\ProcessMonitorObjectModel();
        $syncProduct = $crons->findOneByName('shoppingfeed:syncProduct');
        $this->context->smarty->assign('syncProduct', $syncProduct);

        return $helper->generateForm(array(array('form' => $fields_form)));
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
                    '.$this->module->l('By updating this form, please not your index will be purge.', 'AdminShoppingfeedGeneralSettings').'</div>',
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
                            Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS)
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'label' => $this->module->l('Shipping cost based on carrier', 'AdminShoppingfeedGeneralSettings'),
                    'desc' => $this->module->l('Each product are computed according to this carrier.', 'AdminShoppingfeedGeneralSettings'),
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
     * Renders the HTML for the global configuration form
     * @return string the rendered form's HTML
     */
    public function renderFactoryConfigForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Factory settings', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    '.$this->module->l('This settings are only updatable by Shoppingfeed support team.', 'AdminShoppingfeedGeneralSettings').'</div>',
                ),
                array(
                    'type' => 'select',
                    'disabled' => (Tools::getValue('with_factory') !== false) ? false : true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => '',
                                'name' => $this->module->l('Default value (ID product with ID combination)', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'reference',
                                'name' => $this->module->l('Reference (SKU defined by the merchand)', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'supplier_reference',
                                'name' => $this->module->l('Supplier reference', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'isbn',
                                'name' => $this->module->l('ISBN code', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'ean13',
                                'name' => $this->module->l('EAN-13 or JAN barcode', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'upc',
                                'name' => $this->module->l('UPC barcode', 'AdminShoppingfeedGeneralSettings'),
                            ),
                            array(
                                'id' => 'mpn',
                                'name' => $this->module->l('MPN', 'AdminShoppingfeedGeneralSettings'),
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'label' => $this->module->l('Product reference association', 'AdminShoppingfeedGeneralSettings'),
                    'desc' => $this->module->l('Shoud be: Default shoppingfeed value, reference, supplier reference, isbn, ean13, upc or mpn.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT,
                ),
            ),
        );
        if (Tools::getValue('with_factory') !== false) {
            $fields_form['submit'] = array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveFactoryConfig',
            );
            $fields_form['input'][] = array(
                'type' => 'hidden',
                'name' => 'with_factory',
            );
        }

        $fields_value = array(
            Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT => Configuration::get(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT),
            'with_factory' => Tools::getValue('with_factory'),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function renderProductSelectionConfigForm()
    {
        $tpl = Context::getContext()->smarty->createTemplate(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/shoppingfeed_general_settings/product_filter.tpl');

        $product_feed_rule_filters = Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS);
        $product_filters = Tools::jsonDecode($product_feed_rule_filters, true);

        $tpl->assign([
            'product_filters' => ($product_feed_rule_filters === null)? [] : $product_filters,
        ]);

        return $tpl->fetch();
    }

    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveFeedFilterConfig')) {
            $this->saveFilterConfig();
            $this->purgePrealoading();
            return true;
        } else if (Tools::isSubmit('saveGlobalConfig')) {
            return $this->saveGlobalConfig();
        } elseif (Tools::isSubmit('saveSynchroConfig')) {
            return $this->saveSynchroConfig();
        } elseif (Tools::isSubmit('saveFeedConfig')) {
            $this->purgePrealoading();
            return $this->saveFeedConfig();
        } elseif (Tools::isSubmit('saveFactoryConfig') && Tools::getValue('with_factory') !== false) {
            $this->purgePrealoading();
            return $this->saveFactoryConfig();
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

        Configuration::updateGlobalValue(Shoppingfeed::STOCK_SYNC_ENABLED, ($stock_sync_enabled ? true : false));
        Configuration::updateGlobalValue(Shoppingfeed::PRICE_SYNC_ENABLED, ($price_sync_enabled ? true : false));

        return true;
    }

    public function saveFilterConfig()
    {
        $product_rule_select = Tools::getValue('product_rule_select', []);
        $product_filter = [];

        foreach ($product_rule_select as $type => $filterIds) {
            if (empty($type)) {
                continue;
            }
            $id = implode(',', $filterIds);
            $id = explode(',', $id);
            $id = array_filter($id, 'is_numeric');
            $id = implode(',', $id);
            if (empty($id)) {
                continue;
            }
            $product_filter[$type] = $id;
        }
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS, Tools::jsonEncode($product_filter));

        return true;
    }
    

    public function purgePrealoading()
    {
        $preloading = new ShoppingfeedPreloading();
        $preloading->purge();

        if ($this->module->countProductsOnFeed() < 100) {
            $handler = new ActionsHandler();
            $handler->addActions('getBatch');
            $sft = new ShoppingfeedToken();
            $tokens = $sft->findAllActive();
            try {
                foreach ($tokens as $token) {
                    $handler->setConveyor(array(
                        'id_token' => $token['id_shoppingfeed_token'],
                        'product_action' => ShoppingfeedPreloading::ACTION_SYNC_PRELODING,
                    ));
                    $processResult = $handler->process('shoppingfeedProductSyncPreloading');
                }
            } catch (Exception $e) {
                return true;
            }
        }
    }

    /**
     * Saves the global configuration for the module
     * @return bool
     */
    public function saveFactoryConfig()
    {
        $reference_format = Tools::getValue(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT);

        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT, $reference_format);

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

        Configuration::updateGlobalValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, ($realtime_sync ? true : false));

        if (!is_numeric($stock_sync_max_products) || $stock_sync_max_products > 200 || $stock_sync_max_products <= 0) {
            $this->errors[] = $this->module->l('You must specify a \'Max. product update per request\' number (between 1 and 200 included).', 'AdminShoppingfeedGeneralSettings');
        } else {
            Configuration::updateGlobalValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, $stock_sync_max_products);
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


        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK, ($sync_pack ? true : false));
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE, $carrierReference);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT, $imageFormat);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY, $categoryDisplay);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS, json_encode($customFields));

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

    public function displayAjaxPurgeCache()
    {
        $this->purgePrealoading();
        $this->ajaxDie(Tools::jsonEncode(['success' => true]));
    }

    public function displayAjaxProductSelectionConfigForm()
    {
        $product_rule_type = Tools::getValue('product_rule_type');
        $selected = Tools::getValue('selected', '');
        if (Tools::getValue('selected', '') === '') {
            $selected = [];
        } else {
            $selected = explode(',', $selected);
        }
        $products = [
            'selected' => [], 
            'unselected' => []
        ];
        switch ($product_rule_type) {
            case 'attributes':
                $results = Db::getInstance()->executeS('
				SELECT CONCAT(agl.name, " - ", al.name) as name, a.id_attribute as id
				FROM '._DB_PREFIX_.'attribute_group_lang agl
				LEFT JOIN '._DB_PREFIX_.'attribute a ON a.id_attribute_group = agl.id_attribute_group
				LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = '.(int)Context::getContext()->language->id.')
				WHERE agl.id_lang = '.(int)Context::getContext()->language->id.'
				ORDER BY agl.name, al.name');
                break;
            case 'products':
                $results = Db::getInstance()->executeS('
				SELECT DISTINCT name, p.id_product as id
				FROM '._DB_PREFIX_.'product p
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int)Context::getContext()->language->id.Shop::addSqlRestrictionOnLang('pl').')
				'.Shop::addSqlAssociation('product', 'p').'
				WHERE id_lang = '.(int)Context::getContext()->language->id.'
				ORDER BY name');
                break;
            case 'manufacturers':
                $results = Db::getInstance()->executeS('
				SELECT name, id_manufacturer as id
				FROM '._DB_PREFIX_.'manufacturer
				ORDER BY name');
                break;
            case 'suppliers':
                $results = Db::getInstance()->executeS('
				SELECT name, id_supplier as id
				FROM '._DB_PREFIX_.'supplier
				ORDER BY name');
                break;
            case 'categories':
                $results = Db::getInstance()->executeS('
				SELECT DISTINCT name, c.id_category as id
				FROM '._DB_PREFIX_.'category c
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (c.`id_category` = cl.`id_category`
					AND cl.`id_lang` = '.(int)Context::getContext()->language->id.Shop::addSqlRestrictionOnLang('cl').')
				'.Shop::addSqlAssociation('category', 'c').'
				WHERE id_lang = '.(int)Context::getContext()->language->id.'
				ORDER BY name');
                break;
            case 'features':
                $results = Db::getInstance()->executeS('
                SELECT DISTINCT name, f.id_feature as id
                FROM '._DB_PREFIX_.'feature f
                LEFT JOIN `'._DB_PREFIX_.'feature_lang` fl
                    ON (f.`id_feature` = fl.`id_feature`)
                '.Shop::addSqlAssociation('feature', 'f').'
                WHERE id_lang = '.(int)Context::getContext()->language->id.'
                ORDER BY name');
                break;
            default :
                return '';
        }
        foreach ($results as $row) {
            $products[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
        }

        $tpl = Context::getContext()->smarty->createTemplate(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/shoppingfeed_general_settings/product_filter_rules.tpl');
        $tpl->assign([
            'products' => $products,
        ]);

        return $tpl->display();
    }
}
