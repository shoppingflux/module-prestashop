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

require_once _PS_MODULE_DIR_.'shoppingfeed/config_dev.php';
require_once _PS_MODULE_DIR_.'shoppingfeed/classes/ShoppingfeedProduct.php';

TotLoader::import('shoppingfeed\classlib\module');

class Shoppingfeed extends ShoppingfeedModule
{
    /** @var string This module requires at least PHP version */
    public $php_version_required = '5.6';

    const AUTH_TOKEN = "SHOPPINGFEED_AUTH_TOKEN";
    const STOCK_SYNC_MAX_PRODUCTS = "SHOPPINGFEED_STOCK_SYNC_MAX_PRODUCTS";
    const REAL_TIME_SYNCHRONIZATION = "SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION";

    /**
     * List of objectModel used in this Module
     * @var array
     */
    public $objectModels = array(
        'ShoppingfeedProcessMonitor',
        'ShoppingfeedProcessLogger',
        'ShoppingfeedProduct',
    );

    /** @var array $moduleAdminControllers
     *  The "AdminShoppingfeed2" "AdminShoppingfeed" are both required for ps1.6 / ps1.7 compliance
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
                'en' => 'Configuration',
                'fr' => 'Configuration'
            ),
            'class_name' => 'AdminShoppingfeedConfiguration',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Scheduled tasks',
                'fr' => 'Tâches planifiées'
            ),
            'class_name' => 'AdminShoppingfeedProcessMonitor',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Tasks logs',
                'fr' => 'Journal des tâches'
            ),
            'class_name' => 'AdminShoppingfeedProcessLogger',
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
        'syncStock'
    );

    /**
     * List of hooks used in this Module
     * @var array
     */
    public $hooks = array(
        'actionUpdateQuantity',
    );

    /**
     * Used to avoid spam or unauthorized execution of cron controller
     * @var string Unique token depend on _COOKIE_KEY_ which is unique to this website
     * @see Tools::encrypt()
     */
    public $secure_key;

    public function __construct()
    {
        $this->name = 'shoppingfeed';
        $this->version = '1.0.0';
        $this->author = '202 ecommerce';
        $this->tab = 'adminModule';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        $this->need_instance = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('15 min Marketplace Updates - Shopping Feed');
        $this->description = $this->l('Improve your Shopping Feed module\'s  Marketplaces stocks synchronization speed.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->secure_key = Tools::encrypt($this->name);
    }

    public function install()
    {
        $res = parent::install();

        // Try to retrieve the token from the other SF module
        $token = Configuration::get('SHOPPING_FLUX_TOKEN');
        if ($token) {
            Configuration::updateValue(self::AUTH_TOKEN, $token);
        }

        Configuration::updateValue(self::STOCK_SYNC_MAX_PRODUCTS, 100);
        Configuration::updateValue(self::REAL_TIME_SYNCHRONIZATION, false);

        return $res;
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminShoppingfeedConfiguration')
        );
    }

    /**
     * Saves a product for stock synchronization, or synchronizes it directly using the Actions handler
     * @param $params
     */
    public function hookActionUpdateQuantity($params)
    {
        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];
        $new_quantity = $params['quantity'];

        $id_shop_list = Context::getContext()->shop->getContextListShopID();
        TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\ProcessLoggerHandler');
        TotLoader::import('shoppingfeed\classlib\registry');

        foreach ($id_shop_list as $id_shop) {
            try {
                $sfProduct = new ShoppingfeedProduct();
                $sfProduct->action = ShoppingfeedProduct::ACTION_SYNC_STOCK;
                $sfProduct->id_product = $id_product;
                $sfProduct->id_product_attribute = $id_product_attribute;
                $sfProduct->id_shop = $id_shop;
                if ($sfProduct->save() == false) {
                    ShoppingfeedProcessLoggerHandler::logError(
                        '[Stock] Fail : ' .
                        "Product $id_product _ $id_product_attribute cannot be added to synchro.",
                        'Product',
                        $id_product
                    );
                }
            } catch (Exception $e) {
                // We can't do an "insert ignore", so use a try catch...
                ShoppingfeedProcessLoggerHandler::logInfo(
                    '[Stock] Fail : ' .
                    "Product $id_product _ $id_product_attribute already in synchro list.",
                    'Product',
                    $id_product
                );
            }
        }

        if (true == Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
            /** @var ShoppingfeedHandler $productActionsHandler */
            $productActionsHandler = TotLoader::getInstance('shoppingfeed\classlib\actions\handler');
            $productActionsHandler
                ->addActions("getBatch", "prepareBatch", "executeBatch")
                ->process("shoppingfeedProductStockSync");
        }
        ShoppingfeedProcessLoggerHandler::closeLogger();
    }
}
