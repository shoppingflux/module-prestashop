<?php
/**
 *
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
 *
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
    $module->setConfigurationDefault(Shoppingfeed::ORDER_SYNC_ENABLED, true);
    $module->setConfigurationDefault(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, 100);
    $module->setConfigurationDefault(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, false);
    $module->setConfigurationDefault(Shoppingfeed::ORDER_STATUS_TIME_SHIFT, 5);
    $module->setConfigurationDefault(Shoppingfeed::ORDER_STATUS_MAX_ORDERS, 100);
    $module->setConfigurationDefault(Shoppingfeed::SHIPPED_ORDERS, json_encode(array()));
    $module->setConfigurationDefault(Shoppingfeed::CANCELLED_ORDERS, json_encode(array()));
    $module->setConfigurationDefault(Shoppingfeed::REFUNDED_ORDERS, json_encode(array()));

    return true;
}
