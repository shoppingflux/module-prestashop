<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 * @version   feature/34626_diagnostic
 */

namespace ShoppingfeedClasslib\Extensions\Diagnostic\Controllers\Admin;

use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\AbstractStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Storage\StubStorage;
use ShoppingfeedClasslib\Extensions\Diagnostic\DiagnosticExtension;
use ShoppingfeedClasslib\Module\Module;
use Configuration;
use Context;
use HelperForm;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\GithubVersionStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\HooksStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\OverridesStub;
use Media;
use Tools;
use ZipArchive;

/**
 * @include 'shoppingfeed/views/templates/admin/diagnostic/export.tpl'
 * @include 'shoppingfeed/views/js/diagnostic/diagnostic.js.map'
 */
class AdminDiagnosticController extends \ModuleAdminController
{
    public $bootstrap = false;

    public $override_folder;

    /**
     * @var int
     */
    public $multishop_context = 0;

    public $className = 'Configuration';

    public $table = 'configuration';

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS(_PS_MODULE_DIR_ . 'shoppingfeed/views/js/diagnostic/diagnostic.js');
        $this->addCSS(_PS_MODULE_DIR_ . 'shoppingfeed/views/css/diagnostic/diagnostic.css');

        Media::addJsDef([
            $this->module->name => $this->getJsVariables(),
        ]);
    }

    public function initContent()
    {
        $this->content .= $this->renderConfiguration();
        parent::initContent();
    }

    protected function renderConfiguration()
    {
        $tplFile = _PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/diagnostic/layout.tpl';

        $actionsLink = Context::getContext()->link->getAdminLink(
            $this->controller_name,
            true
        ) . '&stubAction=true';
        $exportStubLink = Context::getContext()->link->getAdminLink(
            $this->controller_name,
            true
        ) . '&stubExport=true';

        Context::getContext()->smarty->assign([
            'actionsLink' => $actionsLink,
            'exportStubLink' => $exportStubLink,
        ]);
        $tpl = Context::getContext()->smarty->createTemplate($tplFile);
        $tpl->assign([
            'stubs' => $this->getStubs(),
            'exportStubLink' => $exportStubLink,
            'actionsLink' => $actionsLink,
        ]);

        return $tpl->fetch();
    }

    protected function getStubs()
    {
        $stubStorage = StubStorage::getInstance();
        $stubs = [];

        if (empty($stubStorage->getModuleConfigModel())) {
            return [];
        }

        /** @var string $stub */
        foreach ($stubStorage->getModuleConfigModel()->getStubs() as $stub => $parameters) {
            /** @var AbstractStub $stubObj */
            $stubObj = new $stub($parameters);
            $stubObj->setModule($this->getStubModule());
            $stubs[] = $stubObj->fetch();
        }

        return $stubs;
    }

    protected function getJsVariables()
    {
        return [
            'actionLink' => Context::getContext()->link->getAdminLink(
                $this->controller_name,
                true
            ) . '&stubAction=true',

        ];
    }

    public function postProcess()
    {
        if (Tools::getIsset('stubAction')) {
            $event = Tools::getValue('event');
            if (empty($event)) {
                Tools::redirectAdmin(Context::getContext()->link->getAdminLink($this->controller_name));
            }
            $stubStorage = StubStorage::getInstance();

            if (!empty($stubStorage->getModuleConfigModel())) {
                /** @var AbstractStub $stub */
                foreach ($stubStorage->getModuleConfigModel()->getStubs() as $stub => $parameters) {
                    $stubObj = new $stub($parameters);
                    $stubObj->setModule($this->getStubModule());
                    if ($stubObj->hasEvent($event)) {
                        $stubObj->dispatchEvent($event, Tools::getAllValues());
                    }
                }
            }

            Tools::redirectAdmin(Context::getContext()->link->getAdminLink($this->controller_name));
        }

        if (Tools::getIsset('stubExport')) {
            $this->processStubExport();
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink($this->controller_name));
        }

        parent::postProcess();
    }

    public function processStubExport()
    {
        $stubStorage = StubStorage::getInstance();

        if (!empty($stubStorage->getModuleConfigModel())) {
            $zipPath = _PS_MODULE_DIR_ . $this->module->name . '/export.zip';
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            $zip = new ZipArchive();

            if (!$zip->open($zipPath, ZipArchive::CREATE)) {
                throw new \PrestaShopException('Failed to create zip file');
            }

            /** @var AbstractStub $stub */
            foreach ($stubStorage->getModuleConfigModel()->getStubs() as $stub => $parameters) {
                /** @var AbstractStub $stubObj */
                $stubObj = new $stub($parameters);
                $stubObj->setModule($this->getStubModule());
                if (!$stubObj->isHasExport()) {
                    continue;
                }
                $content = $stubObj->getHandler()->export(false);
                if (empty($content)) {
                    continue;
                }

                foreach ($content as $fileName => $data) {
                    $zip->addFromString($fileName, $data);
                }
            }

            $zip->close();

            header("Content-type: application/zip");
            $moduleName = $this->getStubModule()->name;
            header("Content-Disposition: attachment; filename=export.$moduleName.zip");
            header("Content-length: " . filesize($zipPath));
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($zipPath);
            unlink($zipPath);
        }
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_title = sprintf($this->module->l('Diagnostic %s'), Configuration::get(DiagnosticExtension::MODULE_NAME));
    }

    protected function getStubModule()
    {
        $moduleName = Configuration::get(DiagnosticExtension::MODULE_NAME);
        $module = null;
        if (!empty($moduleName)) {
            $module = Module::getInstanceByName($moduleName);
        }

        return $module;
    }
}
