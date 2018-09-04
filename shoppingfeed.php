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

require_once _PS_MODULE_DIR_ . 'shoppingfeed/config_dev.php';

TotLoader::import('shoppingfeed\classlib\module');

/**
 * The base module class
 */
class Shoppingfeed extends ShoppingfeedModule
{
    /**
     * This module requires at least PHP version
     * @var string
     */
    public $php_version_required = '5.6';

    const AUTH_TOKEN = "SHOPPINGFEED_AUTH_TOKEN";
    const STOCK_SYNC_MAX_PRODUCTS = "SHOPPINGFEED_STOCK_SYNC_MAX_PRODUCTS";
    const REAL_TIME_SYNCHRONIZATION = "SHOPPINGFEED_REAL_TIME_SYNCHRONIZATION";
    const LAST_CRON_TIME_SYNCHRONIZATION = "SHOPPINGFEED_LAST_CRON_TIME_SYNCHRONIZATION";

    /**
     * List of objectModel used in this Module
     * @var array
     */
    public $objectModels = array(
        'ShoppingfeedProcessMonitor',
        'ShoppingfeedProcessLogger',
        'ShoppingfeedProduct',
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
                'en' => 'Synchronize stock on Shopping Feed',
                'fr' => 'Synchronisation du stock sur Shopping Feed'
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
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord'
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
        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $token = Configuration::get('SHOPPING_FLUX_TOKEN', null, null, $id_shop);
        if ($token) {
            Configuration::updateValue(self::AUTH_TOKEN, $token, false, false, $id_shop);
        }

        Configuration::updateValue(self::STOCK_SYNC_MAX_PRODUCTS, 100);
        Configuration::updateValue(self::REAL_TIME_SYNCHRONIZATION, false);

        return $res;
    }

    /**
     * Redirects the user to our AdminController for configuration
     * @throws PrestaShopException
     */
    public function getContent()
    {

        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminShoppingfeedConfiguration')
        );
    }

    /**
     * Saves a product for stock synchronization, or synchronizes it directly using the Actions handler
     * @param array $params The hook parameters
     * @throws Exception
     */
    public function hookActionUpdateQuantity($params)
    {
        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];
        $new_quantity = $params['quantity'];

        TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\ProcessLoggerHandler');
        TotLoader::import('shoppingfeed\classlib\registry');

        /** @var ShoppingfeedHandler $handler */
        $handler = TotLoader::getInstance('shoppingfeed\classlib\actions\handler');
        $handler
            ->setConveyor(array(
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
            ))
            ->addActions("saveProduct")
            ->process('shoppingfeedProductStockSync');

        ShoppingfeedProcessLoggerHandler::closeLogger();
    }
}
