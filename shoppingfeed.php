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

// Set this as comment so Classlib will import the files; but don't uncomment !
// Installation will fail on PS 1.6 if "use" statements are in the main module file

// use ShoppingfeedClasslib\Module;
// use ShoppingfeedClasslib\Actions\ActionsHandler;
// use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
// use ShoppingfeedClasslib\Registry;
// use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerExtension;
// use ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorExtension;

/**
 * The base module class
 */
class Shoppingfeed extends \ShoppingfeedClasslib\Module
{
    /**
     * This module requires at least PHP version
     *
     * @var string
     */
    public $php_version_required = '5.6';

    const AUTH_TOKEN = 'SHOPPINGFEED_AUTH_TOKEN';
    const STOCK_SYNC_ENABLED = 'SHOPPINGFEED_STOCK_SYNC_ENABLED';
    const PRICE_SYNC_ENABLED = 'SHOPPINGFEED_PRICE_SYNC_ENABLED';
    const ORDER_SYNC_ENABLED = 'SHOPPINGFEED_ORDER_SYNC_ENABLED';
    const STOCK_SYNC_MAX_PRODUCTS = 'SHOPPINGFEED_STOCK_SYNC_MAX_PRODUCTS';
    const REAL_TIME_SYNCHRONIZATION = 'SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION';
    const LAST_CRON_TIME_SYNCHRONIZATION = 'SHOPPINGFEED_LAST_CRON_TIME_SYNCHRONIZATION';
    const ORDER_STATUS_TIME_SHIFT = 'SHOPPINGFEED_ORDER_STATUS_TIME_SHIFT';
    const ORDER_STATUS_MAX_ORDERS = 'SHOPPINGFEED_ORDER_STATUS_MAX_ORDERS';
    const SHIPPED_ORDERS = 'SHOPPINGFEED_SHIPPED_ORDERS';
    const CANCELLED_ORDERS = 'SHOPPINGFEED_CANCELLED_ORDERS';
    const REFUNDED_ORDERS = 'SHOPPINGFEED_REFUNDED_ORDERS';
    const ORDER_IMPORT_ENABLED = 'SHOPPINGFEED_ORDER_IMPORT_ENABLED';
    const ORDER_IMPORT_TEST = 'SHOPPINGFEED_ORDER_IMPORT_TEST';
    const ORDER_IMPORT_SHIPPED = 'SHOPPINGFEED_ORDER_IMPORT_SHIPPED';
    const ORDER_IMPORT_SHIPPED_MARKETPLACE = 'SHOPPINGFEED_ORDER_IMPORT_SHIPPED_MARKETPLACE';
    const ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION = 'SHOPPINGFEED_ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION';
    const ORDER_DEFAULT_CARRIER_REFERENCE = 'SHOPPINGFEED_ORDER_DEFAULT_CARRIER_REFERENCE';
    const PRODUCT_FEED_CARRIER_REFERENCE = 'SHOPPINGFEED_PRODUCT_FEED_CARRIER_REFERENCE';
    const PRODUCT_FEED_SYNC_PACK = 'SHOPPINGFEED_PRODUCT_FEED_SYNC_PACK';
    const PRODUCT_FEED_IMAGE_FORMAT = 'SHOPPINGFEED_PRODUCT_FEED_IMAGE_FORMAT';
    const PRODUCT_FEED_CATEGORY_DISPLAY = 'SHOPPINGFEED_PRODUCT_FEED_CATEGORY_DISPLAY';
    const PRODUCT_FEED_CUSTOM_FIELDS = 'SHOPPINGFEED_PRODUCT_FEED_CUSTOM_FIELDS';
    const PRODUCT_FEED_REFERENCE_FORMAT = 'SHOPPINGFEED_PRODUCT_FEED_REFERENCE_FORMAT';
    const PRODUCT_FEED_RULE_FILTERS = 'SHOPPINGFEED_PRODUCT_FEED_RULE_FILTERS';
    const PRODUCT_VISIBILTY_NOWHERE = 'SHOPPINGFEED_PRODUCT_VISIBILTY_NOWHERE';
    const PRODUCT_SYNC_BY_DATE_UPD = 'SHOPPINGFEED_PRODUCT_SYNC_BY_DATE_UPD';
    const PRODUCT_FEED_TIME_FULL_UPDATE = 'SHOPPINGFEED_PRODUCT_FEED_TIME_FULL_UPDATE';
    const PRODUCT_FEED_INTERVAL_CRON = 'SHOPPINGFEED_PRODUCT_FEED_INTERVAL_CRON';
    const ORDER_IMPORT_PERMANENT_SINCE_DATE = 'SHOPPINGFEED_ORDER_IMPORT_PERMANENT_SINCE_DATE';

    public $extensions = [
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerExtension::class,
        \ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorExtension::class,
    ];

