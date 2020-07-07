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

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedPreloading.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedOrder.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedCarrier.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedTaskOrder.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedToken.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncStockActions.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncPriceActions.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncPreloadingActions.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedOrderSyncActions.php';

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
     * @var string
     */
    public $php_version_required = '5.6';

    const AUTH_TOKEN = "SHOPPINGFEED_AUTH_TOKEN";
    const STOCK_SYNC_ENABLED = "SHOPPINGFEED_STOCK_SYNC_ENABLED";
    const PRICE_SYNC_ENABLED = "SHOPPINGFEED_PRICE_SYNC_ENABLED";
    const ORDER_SYNC_ENABLED = "SHOPPINGFEED_ORDER_SYNC_ENABLED";
    const STOCK_SYNC_MAX_PRODUCTS = "SHOPPINGFEED_STOCK_SYNC_MAX_PRODUCTS";
    const REAL_TIME_SYNCHRONIZATION = "SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION";
    const LAST_CRON_TIME_SYNCHRONIZATION = "SHOPPINGFEED_LAST_CRON_TIME_SYNCHRONIZATION";
    const ORDER_STATUS_TIME_SHIFT = "SHOPPINGFEED_ORDER_STATUS_TIME_SHIFT";
    const ORDER_STATUS_MAX_ORDERS = "SHOPPINGFEED_ORDER_STATUS_MAX_ORDERS";
    const SHIPPED_ORDERS = "SHOPPINGFEED_SHIPPED_ORDERS";
    const CANCELLED_ORDERS = "SHOPPINGFEED_CANCELLED_ORDERS";
    const REFUNDED_ORDERS = "SHOPPINGFEED_REFUNDED_ORDERS";
    const ORDER_IMPORT_ENABLED = "SHOPPINGFEED_ORDER_IMPORT_ENABLED";
    const ORDER_IMPORT_TEST = "SHOPPINGFEED_ORDER_IMPORT_TEST";
    const ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION = "SHOPPINGFEED_ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION";
    const ORDER_DEFAULT_CARRIER_REFERENCE = "SHOPPINGFEED_ORDER_DEFAULT_CARRIER_REFERENCE";
    const PRODUCT_FEED_CARRIER_REFERENCE = "SHOPPINGFEED_PRODUCT_FEED_CARRIER_REFERENCE";
    const PRODUCT_FEED_SYNC_PACK = "SHOPPINGFEED_PRODUCT_FEED_SYNC_PACK";
    const PRODUCT_FEED_IMAGE_FORMAT = "SHOPPINGFEED_PRODUCT_FEED_IMAGE_FORMAT";
    const PRODUCT_FEED_CATEGORY_DISPLAY = "SHOPPINGFEED_PRODUCT_FEED_CATEGORY_DISPLAY";
    const PRODUCT_FEED_CUSTOM_FIELDS = "SHOPPINGFEED_PRODUCT_FEED_CUSTOM_FIELDS";
    const PRODUCT_FEED_REFERENCE_FORMAT = "SHOPPINGFEED_PRODUCT_FEED_REFERENCE_FORMAT";


    public $extensions = array(
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerExtension::class,
        \ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorExtension::class
    );

    /**
     * List of objectModel used in this Module
     * @var array
     */
    public $objectModels = array(
        ShoppingfeedTaskOrder::class,
        ShoppingfeedProduct::class,
        ShoppingfeedOrder::class,
        ShoppingfeedCarrier::class,
        ShoppingfeedPreloading::class,
        ShoppingfeedToken::class,
    );

    /**
     * List of cron tasks indexed by controller name
     * Title value must be an array indexed by iso language (en is required)
     * Frequency value can be hourly, daily, weekly, monthly
     *
     * @var array
     */
    public $cronTasks = array(
        'syncProduct' => array(
            'name' => 'shoppingfeed:syncProduct',
            'title' => array(
                'en' => 'Synchronize products on Shopping Feed',
                'fr' => 'Synchronisation des produits sur Shopping Feed'
            ),
            'frequency' => '5min',
        ),
        'syncOrder' => array(
            'name' => 'shoppingfeed:syncOrder',
            'title' => array(
                'en' => 'Synchronize orders on Shopping Feed',
                'fr' => 'Synchronisation des commandes sur Shopping Feed'
            ),
            'frequency' => '5min',
        )
    );

    /** @var array $moduleAdminControllers
     */
    public $moduleAdminControllers = array(
        array(
            'name' => array(
                'en' => 'Shopping Feed',
                'fr' => 'Shopping Feed'
            ),
            'class_name' => 'shoppingfeed',
            'parent_class_name' => 'SELL',
            'icon' => 'store_mall_directory',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Marketplaces Summary',
                'fr' => 'Commandes Marketplaces'
            ),
            'class_name' => 'AdminShoppingfeedOrders',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Settings',
                'fr' => 'Paramètres'
            ),
            'class_name' => 'AdminShoppingfeedSettings',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Account settings',
                'fr' => 'Paramètres du compte'
            ),
            'class_name' => 'AdminShoppingfeedAccountSettings',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Products feed',
                'fr' => 'Flux des produits'
            ),
            'class_name' => 'AdminShoppingfeedGeneralSettings',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Orders feeds',
                'fr' => 'Flux des commandes'
            ),
            'class_name' => 'AdminShoppingfeedOrderImportRules',
            'parent_class_name' => 'AdminShoppingfeedSettings',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Logs & crons',
                'fr' => 'Logs & crons',
            ),
            'class_name' => 'AdminShoppingfeedProcess',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Logs',
                'fr' => 'Logs'
            ),
            'class_name' => 'AdminShoppingfeedProcessLogger',
            'parent_class_name' => 'AdminShoppingfeedProcess',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Cron Tasks',
                'fr' => 'Tâches cron'
            ),
            'class_name' => 'AdminShoppingfeedProcessMonitor',
            'parent_class_name' => 'AdminShoppingfeedProcess',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'FAQ/Help',
                'fr' => 'FAQ/Aide'
            ),
            'class_name' => 'AdminShoppingfeedFaq',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
    );

    /**
     * List of ModuleFrontController used in this Module
     * Module::install() register it, after that you can edit it in BO (for rewrite if needed)
     * @var array
     */
    public $controllers = array(
        'syncProduct',
        'syncOrder',
    );

    /**
     * List of hooks used in this Module
     * @var array
     */
    public $hooks = array(
        'actionUpdateQuantity',
        'actionObjectProductUpdateBefore',
        'actionObjectCombinationUpdateBefore',
        'actionObjectProductUpdateAfter',
        'actionObjectCombinationUpdateAfter',
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
        'actionShoppingfeedOrderImportRegisterSpecificRules',
        'actionObjectProductDeleteBefore'
    );

    /**
     * Used to avoid spam or unauthorized execution of cron controller
     * @var string Unique token depend on _COOKIE_KEY_ which is unique to this website
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
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('15 min Marketplace Updates - Shopping Feed');
        $this->description = $this->l('Improve your Shopping Feed module\'s  Marketplaces stocks synchronization speed.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->secure_key = Tools::encrypt($this->name);
    }

    /**
     * Installs the module; see the parent ShoppingfeedModule class from classlib
     * @return bool
     */
    public function install()
    {
        $res = parent::install();

        $this->setConfigurationDefault(self::STOCK_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::PRICE_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::ORDER_SYNC_ENABLED, true);
        $this->setConfigurationDefault(self::STOCK_SYNC_MAX_PRODUCTS, 100);
        $this->setConfigurationDefault(self::REAL_TIME_SYNCHRONIZATION, false);
        $this->setConfigurationDefault(self::ORDER_STATUS_TIME_SHIFT, 5);
        $this->setConfigurationDefault(self::ORDER_STATUS_MAX_ORDERS, 100);
        $this->setConfigurationDefault(self::SHIPPED_ORDERS, json_encode(array()));
        $this->setConfigurationDefault(self::CANCELLED_ORDERS, json_encode(array()));
        $this->setConfigurationDefault(self::REFUNDED_ORDERS, json_encode(array()));
        $this->setConfigurationDefault(self::ORDER_IMPORT_ENABLED, true);
        $this->setConfigurationDefault(self::PRODUCT_FEED_CARRIER_REFERENCE, Configuration::getGlobalValue('PS_CARRIER_DEFAULT'));

        return $res;
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
     *
     * @return boolean
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
     * @return boolean
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
     * @param ShoppingFeedProduct $sfProduct
     * @param array $arguments Should you want to pass more arguments to this
     * function, you can find them in this array
     * @return string
     */
    public function mapReference(ShoppingfeedProduct $sfProduct, ...$arguments)
    {
        $reference = $sfProduct->getShoppingfeedReference();

        Hook::exec(
            'ShoppingfeedMapProductReference', // hook_name
            array(
                'ShoppingFeedProduct' => &$sfProduct,
                'reference' => &$reference
            ) // hook_args
        );

        return $reference;
    }

    /**
     * Returns the Prestashop product matching the Shopping Feed reference. The
     * developer can skip specific products during order import by overriding
     * this method and have it return false.
     * @param string $sfProductReference The product's reference in Shopping Feed's system
     * @param string $id_shop
     * @param array $arguments Should you want to pass more arguments to this
     * function, you can find them in this array
     * @return array
     */
    public function mapPrestashopProduct($sfProductReference, $id_shop, ...$arguments)
    {
        $explodedReference = explode('_', $sfProductReference);
        $id_product = isset($explodedReference[0]) ? $explodedReference[0] : null;

        $product = new Product($id_product, true, null, $id_shop);
        $product->id_product_attribute = null;
        if (isset($explodedReference[1])) {
            $product->id_product_attribute = $explodedReference[1];
        }

        Hook::exec(
            'ShoppingfeedMapProduct', // hook_name
            array(
                'sfProductReference' => &$sfProductReference,
                'product' => &$product,
            ) // hook_args
        );

        return $product;
    }

    /**
     * Returns the product's price sent to the Shopping Feed API. The developer
     * can skip products to sync by overriding this method and have it return
     * false. Note that the comparison with the return value is strict to allow
     * "0" as a valid price.
     * @param ShoppingFeedProduct $sfProduct
     * @param int $id_shop
     * @param array $arguments Should you want to pass more arguments to this
     * function, you can find them in this array
     * @return string
     */
    public function mapProductPrice(ShoppingfeedProduct $sfProduct, $id_shop, $arguments = [])
    {
        $cloneContext = Context::getContext()->cloneContext();
        $cloneContext->shop = new Shop($id_shop);

        $specific_price_output = null;
        Product::flushPriceCache();
        $price = Product::getPriceStatic(
            $sfProduct->id_product, // id_product
            true, // usetax
            $sfProduct->id_product_attribute ? // id_product_attribute
                $sfProduct->id_product_attribute : null,
            2, // decimals
            null, // divisor
            false, // only_reduc
            is_array($arguments) && array_key_exists('price_with_reduction', $arguments) && $arguments['price_with_reduction'] === true, // usereduc
            1, // quantity
            false, // force_associated_tax
            null, // id_customer
            null, // id_cart
            null, // id_address
            $specific_price_output, // specific_price_output; reference
            true, // with_ecotax
            true, // use_group_reduction
            $cloneContext, // context; get the price for the specified shop
            true, // use_customer_price
            null // id_customization
        );

        Hook::exec(
            'ShoppingfeedMapProductPrice', // hook_name
            array(
                'ShoppingFeedProduct' => &$sfProduct,
                'price' => &$price,
                'arguments' => $arguments,
            ) // hook_args
        );

        return $price;
    }

    /****************************** Stock hook *******************************/

    /**
     * Saves a product for stock synchronization, or synchronizes it directly
     * using the Actions handler
     * @param array $params The hook parameters
     * @throws Exception
     */
    public function hookActionUpdateQuantity($params)
    {
        if (!Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED)) {
            return;
        }

        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];

        try {
            /** @var ShoppingfeedHandler $handler */
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor(array(
                    'id_product' => $id_product,
                    'id_product_attribute' => $id_product_attribute,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_STOCK,
                ))
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncStock');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    ShoppingfeedProductSyncStockActions::getLogPrefix() . ' ' . $this->l('Product %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
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
        if ((float)$old_product->price == (float)$product->price) {
            return;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor(array(
                    'id_product' => $product->id,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ))
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncPrice');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    ShoppingfeedProductSyncPriceActions::getLogPrefix() . ' ' . $this->l('Product %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
                    $product->id,
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                'Product',
                $product->id
            );
        }

        if (!\ShoppingfeedClasslib\Registry::isRegistered('updated_product_prices_ids')) {
            \ShoppingfeedClasslib\Registry::set('updated_product_prices_ids', array());
        }
        $updatedProductPricesIds = \ShoppingfeedClasslib\Registry::get('updated_product_prices_ids');
        $updatedProductPricesIds[] = $product->id;
        \ShoppingfeedClasslib\Registry::set('updated_product_prices_ids', $updatedProductPricesIds);

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();

        // Combinations hook are not called when saving the product on 1.6
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $attributes = $product->getAttributesResume(Context::getContext()->language->id);
            foreach ($attributes as $attribute) {
                $this->hookActionObjectCombinationUpdateBefore(array(
                    'object' => new Combination($attribute['id_product_attribute'])
                ));
            }
        }
    }

    /**
     * Compares an updated combinations's price with its old price. If the new
     * price is different, saves the combination for price synchronization.
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
        if ((float)$old_combination->price == (float)$combination->price &&
            (
                !\ShoppingfeedClasslib\Registry::isRegistered('updated_product_prices_ids') ||
                !in_array($combination->id_product, \ShoppingfeedClasslib\Registry::get('updated_product_prices_ids'))
            )) {
            return;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler
                ->setConveyor(array(
                    'id_product' => $combination->id_product,
                    'id_product_attribute' => $combination->id,
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ))
                ->addActions('saveProduct')
                ->process('shoppingfeedProductSyncPrice');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    ShoppingfeedProductSyncPriceActions::getLogPrefix() . ' ' . $this->l('Combination %s not registered for synchronization: %s', 'ShoppingfeedProductSyncActions'),
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
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductDeleteBefore($params)
    {
        $product = $params['object'];
        if (!Validate::isLoadedObject($product)) {

            return false;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler->addActions('deleteProduct')
                ->setConveyor(
                    array(
                        'product' => $product,
                    )
                );
            $processResult = $handler->process('ShoppingfeedProductSyncPreloading');
            if (!$processResult) {
                \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                    ShoppingfeedProductSyncPreloadingActions::getLogPrefix() . ' ' . $this->l('Fail : An error occurred during process.')
                );
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedProductSyncPreloadingActions::getLogPrefix() . ' ' . $this->l('Fail : %s'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Updates a product on SF if realtime sync is enabled.
     * On PS1.6, it should also update the product's combinations if needed.
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
        $this->updateShoppingFeedPreloading($params, ShoppingfeedPreloading::ACTION_SYNC_ALL);
        $this->updateShoppingFeedPriceRealtime();
    }

    /**
     * Updates a combination on SF if realtime sync is enabled.
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

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler->addActions('getBatch');
            $sft = new ShoppingfeedToken();
            $tokens = $sft->findALlActive();

            if (Configuration::getGlobalValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
                foreach ($tokens as $token) {
                    $handler->setConveyor(array(
                        'id_token' => $token['id_shoppingfeed_token'],
                        'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                    ));

                    $processResult = $handler->process('shoppingfeedProductSyncPrice');
                    if (!$processResult) {
                        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                            ShoppingfeedProductSyncPriceActions::getLogPrefix() . ' ' . $this->l('Fail : An error occurred during process.')
                        );
                    }
                }
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedProductSyncPriceActions::getLogPrefix() . ' ' . $this->l('Fail : %s'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
        }
        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

    /**
     * Processes products to indexed on add into XML feed.
     */
    public function updateShoppingFeedPreloading($params, $action)
    {
        $product = $params['object'];
        if (!Validate::isLoadedObject($product)) {

            return false;
        }

        try {
            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $handler->addActions('saveProduct')
                    ->setConveyor(
                        array(
                            'product' => $product,
                            'product_action' => $action
                        )
                    );
            $processResult = $handler->process('ShoppingfeedProductSyncPreloading');
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedProductSyncPreloadingActions::getLogPrefix() . ' ' . $this->l('Fail : %s'),
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
     * @return void
     */
    public function hookActionValidateOrder($params)
    {
        if (!Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED) || !self::isOrderSyncAvailable() || Configuration::get(self::ORDER_IMPORT_ENABLED)) {
            return;
        }

        $currentOrder = $params['order'];

        // Only process orders added via the shoppingflux module
        if ($currentOrder->module != "sfpayment") {
            return;
        }

        try {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                sprintf(
                    ShoppingfeedOrderSyncActions::getLogPrefix($currentOrder->id) . ' ' .
                        $this->l('Start import Order %s ', 'ShoppingfeedOrderActions'),
                    $currentOrder->id
                ),
                'Order',
                $currentOrder->id
            );

            $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
            $processResult = $handler
                ->setConveyor(array('id_order' => $currentOrder->id))
                ->addActions('saveOrder')
                ->process('shoppingfeedOrderSync');

            if (!$processResult) {
                \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                    ShoppingfeedOrderSyncActions::getLogPrefix($currentOrder->id) . ' ' .
                        $this->l('Fail : An error occurred during process.')
                );
            }
        } catch (Exception $e) {
            \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                sprintf(
                    ShoppingfeedOrderSyncActions::getLogPrefix() . ' ' .
                        $this->l('Order %s not imported : %s', 'ShoppingfeedOrderActions'),
                    $params['id_order'],
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
                ->setConveyor(array(
                    'id_order' => $params['id_order'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                ))
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
        $defaultRulesClassNames = array(
            ShoppingfeedAddon\OrderImport\Rules\AmazonB2B::class,
            ShoppingfeedAddon\OrderImport\Rules\AmazonEbay::class,
            ShoppingfeedAddon\OrderImport\Rules\AmazonPrime::class,
            ShoppingfeedAddon\OrderImport\Rules\Cdiscount::class,
            ShoppingfeedAddon\OrderImport\Rules\CdiscountRelay::class,
            ShoppingfeedAddon\OrderImport\Rules\Mondialrelay::class,
            ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay::class,
            ShoppingfeedAddon\OrderImport\Rules\Socolissimo::class,
            ShoppingfeedAddon\OrderImport\Rules\ShippedByMarketplace::class,
            ShoppingfeedAddon\OrderImport\Rules\TestingOrder::class,
        );

        foreach($defaultRulesClassNames as $ruleClassName) {
            $params['specificRulesClassNames'][] = $ruleClassName;
        }
    }
}
