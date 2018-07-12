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
class ShoppingfeedInstaller
{
    /**
     * @var Module
     */
    protected $module;


    /**
     * @return this
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return bool
     */
    public function install($module)
    {
        $this->module = $module;
        $result = $this->registerHooks();
        $result &= $this->installObjectModels();
        $result &= $this->installModuleAdminControllers();

        return $result;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function checkPhpVersion($module)
    {
        if (empty($module->php_version_required)) {
            return true;
        }

        $phpVersion = Tools::checkPhpVersion();

        if (Tools::version_compare($phpVersion, $module->php_version_required, '<')) {
            throw new Exception(sprintf(
                '[%s] This module requires at least PHP %s or newer versions.',
                $module->name,
                $module->php_version_required
            ));
        }

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall($module)
    {
        $this->module = $module;
        $result = $this->uninstallObjectModels();
        $result &= $this->uninstallModuleAdminControllers();
        $result &= $this->uninstallConfiguration();

        return $result;
    }

    /**
     * Used only if merchant choose to keep data on modal
     * @return bool
     */
    public function reset($module)
    {
        $this->module = $module;
        $result = $this->clearHookUsed();
        $result &= $this->installObjectModels();
        $result &= $this->uninstallModuleAdminControllers();
        $result &= $this->installModuleAdminControllers();

        return $result;
    }

    /**
     * Register hooks used by our module
     * @return bool
     */
    public function registerHooks()
    {
        if (empty($this->module->hooks)) {
            return true;
        }

        return array_product(array_map(array($this->module, 'registerHook'), $this->module->hooks));
    }

    /**
     * Clear hooks used by our module
     * @return bool
     */
    public function clearHookUsed()
    {
        $result = true;
        $hooksUsed = $this->getHooksUsed();

        if (empty($hooksUsed) && empty($this->module->hooks)) {
            // Both are empty, no need to continue process
            return $result;
        }

        if (false === is_array($this->module->hooks)) {
            // If $module->hooks is not defined or is not an array
            $this->module->hooks = array();
        }

        foreach ($this->module->hooks as $hook) {
            if (false === in_array($hook, $hooksUsed, true)) {
                // If hook is not registered, do it
                $result &= $this->module->registerHook($hook);
            }
        }

        foreach ($hooksUsed as $hookUsed) {
            if (false === in_array($hookUsed, $this->module->hooks, true)) {
                // If hook is registered by module but is not used anymore
                $result &= $this->module->unregisterHook($hookUsed);
                $result &= $this->module->unregisterExceptions($hookUsed);
            }
        }

        return $result;
    }

    /**
     * Retrieve hooks used by our module
     * @throws PrestaShopDatabaseException
     */
    public function getHooksUsed()
    {
        $query = new DbQuery();
        $query->select('h.name');
        $query->from('hook', 'h');
        $query->innerJoin('hook_module', 'hm', 'hm.id_hook = h.id_hook');
        $query->where('hm.id_module = ' . (int)$this->module->id);
        $query->groupBy('h.name');

        $results = Db::getInstance()->executeS($query);

        if (empty($results)) {
            return array();
        }

        return array_column($results, 'name');
    }

    /**
     * Add Tabs for our ModuleAdminController
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installModuleAdminControllers()
    {
        $result = true;

        if (empty($this->module->moduleAdminControllers)) {
            // If module has no ModuleAdminController to install
            return $result;
        }

        foreach ($this->module->moduleAdminControllers as $tabData) {
            if (Tab::getIdFromClassName($tabData['class_name'])) {
                $result &= true;
                continue;
            }

            $parentClassName = $tabData['parent_class_name'];
            if ($parentClassName == 'ShopParameters' && version_compare(_PS_VERSION_, '1.7', '<')) {
                $parentClassName = 'AdminPreferences';
            }
            /** 3 levels available on 1.7+ */
            $defaultTabLevel1 = array('SELL', 'IMPROVE', 'CONFIGURE', 'DEFAULT');
            if (in_array($parentClassName, $defaultTabLevel1) && version_compare(_PS_VERSION_, '1.7', '<')) {
                continue;
            }
            if ($tabData['class_name'] == 'shoppingfeed' && version_compare(_PS_VERSION_, '1.7', '<')) {
                $parentClassName = 'AdminParentModulesSf';
                $tabData['parent_class_name'] = 'AdminParentModulesSf';
                $tabData['visible'] = true;
            }

            $tab = new Tab();
            $parentId = (int)Tab::getIdFromClassName($parentClassName);
            if (!empty($parentId)) {
                $tab->id_parent = $parentId;
            }
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->module->name;

            foreach (Language::getLanguages(false) as $language) {
                if (empty($tabData['name'][$language['iso_code']])) {
                    $tab->name[$language['id_lang']] = $tabData['name']['en'];
                } else {
                    $tab->name[$language['id_lang']] = $tabData['name'][$language['iso_code']];
                }
            }

            $tab->active = true;
            if (isset($tabData['visible'])) {
                $tab->active = $tabData['visible'];
            }

            if (isset($tabData['icon']) && property_exists('Tab', 'icon')) {
                $tab->icon = $tabData['icon']; // For Prestashop 1.7
            }

            $result &= (bool)$tab->add();
        }
        return $result;
    }