    /**
     * List of objectModel used in this Module
     *
     * @var array
     */
    public $objectModels = [
        ShoppingfeedTaskOrder::class,
        ShoppingfeedProduct::class,
        ShoppingfeedOrder::class,
        ShoppingfeedCarrier::class,
        ShoppingfeedPreloading::class,
        ShoppingfeedToken::class,
    ];

    /**
     * List of cron tasks indexed by controller name
     * Title value must be an array indexed by iso language (en is required)
     * Frequency value can be hourly, daily, weekly, monthly
     *
     * @var array
     */
    public $cronTasks = [
        'syncProduct' => [
            'name' => 'shoppingfeed:syncProduct',
            'title' => [
                'en' => 'Synchronize products on Shopping Feed',
                'fr' => 'Synchronisation des produits sur Shopping Feed',
            ],
            'frequency' => '5min',
        ],
        'syncOrder' => [
            'name' => 'shoppingfeed:syncOrder',
            'title' => [
                'en' => 'Synchronize orders on Shopping Feed',
                'fr' => 'Synchronisation des commandes sur Shopping Feed',
            ],
            'frequency' => '5min',
        ],
        'syncAll' => [
            'name' => 'shoppingfeed:syncAll',
            'title' => [
                'en' => 'Sync shoppingfeed products and orders',
                'fr' => 'Sync produits et commandes shoppingfeed',
            ],
            'frequency' => '5min',
        ],
    ];

