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

use ShoppingfeedClasslib\Install\Installer;

function upgrade_module_1_2_0($module)
{
    // Get the classlib installer
    $installer = new Installer();
    $installer->setModule($module);

    // Install the new hooks
    $installer->registerHooks();

    // Install new admintab
    $installer->uninstallModuleAdminControllers();
    $installer->installModuleAdminControllers();

    // Install the ShoppingfeedProduct Model
    $installer->installObjectModel('ShoppingfeedOrder');
    $installer->installObjectModel('ShoppingfeedTaskOrder');

    // Install the new configuration variables
    $shops = Shop::getShops();
    foreach ($shops as $shop) {
        // Set default values for configuration variables
        Configuration::updateValue(ShoppingFeed::ORDER_SYNC_ENABLED, true, false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::STOCK_SYNC_MAX_PRODUCTS, 100, false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::REAL_TIME_SYNCHRONIZATION, false, false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::STATUS_TIME_SHIT, 100, false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::STATUS_MAX_ORDERS, 100, false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::SHIPPED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::CANCELLED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
        Configuration::updateValue(ShoppingFeed::REFUNDED_ORDERS, json_encode(array()), false, null, $shop['id_shop']);
    }
    return true;
}
