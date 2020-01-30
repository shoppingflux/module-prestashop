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

function upgrade_module_1_3_0($module)
{
    // Get the classlib installer
    $installer = new Installer();
    $installer->setModule($module);

    // Install the new hooks
    $installer->registerHooks();

    // Install new admintab
    $installer->uninstallModuleAdminControllers();
    $installer->installModuleAdminControllers();

    // Update the ShoppingfeedProduct Model
    $installer->installObjectModel('ShoppingfeedOrder');
    
    // Install the new ShoppingfeedCarrier Model
    $installer->installObjectModel('ShoppingfeedCarrier');

    // Install the new configuration variables
    $shops = Shop::getShops();
    foreach ($shops as $shop) {
        // Set default values for configuration variables
        $module->setConfigurationDefault(Shoppingfeed::ORDER_IMPORT_ENABLED, false, $shop['id_shop']);
    }
    return true;
}