    /** @var array
     */
    public $moduleAdminControllers = [
        [
            'name' => [
                'en' => 'Shopping Feed',
                'fr' => 'Shopping Feed',
            ],
            'class_name' => 'shoppingfeed',
            'parent_class_name' => 'SELL',
            'icon' => 'store_mall_directory',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Marketplaces Summary',
                'fr' => 'Commandes Marketplaces',
            ],
            'class_name' => 'AdminShoppingfeedOrders',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Shopping Feed',
                'fr' => 'Shopping Feed',
            ],
            'class_name' => 'AdminShoppingfeedOrderImport',
            'parent_class_name' => 'shoppingfeed',
            'visible' => false,
        ],
        [
            'name' => [
                'en' => 'Settings',
                'fr' => 'Paramètres',
            ],
            'class_name' => 'AdminShoppingfeedSettings',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Account settings',
                'fr' => 'Paramètres du compte',
            ],
            'class_name' => 'AdminShoppingfeedAccountSettings',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Products feed',
                'fr' => 'Flux des produits',
            ],
            'class_name' => 'AdminShoppingfeedGeneralSettings',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Orders feeds',
                'fr' => 'Flux des commandes',
            ],
            'class_name' => 'AdminShoppingfeedOrderImportRules',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Logs & crons',
                'fr' => 'Logs & crons',
            ],
            'class_name' => 'AdminShoppingfeedProcess',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Logs',
                'fr' => 'Logs',
            ],
            'class_name' => 'AdminShoppingfeedProcessLogger',
            'parent_class_name' => 'AdminShoppingfeedProcess',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'Cron Tasks',
                'fr' => 'Tâches cron',
            ],
            'class_name' => 'AdminShoppingfeedProcessMonitor',
            'parent_class_name' => 'AdminShoppingfeedProcess',
            'visible' => true,
        ],
        [
            'name' => [
                'en' => 'FAQ/Help',
                'fr' => 'FAQ/Aide',
            ],
            'class_name' => 'AdminShoppingfeedFaq',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ],
    ];

    /**
     * List of ModuleFrontController used in this Module
     * Module::install() register it, after that you can edit it in BO (for rewrite if needed)
     *
     * @var array
     */
    public $controllers = [
        'syncProduct',
        'syncOrder',
    ];

    /**
     * List of hooks used in this Module
     *
     * @var array
     */
    public $hooks = [
        'actionUpdateQuantity',
        'actionObjectProductUpdateBefore',
        'actionObjectCombinationUpdateBefore',
        'actionObjectProductUpdateAfter',
        'actionObjectCombinationUpdateAfter',
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
        'actionShoppingfeedOrderImportRegisterSpecificRules',
        'actionObjectProductDeleteBefore',
        'ActionObjectCategoryUpdateAfter',
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'actionObjectSpecificPriceDeleteAfter',
        'deleteProductAttribute',
        'actionAdminSpecificPriceRuleControllerDeleteBefore',
    ];

    /**
     * Used to avoid spam or unauthorized execution of cron controller
     *
     * @var string Unique token depend on _COOKIE_KEY_ which is unique to this website
     *
     * @see Tools::encrypt()
     */
    public $secure_key;

    /**
     * creates an instance of the module
     * Shoppingfeed constructor.
     */
    public function __construct()
    {
        $this->name = 'shoppingfeed';
        $this->version = '@version@';
        $this->author = '202 ecommerce';
        $this->tab = 'market_place';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.7'];
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Shoppingfeed Prestashop Plugin (Feed&Order)');
        $this->description = $this->l('Improve your Shopping Feed module\'s  Marketplaces stocks synchronization speed.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->secure_key = Tools::encrypt($this->name);
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->moduleAdminControllers = [
                [
                    'name' => [
                        'en' => 'Shopping Feed',
                        'fr' => 'Shopping Feed',
                    ],
                    'class_name' => 'shoppingfeed',
                    'icon' => 'AdminParentOrders',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Marketplaces Summary',
                        'fr' => 'Commandes Marketplaces',
                    ],
                    'class_name' => 'AdminShoppingfeedOrders',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Shopping Feed',
                        'fr' => 'Shopping Feed',
                    ],
                    'class_name' => 'AdminShoppingfeedOrderImport',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => false,
                ],
                [
                    'name' => [
                        'en' => 'Account settings',
                        'fr' => 'Paramètres du compte',
                    ],
                    'class_name' => 'AdminShoppingfeedAccountSettings',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Products feed',
                        'fr' => 'Flux des produits',
                    ],
                    'class_name' => 'AdminShoppingfeedGeneralSettings',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Orders feeds',
                        'fr' => 'Flux des commandes',
                    ],
                    'class_name' => 'AdminShoppingfeedOrderImportRules',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Logs',
                        'fr' => 'Logs',
                    ],
                    'class_name' => 'AdminShoppingfeedProcessLogger',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'Cron Tasks',
                        'fr' => 'Tâches cron',
                    ],
                    'class_name' => 'AdminShoppingfeedProcessMonitor',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
                [
                    'name' => [
                        'en' => 'FAQ/Help',
                        'fr' => 'FAQ/Aide',
                    ],
                    'class_name' => 'AdminShoppingfeedFaq',
                    'parent_class_name' => 'shoppingfeed',
                    'visible' => true,
                ],
            ];
        }
    }

    /**
     * Installs the module; see the parent ShoppingfeedModule class from classlib
     *
     * @return bool
     */
    public function install()
    {
        $res = parent::install();
        $res &= $this->addDateIndexToLogs();

        $this->setConfigurationDefault(self::STOCK_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::PRICE_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::ORDER_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::STOCK_SYNC_MAX_PRODUCTS, 100);
        $this->setConfigurationDefault(self::REAL_TIME_SYNCHRONIZATION, false);
        $this->setConfigurationDefault(self::ORDER_STATUS_TIME_SHIFT, 5);
        $this->setConfigurationDefault(self::ORDER_STATUS_MAX_ORDERS, 100);
        $this->setConfigurationDefault(self::SHIPPED_ORDERS, json_encode([]));
        $this->setConfigurationDefault(self::CANCELLED_ORDERS, json_encode([]));
        $this->setConfigurationDefault(self::REFUNDED_ORDERS, json_encode([]));
        $this->setConfigurationDefault(self::ORDER_IMPORT_ENABLED, true);
        $this->setConfigurationDefault(self::ORDER_IMPORT_SHIPPED, false);
        $this->setConfigurationDefault(self::PRODUCT_FEED_CARRIER_REFERENCE, Configuration::getGlobalValue('PS_CARRIER_DEFAULT'));
        $this->setConfigurationDefault(self::ORDER_DEFAULT_CARRIER_REFERENCE, Configuration::getGlobalValue('PS_CARRIER_DEFAULT'));
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->setConfigurationDefault(self::PRODUCT_FEED_IMAGE_FORMAT, ImageType::getFormatedName('large'));
        } else {
            $this->setConfigurationDefault(self::PRODUCT_FEED_IMAGE_FORMAT, ImageType::getFormattedName('large'));
        }
        $this->saveToken();

        return $res;
    }

    private function saveToken()
    {
        $sfToken = new ShoppingfeedToken();
        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            $tokenConfig = Configuration::get('SHOPPING_FLUX_TOKEN', null, null, $shop['id_shop']);
            if ($tokenConfig === false) {
                continue;
            }
            $tokenTable = $sfToken->findByToken($tokenConfig);
            if ($tokenTable !== false) {
                continue;
            }
            $sfToken->addToken(
                $shop['id_shop'],
                Configuration::get('PS_LANG_DEFAULT', null, null, $shop['id_shop']),
                Configuration::get('PS_CURRENCY_DEFAULT', null, null, $shop['id_shop']),
                $tokenConfig
            );
        }
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Desactivate current module. Hide module admin Tab.
     *
     * @param bool $force_all If true, disable module for all shop
     */
    public function disable($force_all = false)
    {
        if (parent::disable($force_all) === false && version_compare(_PS_VERSION_, '1.7', '>=')) {
            // On pS1.6, Module::disable() always returns false
            return false;
        }

        $tab = Tab::getInstanceFromClassName('shoppingfeed');
        if ($tab->id == null) {
            return true;
        }
        $tab->active = 0;
        $tab->save();

        return true;
    }

    /**
     * Activate current module. Active module admin Tab.
     *
     * @param bool $force_all If true, enable module for all shop
     */
    public function enable($force_all = false)
    {
        if (parent::enable($force_all) === false) {
            return false;
        }

        $tab = Tab::getInstanceFromClassName('shoppingfeed');
        if ($tab->id == null) {
            return true;
        }
        $tab->active = 1;
        $tab->save();

        return true;
    }

    public function setConfigurationDefault($key, $defaultValue)
    {
        if (!Configuration::hasKey($key)) {
            Configuration::updateGlobalValue($key, $defaultValue);
        }
    }

    /**
     * Breaking changes (e.g. deprecation) notice should be set here; get the
     * controller with $this->context->controller and set the messages
     */
    public function setBreakingChangesNotices()
    {
    }

    /**
     * @return bool
     */
    public static function isOrderSyncAvailable($id_shop = null)
    {
        // Is the old module installed ?
        if (Module::isInstalled('shoppingfluxexport')
            && Module::isEnabled('shoppingfluxexport')
            && (
                // Is order "shipped" status sync disabled in the old module ?
                Configuration::get('SHOPPING_FLUX_STATUS_SHIPPED', null, null, $id_shop) != ''
                // Is order "canceled" status sync disabled in the old module ?
                || Configuration::get('SHOPPING_FLUX_STATUS_CANCELED', null, null, $id_shop) != ''
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks if order import can be activated
     *
     * @return bool
     */
    public static function isOrderImportAvailable($id_shop = null)
    {
        // Is the old module installed ?
        if (Module::isInstalled('shoppingfluxexport')
            && Module::isEnabled('shoppingfluxexport')
            && (
                // Is order import disabled in the old module ?
                Configuration::get('SHOPPING_FLUX_ORDERS', null, null, $id_shop) != ''
               )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Redirects the user to our AdminController for configuration
     *
     * @throws PrestaShopException
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminShoppingfeedAccountSettings')
        );
    }

    /**
     * Returns the product's Shopping Feed reference. The developer can skip
     * products to sync by overriding this method and have it return false.
     *
     * @param ShoppingFeedProduct $sfProduct
     * @param array $arguments Should you want to pass more arguments to this
     *                         function, you can find them in this array
     *
     * @return string
     */
    public function mapReference(ShoppingfeedProduct $sfProduct, ...$arguments)
    {
        $reference = $sfProduct->getShoppingfeedReference();

        Hook::exec(
            'ShoppingfeedMapProductReference', // hook_name
            [
                'ShoppingFeedProduct' => &$sfProduct,
                'reference' => &$reference,
            ] // hook_args
        );

        return $reference;
    }

    /**
     * Returns the Prestashop product matching the Shopping Feed reference. The
     * developer can skip specific products during order import by overriding
     * this method and have it return false.
     *
     * @param string $sfProductReference The product's reference in Shopping Feed's system
     * @param string $id_shop
     * @param array $arguments Should you want to pass more arguments to this
     *                         function, you can find them in this array
     *
     * @return array
     */
    public function mapPrestashopProduct($sfProductReference, $id_shop, ...$arguments)
    {
        $sfProduct = new ShoppingfeedProduct();
        $sfProductReference = $sfProduct->getReverseShoppingfeedReference($sfProductReference);

        Hook::exec(
            'ShoppingfeedReverseProductReference', // hook_name
            [
                'sfProductReference' => &$sfProductReference,
            ] // hook_args
        );

        $explodedReference = explode('_', $sfProductReference);
        $id_product = isset($explodedReference[0]) ? $explodedReference[0] : null;

        $product = new Product($id_product, true, null, $id_shop);
        $product->id_product_attribute = null;
        if (isset($explodedReference[1])) {
            $product->id_product_attribute = $explodedReference[1];
        }

        Hook::exec(
            'ShoppingfeedMapProduct', // hook_name
            [
                'sfProductReference' => &$sfProductReference,
                'product' => &$product,
            ] // hook_args
        );

        return $product;
    }

    /**
     * Returns the product's price sent to the Shopping Feed API. The developer
     * can skip products to sync by overriding this method and have it return
     * false. Note that the comparison with the return value is strict to allow
     * "0" as a valid price.
     *
     * @param ShoppingFeedProduct $sfProduct
     * @param int $id_shop
     * @param array $arguments Should you want to pass more arguments to this
     *                         function, you can find them in this array
     *
     * @return string
     */
    public function mapProductPrice(ShoppingfeedProduct $sfProduct, $id_shop, $arguments = [])
    {
        $specific_price_output = null;
        Product::flushPriceCache();

        // Tax depends on a country. We use country configured as default one for shop.
        $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, null, $id_shop);
        $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $id_shop);
        $id_group = (int) Group::getCurrent()->id;

        $price = Product::priceCalculation(
            $id_shop,
            $sfProduct->id_product,
            $sfProduct->id_product_attribute ? // id_product_attribute
                $sfProduct->id_product_attribute : null,
            $id_country,
            0,// id_state
            0,// postcode
            $id_currency,// id_currency
            $id_group,// id_group
            1,// quantity
            true,// use_tax
            2,// decimals
            false,// only_reduc
            is_array($arguments) && array_key_exists('price_with_reduction', $arguments) && $arguments['price_with_reduction'] === true, // usereduc
            true,// with_ecotax
            $specific_price_output,// specific_price_output
            true,// use_group_reduction
            0,// id_customer
            true,// use_customer_price
            0,// id_cart
            0,// real_quantity
            0//id_customization
        );

        Hook::exec(
            'ShoppingfeedMapProductPrice', // hook_name
            [
                'ShoppingFeedProduct' => &$sfProduct,
                'price' => &$price,
                'arguments' => $arguments,
            ] // hook_args
        );

        return $price;
    }

    /**
     * Returns the number of product in the feed The developer
     * can skip products to sync so it's necessary to ajust the number of
     * products in order to manage indexation.
     *
     * @return int
     */
    public function countProductsOnFeed($id_shop = null)
    {
        $sql = $this->sqlProductsOnFeed($id_shop);
        $sql->select('COUNT(p.`id_product`)');
        $countProductsOnFeed = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        return $countProductsOnFeed;
    }

    /**
     * Returns the
     *
     * @return DbQuery
     */
    public function sqlProductsOnFeed($id_shop = null)
    {
        $sql = new DbQuery();
        $sql->from(Product::$definition['table'] . '_shop', 'ps')
            ->leftJoin(Product::$definition['table'], 'p', 'p.id_product = ps.id_product')
            ->where('ps.active = 1')
            ->where('ps.available_for_order = 1');

        if ($id_shop === null) {
            $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        $sql->where('ps.id_shop = ' . (int) $id_shop);
        $product_feed_rule_filters = Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_RULE_FILTERS);
        $product_visibility_nowhere = (bool) Configuration::getGlobalValue(Shoppingfeed::PRODUCT_VISIBILTY_NOWHERE);
        $product_filters = Tools::jsonDecode($product_feed_rule_filters, true);
        $sqlFilter = [];
        if (is_array($product_filters)) {
            foreach ($product_filters as $product_filter_type => $product_filter) {
                switch ($product_filter_type) {
                    case 'products':
                        $sqlFilter[] = 'ps.id_product IN (' . $product_filter . ')';
                        continue 2;
                    case 'attributes':
                        $sqlFilter[] = 'ps.id_product IN (select id_product from ' . _DB_PREFIX_ . 'product_attribute pa JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac on pa.id_product_attribute = pac.id_product_attribute where pac.id_attribute IN (' . $product_filter . '))';
                        continue 2;
                    case 'manufacturers':
                        $sqlFilter[] = 'ps.id_product IN (select id_product from ' . _DB_PREFIX_ . 'product where id_manufacturer IN (' . $product_filter . '))';
                        continue 2;
                    case 'categories':
                        $sqlFilter[] = 'ps.id_category_default IN (' . $product_filter . ')';
                        continue 2;
                    case 'suppliers':
                        $sqlFilter[] = 'ps.id_product IN (select id_product from ' . _DB_PREFIX_ . 'product_supplier where id_supplier IN (' . $product_filter . '))';
                        continue 2;
                    case 'features':
                        $sqlFilter[] = 'ps.id_product IN (select id_product from ' . _DB_PREFIX_ . 'feature_product where id_feature IN (' . $product_filter . '))';
                        continue 2;
                    default:
                        continue 2;
                }
            }
        }
        if (count($sqlFilter) > 0) {
            $sql->where('(' . implode(' or ', $sqlFilter) . ')');
        }
        if ((bool) Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_SYNC_PACK) !== true) {
            $sql->where('p.cache_is_pack = 0');
        }
        if ($product_visibility_nowhere === false) {
            $sql->where("p.visibility != 'none'");
        }
        Hook::exec('ShoppingfeedSqlProductsOnFeed',
            [
                'id_shop' => $id_shop,
                'sql' => &$sql,
            ]
        );

        return $sql;
    }

    /****************************** Stock hook *******************************/

    /**
     * Saves a product for stock synchronization, or synchronizes it directly
     * using the Actions handler
     *
     * @param array $params The hook parameters
     *
     * @throws Exception
     */
    public function hookActionUpdateQuantity($params)
    {
        $this->updateShoppingFeedPreloading([$params['id_product']], ShoppingfeedPreloading::ACTION_SYNC_STOCK);
        if (!Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED)) {
            return;
        }

        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];

        try {
            /** @var ShoppingfeedHandler $handler */
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor([
                    'id_product' => $id_product,
                    'id_product_attribute' => $id_product_attribute,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_STOCK,
                ])
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncStock');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->l('Product %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
                    $id_product . ($id_product_attribute ? '_' . $id_product_attribute : ''),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Product',
                $id_product
            );
        }

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /****************************** Prices hooks ******************************/

    /* We'll have to check for products updates and combinations updates.
     * For each object, we'll use the "UpdateBefore" hooks.
     * We won't check the "Add" and "Delete" hooks, since the export module
     * should export the first prices and the deletion from the catalog.
     * If we're using realtime sync, the SF call will be done in the
     * "UpdateAfter" hooks, otherwise we'll send non-updated values.
     */

    /**
     * Compares an updated product's price with its old price. If the new price
     * is different, saves the product for price synchronization.
     *
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductUpdateBefore($params)
    {
        if (!Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED)) {
            return;
        }

        $product = $params['object'];
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        // Retrieve previous values in DB
        // If all goes well, they should already be cached...
        $old_product = new Product($product->id);
        if ((float) $old_product->price == (float) $product->price) {
            return;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor([
                    'id_product' => $product->id,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ])
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncPrice');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->l('Product %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
                    $product->id,
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Product',
                $product->id
            );
        }

        if (!\ShoppingfeedClasslib\Registry::isRegistered('updated_product_prices_ids')) {
            \ShoppingfeedClasslib\Registry::set('updated_product_prices_ids', []);
        }
        $updatedProductPricesIds = \ShoppingfeedClasslib\Registry::get('updated_product_prices_ids');
        $updatedProductPricesIds[] = $product->id;
        \ShoppingfeedClasslib\Registry::set('updated_product_prices_ids', $updatedProductPricesIds);

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();

        // Combinations hook are not called when saving the product on 1.6
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $attributes = $product->getAttributesResume(Context::getContext()->language->id);

            if (is_array($attributes) && false == empty($attributes)) {
                foreach ($attributes as $attribute) {
                    $this->hookActionObjectCombinationUpdateBefore([
                        'object' => new Combination($attribute['id_product_attribute']),
                    ]);
                }
            }
        }
    }

    /**
     * Compares an updated combinations's price with its old price. If the new
     * price is different, saves the combination for price synchronization.
     *
     * @param array $params The hook parameters
     */
    public function hookActionObjectCombinationUpdateBefore($params)
    {
        if (!Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED)) {
            return;
        }

        $combination = $params['object'];
        if (!Validate::isLoadedObject($combination)) {
            return;
        }

        // Retrieve previous values in DB
        // If all goes well, they should already be cached...
        $old_combination = new Combination($combination->id);
        if ((float) $old_combination->price == (float) $combination->price &&
            (
                !\ShoppingfeedClasslib\Registry::isRegistered('updated_product_prices_ids') ||
                !in_array($combination->id_product, \ShoppingfeedClasslib\Registry::get('updated_product_prices_ids'))
            )) {
            return;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor([
                    'id_product' => $combination->id_product,
                    'id_product_attribute' => $combination->id,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ])
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncPrice');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->l('Combination %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
                    $combination->id_product . ($combination->id ? '_' . $combination->id : ''),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Product',
                $combination->id
            );
        }

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Delete a product on SF
     *
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductDeleteBefore($params)
    {
        $product = $params['object'];
        if (!Validate::isLoadedObject($product)) {
            return false;
        }
        $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
        $handler->addActions('deleteProduct');
        try {
            $handler->setConveyor([
                'product' => $product,
                'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
            ]);
            $processResult = $handler->process('ShoppingfeedProductSyncPreloading');
            if (!$processResult) {
                \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                    $this->l('Fail : An error occurred during process.')
                );
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    $this->l('Fail : %s'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Updates a product on SF if realtime sync is enabled.
     * On PS1.6, it should also update the product's combinations if needed.
     *
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
        $this->updateShoppingFeedPreloading([$params['object']->id], ShoppingfeedPreloading::ACTION_SYNC_ALL);
        $this->updateShoppingFeedPriceRealtime();
    }

    /**
     * Updates a combination on SF if realtime sync is enabled.
     *
     * @param array $params The hook parameters
     */
    public function hookActionObjectCombinationUpdateAfter($params)
    {
        $this->updateShoppingFeedPriceRealtime();
    }

    /**
     * Processes saved price updates if realtime sync is enabled.
     */
    public function updateShoppingFeedPriceRealtime()
    {
        if (!Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED)) {
            return;
        }
        if ((bool) Configuration::getGlobalValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION) === false) {
            return;
        }
        $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
        $handler->addActions('getBatch');
        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        try {
            foreach ($tokens as $token) {
                $handler->setConveyor([
                    'id_token' => $token['id_shoppingfeed_token'],
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ]);

                $processResult = $handler->process('shoppingfeedProductSyncPrice');
                if (!$processResult) {
                    \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                        ShoppingfeedProductSyncPriceActions::getLogPrefix($token['id_shoppingfeed_token']) . ' ' . $this->l('Fail : An error occurred during process.')
                    );
                }
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedProductSyncPriceActions::getLogPrefix($token['id_shoppingfeed_token']) . ' ' . $this->l('Fail : %s'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    public function hookActionObjectCategoryUpdateAfter($params)
    {
        $category = $params['object'];
        $categoryIds = [];
        foreach ($category->getAllParents() as $parent) {
            $categoryIds[] = (int) $parent->id;
        }
        $categoryIds[] = (int) $category->id;
        foreach ($category->getAllChildren() as $children) {
            $categoryIds[] = (int) $children->id;
        }
        $products = $this->getProductsByCategoryIds($categoryIds);

        if (empty($products) === false) {
            $this->updateShoppingFeedPreloading($products, ShoppingfeedPreloading::ACTION_SYNC_CATEGORY);
        }
    }

    private function getProductsByCategoryIds($categoryIds)
    {
        $products = [];
        $sql = new DbQuery();
        $sql->from(Product::$definition['table'])
            ->where('id_category_default in(' . implode(',', $categoryIds) . ')');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return $result === [] ? [] : array_column($result, 'id_product');
    }

    /**
     * Processes products to indexed on add into XML feed.
     */
    public function updateShoppingFeedPreloading($products_id, $action)
    {
        $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
        if (in_array(0, $products_id, true)) {
            $action = 'purge';
        } else {
            $action = 'saveProduct';
        }

        try {
            $processResult = $handler
                        ->addActions($action)
                        ->setConveyor(
                            [
                                'products_id' => $products_id,
                                'product_action' => $action,
                            ]
                        )
                        ->process('ShoppingfeedProductSyncPreloading');
            if (!$processResult) {
                \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                    $this->l('Fail : An error occurred during process.')
                );
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    $this->l('Fail : %s'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /****************************** Order status hooks ******************************/

    /**
     * This hook is used to "record" SF orders imported using the old module.
     *
     * @param type array
     *
     * @return void
     */
    public function hookActionValidateOrder($params)
    {
        $handler = \ShoppingfeedClasslib\Registry::get('shoppingfeedOrderImportHandler');
        if ($handler === false) {
            return;
        }
        $currentOrder = $params['order'];

        // Only process orders added via the shoppingflux module
        if ($currentOrder->module != 'sfpayment') {
            return;
        }
        try {
            $handler->addActions(
                'createSfOrder',
                'acknowledgeOrder',
                'recalculateOrderPrices'
            );
            $conveyor = $handler->getConveyor();
            $conveyor['id_order'] = $currentOrder->id;
            $conveyor['order_reference'] = $currentOrder->reference;
            $conveyor['psOrder'] = $currentOrder;
            $handler->setConveyor($conveyor);
            $processResult = $handler->process('shoppingfeedOrderImport');
            $conveyor = $handler->getConveyor();
            $params['order'] = $conveyor['psOrder'];
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedOrderSyncActions::getLogPrefix() . ' ' .
                        $this->l('Order %s not imported : %s', 'ShoppingfeedOrderActions'),
                        $currentOrder->id,
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Order',
                $currentOrder->id
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Saves an order for status synchronization
     *
     * @param type $params
     *
     * @return type
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED) || !self::isOrderSyncAvailable()) {
            return;
        }

        $shoppingFeedOrder = ShoppingfeedOrder::getByIdOrder($params['id_order']);
        if (!Validate::isLoadedObject($shoppingFeedOrder)) {
            return;
        }

        $order = new Order($params['id_order']);

        // Check if the new status calls for an update with Shopping Feed
        $newOrderStatus = $params['newOrderStatus'];
        $shipped_status = json_decode(Configuration::get(Shoppingfeed::SHIPPED_ORDERS, null, null, $order->id_shop));
        $cancelled_status = json_decode(Configuration::get(Shoppingfeed::CANCELLED_ORDERS, null, null, $order->id_shop));
        $refunded_status = json_decode(Configuration::get(Shoppingfeed::REFUNDED_ORDERS, null, null, $order->id_shop));
        if (!in_array($newOrderStatus->id, $shipped_status)
            && !in_array($newOrderStatus->id, $cancelled_status)
            && !in_array($newOrderStatus->id, $refunded_status)
        ) {
            return;
        }

        $logPrefix = ShoppingfeedOrderSyncActions::getLogPrefix($shoppingFeedOrder->id_order);
        try {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix . ' ' .
                        $this->l('Process started Order %s ', 'ShoppingfeedOrderActions'),
                    $shoppingFeedOrder->id_order
                ),
                'Order',
                $shoppingFeedOrder->id_order
            );
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor([
                    'id_order' => $params['id_order'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                ])
                ->addActions('saveTaskOrder')
                ->process('shoppingfeedOrderSync');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix . ' ' . $this->l('Order %s not registered for synchronization: %s', 'ShoppingfeedOrderActions'),
                    $params['id_order'],
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Order',
                $params['id_order']
            );
        }

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Adds the order import specific rules to the manager.
     * Add, remove or extend an order import rule ! Use this hook to declare
     * your own behaviour.
     *
     * @param array $params
     */
    public function hookActionShoppingfeedOrderImportRegisterSpecificRules($params)
    {
        $defaultRulesClassNames = [
            ShoppingfeedAddon\OrderImport\Rules\AmazonB2B::class,
            ShoppingfeedAddon\OrderImport\Rules\AmazonEbay::class,
            ShoppingfeedAddon\OrderImport\Rules\AmazonPrime::class,
            ShoppingfeedAddon\OrderImport\Rules\Cdiscount::class,
            ShoppingfeedAddon\OrderImport\Rules\CdiscountRelay::class,
            ShoppingfeedAddon\OrderImport\Rules\Mondialrelay::class,
            ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay::class,
            ShoppingfeedAddon\OrderImport\Rules\Socolissimo::class,
            ShoppingfeedAddon\OrderImport\Rules\ChangeStateOrder::class,
            ShoppingfeedAddon\OrderImport\Rules\ShippedByMarketplace::class,
            ShoppingfeedAddon\OrderImport\Rules\RelaisColisRule::class,
            ShoppingfeedAddon\OrderImport\Rules\TestingOrder::class,
            ShoppingfeedAddon\OrderImport\Rules\SymbolConformity::class,
            ShoppingfeedAddon\OrderImport\Rules\ManomanoDpdRelais::class,
            ShoppingfeedAddon\OrderImport\Rules\ZalandoColissimo::class,
        ];

        foreach ($defaultRulesClassNames as $ruleClassName) {
            $params['specificRulesClassNames'][] = $ruleClassName;
        }
    }

    public function hookActionObjectSpecificPriceAddAfter($params)
    {
        $this->updateShoppingFeedPreloading([$params['object']->id_product], ShoppingfeedPreloading::ACTION_SYNC_PRICE);
    }

    public function hookActionObjectSpecificPriceUpdateAfter($params)
    {
        $this->updateShoppingFeedPreloading([$params['object']->id_product], ShoppingfeedPreloading::ACTION_SYNC_PRICE);
    }

    public function hookActionObjectSpecificPriceDeleteAfter($params)
    {
        $this->updateShoppingFeedPreloading([$params['object']->id_product], ShoppingfeedPreloading::ACTION_SYNC_PRICE);
    }

    public function hookDeleteProductAttribute($params)
    {
        $this->updateShoppingFeedPreloading([$params['id_product']], ShoppingfeedPreloading::ACTION_SYNC_ALL);
    }

    public function hookActionAdminSpecificPriceRuleControllerDeleteBefore($params)
    {
        $productIds = $this
            ->getSpecificPriceService()
            ->getProductIdsByRule((int) Tools::getValue('id_specific_price_rule'));

        $this->updateShoppingFeedPreloading($productIds, ShoppingfeedPreloading::ACTION_SYNC_PRICE);
    }

    /**
     * @return \ShoppingfeedAddon\Services\SpecificPriceService
     */
    public function getSpecificPriceService()
    {
        return new \ShoppingfeedAddon\Services\SpecificPriceService();
    }

    public function addDateIndexToLogs()
    {
        $s_index = 'SHOW INDEX
                FROM ' . _DB_PREFIX_ . 'shoppingfeed_processlogger
                WHERE Key_name = "date_log"';

        if (empty(Db::getInstance()->executeS($s_index))) {
            $cr_index = 'CREATE INDEX date_log ON ' . _DB_PREFIX_ . 'shoppingfeed_processlogger(date_add)';

            return Db::getInstance()->execute($cr_index);
        }

        return true;
    }

    public function truncatePrelodingWhenProductSyncByDateUpdDisabled()
    {
        if ((bool) Configuration::get(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD)) {
            return true;
        }

        return Db::getInstance()->execute('TRUNCATE ' . _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table']);
    }
}
