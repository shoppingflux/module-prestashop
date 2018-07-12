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

require_once __DIR__ . '/config_dev.php';
require_once __DIR__ . '/classes/ShoppingfeedProduct.php';

TotLoader::import('shoppingfeed\classlib\module');

class Shoppingfeed extends ShoppingfeedModule
{
    /** @var string This module requires at least PHP version */
    public $php_version_required = '5.6';

    const AUTH_TOKEN = "SHOPPINGFEED_AUTH_TOKEN";
    const STOCK_SYNC_MAX_PRODUCTS = "SHOPPINGFEED_STOCK_SYNC_MAX_PRODUCTS";

    public $objectModels = array(
        'ShoppingfeedProcessMonitor',
        'ShoppingfeedProcessLogger',
        'ShoppingfeedProduct',
    );

    public $moduleAdminControllers = array(
        /* Both are required for ps1.6 / ps1.7 compliancy */
        array(
            'name' => array(
                'en' => 'Shopping Feed',
                'fr' => 'Shopping Feed'
            ),
            'class_name' => 'AdminShoppingfeed2',
            'parent_class_name' => 'SELL',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => '202 Shopping Feed',
                'fr' => '202 Shopping Feed'
            ),
            'class_name' => 'AdminShoppingfeed',
            'parent_class_name' => 'AdminShoppingfeed2',
            'visible' => true,
        ),
        /**/
        array(
            'name' => array(
                'en' => 'Configuration',
                'fr' => 'Configuration'
            ),
            'class_name' => 'AdminShoppingfeedConfiguration',
            'parent_class_name' => 'AdminShoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Scheduled tasks',
                'fr' => 'Tâches planifiées'
            ),
            'class_name' => 'AdminShoppingfeedProcessMonitor',
            'parent_class_name' => 'AdminShoppingfeed',
            'visible' => true,
        ),
        array(
            'name' => array(
                'en' => 'Tasks logs',
                'fr' => 'Journal des tâches'
            ),
            'class_name' => 'AdminShoppingfeedProcessLogger',
            'parent_class_name' => 'AdminShoppingfeed',
            'visible' => true,
        ),
    );

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

        // TODO : check if the other module is installed ?
        $token = Configuration::get('SHOPPING_FLUX_TOKEN');
        if ($token) {
            Configuration::updateValue(self::AUTH_TOKEN, $token);
        }

        Configuration::updateValue(self::STOCK_SYNC_MAX_PRODUCTS, 100);

        // Add unique key on multiple columns for "product" table
        $sqlProductKey = "ALTER TABLE `" . _DB_PREFIX_ . "shoppingfeed_product` ADD UNIQUE( `action`, `id_product`, `id_product_attribute`, `id_shop`);";
        return $res && Db::getInstance()->execute($sqlProductKey);
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminShoppingfeedConfiguration')
        );
    }

    public function hookActionUpdateQuantity($params)
    {
        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];
        $new_quantity = $params['quantity'];

        $product = new Product($id_product);
        if ($product->hasCombinations() && !$id_product_attribute) {
            return;
        }

        $id_shop_list = Context::getContext()->shop->getContextListShopID();

        ShoppingfeedProduct::saveAction(ShoppingfeedProduct::ACTION_SYNC_STOCK, $id_product, $id_shop_list, $id_product_attribute);
    }
}