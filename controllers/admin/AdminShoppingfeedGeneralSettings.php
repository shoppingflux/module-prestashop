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

use ShoppingfeedAddon\ProductFilter\FilterFactory;
use ShoppingfeedClasslib\Actions\ActionsHandler;

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedGeneralSettingsController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    public $nbr_products;

    public $override_folder;

    /**
     * {@inheritdoc}
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
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css',
        ]);
        $this->nbr_products = $this->module->countProductsOnFeed($this->context->shop->id);
        $this->addJS($this->module->getPathUri() . 'views/js/form_config.js');
        $this->addJS($this->module->getPathUri() . 'views/js/form_config_filter.js');
        Media::addJsDef(['url_product_selection_form' => $this->context->link->getAdminLink('AdminShoppingfeedGeneralSettings')]);
        $this->content = $this->welcomeForm();
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
        $product_filters = json_decode($product_feed_rule_filters, true);

        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedGeneralSettings'),
            ],
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . 'views/img/';
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'products_feeds.tpl';
        $tokens = (new ShoppingfeedToken())->findAllActive();
        $shoppingfeedPreloading = new ShoppingfeedPreloading();
        $countPreloading = 0;
        $countProductInShops = 0;

        foreach ($tokens as $token) {
            $countProductInShops += (int) $this->module->countProductsOnFeed((int) $token['id_shop']);
            $countPreloading += (int) $shoppingfeedPreloading->getPreloadingCountForSync($token['id_shoppingfeed_token']);
        }

        // avoid division by zero
        if ($countProductInShops) {
            $percentPreloading = ($countPreloading / $countProductInShops) * 100;
        } else {
            $percentPreloading = 0;
        }

        $this->context->smarty->assign('count_products', $this->nbr_products);
        $this->context->smarty->assign('hasAFilter', $product_filters !== null);
        $this->context->smarty->assign('percent_preloading', floor($percentPreloading));
        $this->context->smarty->assign(
            'productFlowLink',
            $this->context->link->getModuleLink(
                'shoppingfeed',
                'product',
                ['feed_key' => empty($token['feed_key']) ? '' : $token['feed_key']],
                true
            )
        );
        $crons = new ShoppingfeedClasslib\Extensions\ProcessMonitor\Classes\ProcessMonitorObjectModel();
        $syncProduct = $crons->findOneByName('shoppingfeed:syncProduct');
        $this->context->smarty->assign('syncProduct', $syncProduct);

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Renders the HTML for the global configuration form
     *
     * @return string the rendered form's HTML
     */
    public function renderGlobalConfigForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Stocks and prices 15 min updates (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog',
            ],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    ' . $this->module->l('If you disabled stocks and prices synchronisation, your data will be sync only once a day with your products feed.', 'AdminShoppingfeedGeneralSettings') . '</div>',
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
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
                    'label' => $this->module->l('Products Stock synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::STOCK_SYNC_ENABLED,
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
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
                    'label' => $this->module->l('Products Price synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRICE_SYNC_ENABLED,
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveGlobalConfig',
            ],
        ];

        $fields_value = [
            Shoppingfeed::STOCK_SYNC_ENABLED => Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED),
            Shoppingfeed::PRICE_SYNC_ENABLED => Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED),
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Renders the HTML for the product feed configuration form
     *
     * @return string the rendered form's HTML
     */
    public function renderFeedConfigForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Products feed (all shops)', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog',
            ],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    ' . $this->module->l('By updating this form, please not your index will be purge.', 'AdminShoppingfeedGeneralSettings') . '</div>',
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
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
                    'label' => $this->module->l('Export packs', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_SYNC_PACK,
                ],
                [
                    'type' => 'select',
                    'options' => [
                        'query' => array_merge(
                            [
                                [
                                    'id' => 0,
                                    'name' => $this->module->l('Select carrier', 'AdminShoppingfeedGeneralSettings'),
                                ],
                            ],
                            array_map(
                                function ($c) {
                                    return [
                                        'id' => $c['id_reference'],
                                        'name' => $c['name'],
                                    ];
                                },
                                Carrier::getCarriers(Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS)
                            )
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'label' => $this->module->l('Shipping cost based on carrier', 'AdminShoppingfeedGeneralSettings'),
                    'desc' => $this->module->l('Each product are computed according to this carrier.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE,
                ],
                [
                    'type' => 'select',
                    'options' => [
                        'query' => array_map(
                            function ($c) {
                                return [
                                    'id' => $c['name'],
                                    'name' => $c['name'],
                                ];
                            },
                            ImageType::getImagesTypes('products')
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'label' => $this->module->l('Image format', 'AdminShoppingfeedGeneralSettings'),
                    'hint' => $this->module->l('Send image according to a specific image format.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT,
                ],
                [
                    'type' => 'select',
                    'options' => [
                        'query' => [
                            [
                                'id' => 'breadcrumb',
                                'name' => $this->module->l('Breadcrumb format with all parents categories', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'default_category',
                                'name' => $this->module->l('Only the default category', 'AdminShoppingfeedGeneralSettings'),
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'label' => $this->module->l('Category display', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY,
                ],
                [
                    'type' => 'select',
                    'options' => [
                        'query' => [
                            [
                                'id' => 'product_with_children',
                                'name' => $this->module->l('Child products of their parent product', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'product_separate_children_with_parent',
                                'name' => $this->module->l('Separately (one node for each parent and child product)', 'AdminShoppingfeedGeneralSettings'),
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'label' => $this->module->l('Layout of variations', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY,
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveFeedConfig',
            ],
        ];

        $fields_value = [
            Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE => Configuration::get(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE),
            Shoppingfeed::PRODUCT_FEED_SYNC_PACK => Configuration::get(Shoppingfeed::PRODUCT_FEED_SYNC_PACK),
            Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT => Configuration::get(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT),
            Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY => Configuration::get(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY),
            Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY => Configuration::get(Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY),
        ];

        $customFields = $this->getOverrideFields();
        if (empty($customFields) === false) {
            $fields_form['input'][] = [
                'type' => 'select',
                'multiple' => true,
                'options' => [
                    'query' => array_map(
                        function ($field) {
                            return [
                                'id' => $field,
                                'name' => $field,
                            ];
                        },
                        $customFields
                    ),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'label' => $this->module->l('Custom fields', 'AdminShoppingfeedGeneralSettings'),
                'name' => Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS . '[]',
                'desc' => $this->module->l('Select products fields to include on the feed.', 'AdminShoppingfeedGeneralSettings'),
            ];
            $customFieldsValues = json_decode(Configuration::get(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS), true);
            $fields_value[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS . '[]'] = $customFieldsValues;
        }

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Renders the HTML for the global configuration form
     *
     * @return string the rendered form's HTML
     */
    public function renderFactoryConfigForm()
    {
        $syncByDateUpdate = (bool) Configuration::get(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD);
        $time_full_update = (int) Configuration::get(Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE);
        $interval_cron = (int) Configuration::get(Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON);
        $message_realtime = '';
        switch (true) {
            case $this->nbr_products <= 100:
                $message_realtime = $this->module->l('You have less than 100 products, the RealTime parameter on YES is recommended. You have little stock for each reference and for you the stock precision is fundamental. Moreover, no need to set up any cron job. Sending real-time inventory updates to the Feed API makes it easy for you to sync inventory in less than 15 minutes. However, this multiplies the calls to the Shopping API stream which can slow the loading time of pages that decrement or increment the stock, especially during order status updates.', 'AdminShoppingfeedGeneralSettings');
                break;
            case $this->nbr_products < 1000 && $this->nbr_products > 100:
                $message_realtime = $this->module->l('You have between 100 and 1000 products, the Realtime parameter on NO is recommended. Updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances.', 'AdminShoppingfeedGeneralSettings');
                break;
            case $this->nbr_products > 1000:
                $message_realtime = $this->module->l('You have more than 1000 products, Realtime parameter NO is required. You probably use an external tool (like an ERP) to manage your inventory which can lead to many updates at the same time. In this case, the updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances', 'AdminShoppingfeedGeneralSettings');
                break;
        }

        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Factory settings', 'AdminShoppingfeedGeneralSettings'),
                'icon' => 'icon-cog',
            ],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    ' . $this->module->l('This settings are only updatable by Shoppingfeed support team.', 'AdminShoppingfeedGeneralSettings') . '</div>',
                ],
                [
                    'type' => 'select',
                    'disabled' => (Tools::getValue('with_factory') !== false) ? false : true,
                    'options' => [
                        'query' => [
                            [
                                'id' => '',
                                'name' => $this->module->l('Default value (ID product with ID combination)', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'reference',
                                'name' => $this->module->l('Reference (SKU defined by the merchand)', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'supplier_reference',
                                'name' => $this->module->l('Supplier reference', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'isbn',
                                'name' => $this->module->l('ISBN code', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'ean13',
                                'name' => $this->module->l('EAN-13 or JAN barcode', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'upc',
                                'name' => $this->module->l('UPC barcode', 'AdminShoppingfeedGeneralSettings'),
                            ],
                            [
                                'id' => 'mpn',
                                'name' => $this->module->l('MPN', 'AdminShoppingfeedGeneralSettings'),
                            ],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'label' => $this->module->l('Product reference association', 'AdminShoppingfeedGeneralSettings'),
                    'desc' => $this->module->l('Shoud be: Default shoppingfeed value, reference, supplier reference, isbn, ean13, upc or mpn.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT,
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
                    'disabled' => (Tools::getValue('with_factory') !== false) ? ($time_full_update > 0 || $interval_cron > 0) : true,
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
                    'label' => $this->module->l("Synchronize the XML feed from the 'ps_product.date_upd' et 'ps_product_shop.date_upd' fields", 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD,
                ],
                [
                    'type' => 'number',
                    'label' => $this->module->l('Update products in XML feed every X hours', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE,
                    'disabled' => (Tools::getValue('with_factory') !== false) ? $syncByDateUpdate : true,
                    'class' => 'for_real',
                ],
                [
                    'type' => 'number',
                    'label' => $this->module->l('Cron update time every X minutes', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON,
                    'disabled' => (Tools::getValue('with_factory') !== false) ? $syncByDateUpdate : true,
                    'class' => 'for_real',
                ],
                [
                    'type' => 'html',
                    'name' => 'real_synch',
                    'html_content' => '<div id="real_synch_notice" class="alert alert-info">
                    ' . sprintf(
                        $this->module->l('You should select the type of synchronization (in real time or via a %s Cron job %s) for updating your product stocks and / or prices.', 'AdminShoppingfeedGeneralSettings'),
                        '<a href="' . $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor') . '">',
                        '</a>'
                    ) . '</div>',
                ],
                [
                    'type' => 'html',
                    'name' => 'real_synch_help',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    ' . $message_realtime . '</div>',
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
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
                    'label' => $this->module->l('Real-time synchronization', 'AdminShoppingfeedGeneralSettings'),
                    'hint' => $this->module->l('If checked, no CRON will be needed. Synchronization will occur as soon as the changes are made. This may impact user performance.', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::REAL_TIME_SYNCHRONIZATION,
                    'disabled' => (Tools::getValue('with_factory') !== false) ? $syncByDateUpdate : true,
                ],
                [
                    'type' => 'html',
                    'name' => 'for_real',
                    'html_content' => '<div id="for_real" class="alert alert-warning">
                    ' . $this->module->l('The Max product update parameter is reserved for experts (100 by default). You can configure the number of products to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.', 'AdminShoppingfeedGeneralSettings') . '</div>',
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Max. product update per request', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS,
                    'required' => true,
                    'class' => 'for_real',
                    'disabled' => (Tools::isSubmit('with_factory') === false),
                ],
                [
                    'type' => 'switch',
                    'is_bool' => true,
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
                    'label' => $this->module->l('Compress products feed', 'AdminShoppingfeedGeneralSettings'),
                    'name' => Shoppingfeed::COMPRESS_PRODUCTS_FEED,
                    'disabled' => (Tools::isSubmit('with_factory') === false),
                ],
            ],
        ];
        if (Tools::getValue('with_factory') !== false) {
            $fields_form['submit'] = [
                'title' => $this->module->l('Save', 'AdminShoppingfeedGeneralSettings'),
                'name' => 'saveFactoryConfig',
            ];
            $fields_form['input'][] = [
                'type' => 'hidden',
                'name' => 'with_factory',
            ];
        }

        $fields_value = [
            Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT => Configuration::get(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT),
            Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD => $syncByDateUpdate,
            Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE => $time_full_update,
            Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON => $interval_cron,
            Shoppingfeed::REAL_TIME_SYNCHRONIZATION => (int) Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION),
            Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS => Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS),
            Shoppingfeed::COMPRESS_PRODUCTS_FEED => (int) Configuration::get(Shoppingfeed::COMPRESS_PRODUCTS_FEED),
            'with_factory' => Tools::getValue('with_factory'),
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'shoppingfeed_general_settings/factory_form.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }

    public function renderProductSelectionConfigForm()
    {
        $tpl = Context::getContext()->smarty->createTemplate(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/shoppingfeed_general_settings/product_filter.tpl');
        $product_visibility_nowhere = (bool) Configuration::getGlobalValue(Shoppingfeed::PRODUCT_VISIBILTY_NOWHERE);

        $tpl->assign([
            'product_filters' => $this->getProductFilters(),
            'product_visibility_nowhere' => $product_visibility_nowhere,
        ]);

        return $tpl->fetch();
    }

    protected function getProductFilters()
    {
        $product_feed_rule_filters = Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS);
        if ($product_feed_rule_filters === false) {
            return [];
        }
        $product_feed_rule_filters = json_decode($product_feed_rule_filters, true);
        $product_filters = [];

        foreach ($product_feed_rule_filters as $index => $groupFilter) {
            $product_filters[$index] = [];
            foreach ($groupFilter as $filterMap) {
                $type = key($filterMap);
                $filter = $this->getFilterFactory()->getFilter($type, $filterMap[$type]);
                $product_filters[$index][] = $filter;
            }
        }

        return $product_filters;
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveFeedFilterConfig')) {
            $this->saveFilterConfig();
            $this->purgePrealoading();

            return true;
        } elseif (Tools::isSubmit('saveGlobalConfig')) {
            return $this->saveGlobalConfig();
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
     *
     * @return bool
     */
    public function saveGlobalConfig()
    {
        $stock_sync_enabled = Tools::getValue(Shoppingfeed::STOCK_SYNC_ENABLED);
        $price_sync_enabled = Tools::getValue(Shoppingfeed::PRICE_SYNC_ENABLED);

        Configuration::updateGlobalValue(Shoppingfeed::STOCK_SYNC_ENABLED, $stock_sync_enabled ? true : false);
        Configuration::updateGlobalValue(Shoppingfeed::PRICE_SYNC_ENABLED, $price_sync_enabled ? true : false);

        return true;
    }

    public function saveFilterConfig()
    {
        $product_visibility_nowhere = (bool) Tools::getValue('product_visibility_nowhere', false);
        $product_rule_select = Tools::getValue('product_rule_select', []);

        foreach ($product_rule_select as &$groupFilter) {
            foreach ($groupFilter as &$filter) {
                $filter = json_decode($filter, true);
            }
        }

        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS, json_encode($product_rule_select));
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_VISIBILTY_NOWHERE, $product_visibility_nowhere);

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
                    $handler->setConveyor([
                        'id_token' => $token['id_shoppingfeed_token'],
                        'product_action' => ShoppingfeedPreloading::ACTION_SYNC_PRELODING,
                    ]);
                    $processResult = $handler->process('shoppingfeedProductSyncPreloading');
                }
            } catch (Exception $e) {
                return true;
            }
        }
    }

    /**
     * Saves the global configuration for the module
     *
     * @return bool
     */
    public function saveFactoryConfig()
    {
        $reference_format = Tools::getValue(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT);
        $sync_by_date = Tools::getValue(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD);
        $time_full_update = Tools::getValue(Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE);
        $interval_cron = Tools::getValue(Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON);
        $realtime_sync = Tools::getValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION);
        $stock_sync_max_products = (int) Tools::getValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);
        $compressProductsFeed = (int) Tools::getValue(Shoppingfeed::COMPRESS_PRODUCTS_FEED);

        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT, $reference_format);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD, $sync_by_date);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE, $time_full_update);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON, $interval_cron);
        Configuration::updateGlobalValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, $realtime_sync ? true : false);
        Configuration::updateGlobalValue(Shoppingfeed::COMPRESS_PRODUCTS_FEED, $compressProductsFeed);

        if (!is_numeric($stock_sync_max_products) || $stock_sync_max_products > 2000 || $stock_sync_max_products <= 0) {
            $this->errors[] = $this->module->l('You must specify a \'Max. product update per request\' number (between 1 and 2000 included).', 'AdminShoppingfeedGeneralSettings');
        } else {
            Configuration::updateGlobalValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, $stock_sync_max_products);
        }

        return true;
    }

    /**
     * Saves the synchro configuration for the module
     *
     * @return bool
     */
    public function saveFeedConfig()
    {
        $sync_pack = Tools::getValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK);
        $carrierReference = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE);
        $imageFormat = Tools::getValue(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT);
        $categoryDisplay = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY);
        $customFields = Tools::getValue(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS);
        $exportWithHierarchy = Tools::getValue(Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY);

        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK, $sync_pack ? true : false);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE, $carrierReference);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT, $imageFormat);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY, $categoryDisplay);
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS, json_encode($customFields));
        Configuration::updateGlobalValue(Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY, $exportWithHierarchy);

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

        $newFields = [];

        $productCoreFields = ProductCore::$definition['fields'];
        $coreFields = [];

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
        $this->ajaxDie(json_encode(['success' => true]));
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
            'unselected' => [],
        ];
        switch ($product_rule_type) {
            case 'attributes':
                $results = Db::getInstance()->executeS('
				SELECT CONCAT(agl.name, " - ", al.name) as name, a.id_attribute as id
				FROM ' . _DB_PREFIX_ . 'attribute_group_lang agl
				LEFT JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute_group = agl.id_attribute_group
				LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = ' . (int) Context::getContext()->language->id . ')
				WHERE agl.id_lang = ' . (int) Context::getContext()->language->id . '
				ORDER BY agl.name, al.name');
                break;
            case 'products':
                $results = Db::getInstance()->executeS('
				SELECT DISTINCT name, p.id_product as id
				FROM ' . _DB_PREFIX_ . 'product p
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) Context::getContext()->language->id . Shop::addSqlRestrictionOnLang('pl') . ')
				' . Shop::addSqlAssociation('product', 'p') . '
				WHERE id_lang = ' . (int) Context::getContext()->language->id . '
				ORDER BY name');
                break;
            case 'manufacturers':
                $results = Db::getInstance()->executeS('
				SELECT name, id_manufacturer as id
				FROM ' . _DB_PREFIX_ . 'manufacturer
				ORDER BY name');
                break;
            case 'suppliers':
                $results = Db::getInstance()->executeS('
				SELECT name, id_supplier as id
				FROM ' . _DB_PREFIX_ . 'supplier
				ORDER BY name');
                break;
            case 'categories':
                $results = Db::getInstance()->executeS('
				SELECT DISTINCT name, c.id_category as id
				FROM ' . _DB_PREFIX_ . 'category c
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON (c.`id_category` = cl.`id_category`
					AND cl.`id_lang` = ' . (int) Context::getContext()->language->id . Shop::addSqlRestrictionOnLang('cl') . ')
				' . Shop::addSqlAssociation('category', 'c') . '
				WHERE id_lang = ' . (int) Context::getContext()->language->id . '
				ORDER BY name');
                break;
            case 'features':
                $results = Db::getInstance()->executeS('
                SELECT DISTINCT name, f.id_feature as id
                FROM ' . _DB_PREFIX_ . 'feature f
                LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                    ON (f.`id_feature` = fl.`id_feature`)
                ' . Shop::addSqlAssociation('feature', 'f') . '
                WHERE id_lang = ' . (int) Context::getContext()->language->id . '
                ORDER BY name');
                break;
            default:
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

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        if (Tools::getValue('with_factory') !== false) {
            $this->addJS(_PS_MODULE_DIR_ . 'shoppingfeed/views/js/general_settings/general_settings.js');
        }

        $this->addJS(_PS_MODULE_DIR_ . 'shoppingfeed/views/js/general_settings/RuleConditionGenerator.js');
    }

    public function displayAjaxGetCategoryList()
    {
        $query = (new DbQuery())
            ->from('category_lang', 'cl')
            ->leftJoin('category', 'c', 'cl.id_category = c.id_category')
            ->where('cl.id_lang = ' . (int) $this->context->language->id)
            ->where('c.id_parent <> 0')
            ->where('cl.name <> ""')
            ->where('cl.name IS NOT NULL')
            ->orderBy('c.id_category ASC')
            ->groupBy('cl.id_category')
            ->select('c.id_category as id, CONCAT("(", c.id_category, ")", " ", cl.name) as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetBrandList()
    {
        $query = (new DbQuery())
            ->from('manufacturer')
            ->orderBy('id_manufacturer ASC')
            ->select('id_manufacturer as id, name as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetSupplierList()
    {
        $query = (new DbQuery())
            ->from('supplier')
            ->orderBy('id_supplier ASC')
            ->select('id_supplier as id, name as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetAttributeGroupList()
    {
        $query = (new DbQuery())
            ->from('attribute_group_lang')
            ->where('id_lang = ' . (int) $this->context->language->id)
            ->orderBy('id_attribute_group ASC')
            ->select('id_attribute_group as id, name as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetAttributeList()
    {
        $query = (new DbQuery())
            ->from('attribute_lang', 'al')
            ->leftJoin('attribute', 'a', 'al.id_attribute = a.id_attribute')
            ->where('al.id_lang = ' . (int) $this->context->language->id)
            ->where('a.id_attribute_group = ' . (int) Tools::getValue('id_group'))
            ->orderBy('a.id_attribute ASC')
            ->select('a.id_attribute as id, al.name as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetFeatureList()
    {
        $query = (new DbQuery())
            ->from('feature_lang')
            ->where('id_lang = ' . (int) $this->context->language->id)
            ->orderBy('id_feature ASC')
            ->select('id_feature as id, name as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    public function displayAjaxGetFeatureValueList()
    {
        $query = (new DbQuery())
            ->from('feature_value_lang', 'fvl')
            ->leftJoin('feature_value', 'fv', 'fv.id_feature_value = fvl.id_feature_value')
            ->where('fvl.id_lang = ' . (int) $this->context->language->id)
            ->where('fv.id_feature = ' . (int) Tools::getValue('id_feature'))
            ->orderBy('fv.id_feature ASC')
            ->select('fv.id_feature_value as id, fvl.value as title');

        exit(json_encode(Db::getInstance()->executeS($query)));
    }

    protected function getFilterFactory()
    {
        return new FilterFactory();
    }
}
