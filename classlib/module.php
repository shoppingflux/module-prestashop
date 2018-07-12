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
 * @version   develop
 */

/**
 * @desc
 */
class ShoppingfeedModule extends Module
{

    /**
     * Install Module
     * @return bool
     */
    public function install()
    {
        $installer = TotLoader::getInstance('shoppingfeed\classlib\install\installer');

        $isPhpVersionCompliant = false;
        try {
            $isPhpVersionCompliant = $installer->checkPhpVersion($this);
        } catch (Exception $e) {
            $this->_errors[] = Tools::displayError($e->getMessage());
        }

        return $isPhpVersionCompliant && parent::install() && $installer->install($this);
    }

    /**
     * Uninstall Module
     * @return bool
     */
    public function uninstall()
    {
        $installer = TotLoader::getInstance('shoppingfeed\classlib\install\installer');

        return parent::uninstall() && $installer->uninstall($this);
    }

    /**
     * Reset Module only if merchant choose to keep data on modal
     * @return bool
     */
    public function reset()
    {
        $installer = TotLoader::getInstance('shoppingfeed\classlib\install\installer');

        return $installer->reset($this);
    }

    /**
     * Check if a module need to be upgraded.
     * Upgrade object model and after default upgrade.
     *
     * @param $module
     * @return bool
     */
    public static function needUpgrade($module)
    {
        self::$modules_cache[$module->name]['upgrade']['upgraded_from'] = $module->database_version;
        if (Tools::version_compare($module->version, $module->database_version, '>')) {
            $module = Module::getInstanceByName($module->name);
            if ($module instanceof Module) {
                $installer = TotLoader::getInstance('shoppingfeed\classlib\install\installer');
                $installer->upgrade($module);
            }
        }

        return parent::needUpgrade($module);
    }
}