    /**
     * Delete Tabs of our ModuleAdminController
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallModuleAdminControllers()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('tab');
        $query->where('module = \''.pSQL($this->module->name).'\'');

        $tabs = Db::getInstance()->executeS($query);

        if (empty($tabs)) {
            return true;
        }

        $result = true;
        foreach ($tabs as $tabData) {
            $tab = new Tab((int)$tabData['id_tab']);
            $result &= (bool)$tab->delete();
        }
        return $result;
    }

    /**
     * Install all our ObjectModel
     * @return bool
     */
    public function installObjectModels()
    {
        if (empty($this->module->objectModels)) {
            return true;
        }

        return array_product(array_map(array($this, 'installObjectModel'), $this->module->objectModels));
    }

    /**
     * Install model
     * @param string $objectModelClassName
     * @return bool
     * @throws Exception
     */
    public function installObjectModel($objectModelClassName)
    {
        /** @var ObjectModel $objectModel */
        $objectModelPath = _PS_MODULE_DIR_ . 'shoppingfeed/classes/'.$objectModelClassName.'.php';
        if (file_exists($objectModelPath)) {
            require_once $objectModelPath;
        } else {
            throw new Exception('Installer error : ModelObject "' . $objectModelClassName .
                    '" not found or file "' . $objectModelPath .
                    '" doesn\'t exist. Please check a typo ?');
        }

        $objectModel = new $objectModelClassName();

        TotLoader::import('shoppingfeed\classlib\db\ObjectModelExtension');
        $objectModelExtended = new ShoppingfeedObjectModelExtension(
            $objectModel,
            Db::getInstance()
        );

        return $objectModelExtended->install();
    }

    /**
     * Uninstall models
     * @return bool
     */
    public function uninstallObjectModels()
    {
        if (empty($this->module->objectModels)) {
            return true;
        }

        return array_product(array_map(array($this, 'uninstallObjectModel'), $this->module->objectModels));
    }

    /**
     * Uninstall model
     * @param string $objectModelClassName
     * @return bool
     * @throws Exception
     */
    public function uninstallObjectModel($objectModelClassName)
    {
        /** @var ObjectModel $objectModel */
        $objectModelPath = _PS_MODULE_DIR_ . 'shoppingfeed/classes/'.$objectModelClassName.'.php';
        if (file_exists($objectModelPath)) {
            require_once $objectModelPath;
        } else {
            throw new Exception('Installer error : ModelObject "' . $objectModelClassName .
                    '" not found or file "' . $objectModelPath .
                    '" doesn\'t exist. Please check a typo ?');
        }
        $objectModel = new $objectModelClassName();

        TotLoader::import('shoppingfeed\classlib\db\ObjectModelExtension');
        $objectModelExtended = new ShoppingfeedObjectModelExtension(
            $objectModel,
            Db::getInstance()
        );

        return $objectModelExtended->uninstall();
    }

    /**
     * Uninstall Configuration (with or without language management)
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function uninstallConfiguration()
    {
        $query = new DbQuery();
        $query->select('name');
        $query->from('configuration');
        $query->where('name LIKE \''.pSQL(strtoupper($this->module->name)).'_%\'');

        $results = Db::getInstance()->executeS($query);

        if (empty($results)) {
            return true;
        }

        $configurationKeys = array_column($results, 'name');

        $result = true;
        foreach ($configurationKeys as $configurationKey) {
            $result &= Configuration::deleteByName($configurationKey);
        }

        return $result;
    }

}
