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

require_once _PS_MODULE_DIR_ . "shoppingfeed/vendor/autoload.php";
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php';
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncStockActions.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncPriceActions.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedOrderSyncActions.php');

// Set this as comment so Classlib will import the files; but don't uncomment !
// Installation will fail on PS 1.6 if "use" statements are in the main module file

// use ShoppingfeedClasslib\Module;
// use ShoppingfeedClasslib\Actions\ActionsHandler;
// use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
// use ShoppingfeedClasslib\Registry;

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
    const STATUS_TIME_SHIT = "SHOPPINGFEED_STATUS_TIME_SHIT";
    const STATUS_MAX_ORDERS = "SHOPPINGFEED_STATUS_MAX_ORDERS";
    const SHIPPED_ORDERS = "SHOPPINGFEED_SHIPPED_ORDERS";
    const CANCELLED_ORDERS = "SHOPPINGFEED_CANCELLED_ORDERS";
    const REFUNDED_ORDERS = "SHOPPINGFEED_REFUNDED_ORDERS";

    /**
     * List of objectModel used in this Module
     * @var array
     */
    public $objectModels = array(
        'ShoppingfeedTaskOrder',
        'ShoppingfeedProcessMonitor',
        'ShoppingfeedProcessLogger',
        'ShoppingfeedProduct',
        'ShoppingfeedOrder',
    );

    /**
     * List of cron tasks indexed by controller name
     * Title value must be an array indexed by iso language (en is required)
     * Frequency value can be hourly, daily, weekly, monthly
     *
     * @var array
     */
    public $cronTasks = array(
        'syncStock' => array(
            'name' => 'shoppingfeed:syncStock',
            'title' => array(
                'en' => '[Deprecated] This task was renamed to shoppingfeed:syncProduct',
                'fr' => '[Dépréciée] Cette tâche a été renommée en shoppingfeed:syncProduct'
            ),
            'frequency' => '5min',
        ),
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
            'parent_class_name' => 'ShopParameters',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Account Settings',
                'fr' => 'Paramètres du compte'
            ),
            'class_name' => 'AdminShoppingfeedAccountSettings',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'General Settings',
                'fr' => 'Paramètres généraux'
            ),
            'class_name' => 'AdminShoppingfeedGeneralSettings',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Cron Tasks',
                'fr' => 'Tâches cron'
            ),
            'class_name' => 'AdminShoppingfeedProcessMonitor',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Tasks activity',
                'fr' => 'Journal d\'activité'
            ),
            'class_name' => 'AdminShoppingfeedProcessLogger',
            'parent_class_name' => 'shoppingfeed',
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
        'syncStock',
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
        'actionOrderStatusPostUpdate',
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

        // Try to retrieve the token from the other SF module
        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            $token = Configuration::get('SHOPPING_FLUX_TOKEN', null, null, $shop['id_shop']);
            if ($token) {
                Configuration::updateValue(self::AUTH_TOKEN, $token, false, null, $shop['id_shop']);
            }
            // Set default values for configuration variables
            Configuration::updateValue(self::STOCK_SYNC_ENABLED, true, false, null, $shop['id_shop']);
            Configuration::updateValue(self::PRICE_SYNC_ENABLED, true, false, null, $shop['id_shop']);
            Configuration::updateValue(self::ORDER_SYNC_ENABLED, true, false, null, $shop['id_shop']);
            Configuration::updateValue(self::STOCK_SYNC_MAX_PRODUCTS, 100, false, null, $shop['id_shop']);
            Configuration::updateValue(self::REAL_TIME_SYNCHRONIZATION, false, false, null, $shop['id_shop']);
            Configuration::updateValue(self::STATUS_TIME_SHIT, 100, false, null, $shop['id_shop']);
            Configuration::updateValue(self::STATUS_MAX_ORDERS, 100, false, null, $shop['id_shop']);
            Configuration::updateValue(self::SHIPPED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
            Configuration::updateValue(self::CANCELLED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
            Configuration::updateValue(self::REFUNDED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
        }

        return $res;
    }
    
    public function setBreakingChangesNotices() {
        if (version_compare($this->version, '1.2', '<')) {
            $this->context->controller->warnings[] = sprintf(
                $this->l('If you are using a Cron task for synchronisation, please note that starting from the v1.1.0 of the module, you should change the URL of your Cron task. Please use the %s shoppingfeed:syncProduct %s Cron task instead of %s shoppingfeed:syncStock %s (you can still use the %s shoppingfeed:syncStock %s Cron task in the v1.1.0 of the module). To get the new Cron task URL please check the %s Scheduled tasks %s tab.'),
                '<b>',
                '</b>',
                '<b>',
                '</b>',
                '<b>',
                '</b>',
                '<a href="' . $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor') . '">',
                '</a>'
            );
        }
    }

    static public function checkImportExportValidity()
    {
       if (Module::isInstalled('shoppingfluxexport') && (Configuration::get('SHOPPING_FLUX_STATUS_SHIPPED') != '' ||
           Configuration::get('SHOPPING_FLUX_STATUS_CANCELED') != '')) {
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
        $reference = $sfProduct->id_product . ($sfProduct->id_product_attribute ? "_" . $sfProduct->id_product_attribute : "");
        
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
    public function mapProductPrice(ShoppingfeedProduct $sfProduct, $id_shop, ...$arguments)
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
            false, // usereduc
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
                'price' => &$price
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
            (!\ShoppingfeedClasslib\Registry::isRegistered('updated_product_prices_ids') ||
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
     * Updates a product on SF if realtime sync is enabled.
     * On PS1.6, it should also update the product's combinations if needed.
     * @param array $params The hook parameters
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
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
            $shops = Shop::getShops();
            foreach ($shops as $shop) {
                if (false == Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, null, null, $shop['id_shop'])) {
                    continue;
                }
                
                $handler->setConveyor(array(
                    'id_shop' => $shop['id_shop'],
                    'product_action' => ShoppingfeedProduct::ACTION_SYNC_PRICE,
                ));

                $processResult = $handler->process('shoppingfeedProductSyncPrice');
                if (!$processResult) {
                    \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logError(
                        ShoppingfeedProductSyncPriceActions::getLogPrefix() . ' ' . $this->l('Fail : An error occurred during process.')
                    );
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

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED)) {
            return;
        }

        $currentOrder = new Order($params['id_order']);

        if ($currentOrder->module == "sfpayment") {
            $shipped_status = json_decode(Configuration::get(self::SHIPPED_ORDERS));
            $cancelled_status = json_decode(Configuration::get(self::CANCELLED_ORDERS));
            $refunded_status = json_decode(Configuration::get(self::REFUNDED_ORDERS));
            $order_action = null;

            if (in_array($params['newOrderStatus']->id, $shipped_status)) {
                $order_action = "shipped";
            } elseif (in_array($params['newOrderStatus']->id, $cancelled_status)) {
                $order_action = "cancelled";
            } elseif (in_array($params['newOrderStatus']->id, $refunded_status)) {
                $order_action = "refunded";
            }

            if ($order_action != null) {
                try {
                    $handler = new \ShoppingfeedClasslib\Actions\ActionsHandler();
                    $handler
                        ->setConveyor(array(
                            'id_order' => $params['id_order'],
                            'order_action' => $order_action,
                        ))
                        ->addActions('saveOrder')
                        ->process('shoppingfeedOrderSync');
                } catch (Exception $e) {
                    \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                        sprintf(
                            ShoppingfeedOrderSyncActions::getLogPrefix() . ' ' . $this->l('Order %s not registered for synchronization: %s', 'ShoppingfeedOrderActions'), $params['id_order'],
                            $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                        ),
                        'Order',
                        $params['id_order']
                    );
                }
            }
        }

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::closeLogger();
    }

}
