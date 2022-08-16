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

namespace ShoppingfeedClasslib\Extensions\Diagnostic;

use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\ConflictsStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\FileIntegrityStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\GithubVersionStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\HooksStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\HostStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\OverridesStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\ConnectStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\LogsStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\ConfigurationStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\DatabaseStub;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Storage\DiagnosticRetriever;
use ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Storage\StubStorage;
use ShoppingfeedClasslib\Extensions\AbstractModuleExtension;
use ShoppingfeedClasslib\Extensions\Diagnostic\Controllers\Admin\AdminDiagnosticController;
use Configuration;

class DiagnosticExtension extends AbstractModuleExtension
{
    public $name = 'diagnostic';

    public $extensionAdminControllers = [
        [
            'name' => [
                'en' => 'Diagnostic',
                'fr' => 'Diagnostique',
            ],
            'class_name' => 'AdminShoppingfeedDiagnostic',
            'parent_class_name' => 'shoppingfeed',
            'visible' => true,
        ],
    ];

    public $objectModels = [];

    const MODULE_NAME = 'SHOPPINGFEED_MODULE_NAME';

    const DIAGNOSTIC_MODULE_NAME = 'SHOPPINGFEED_DIAGNOSTIC_MODULE_NAME';

    const CONNECT_EMPLOYEE = 'SHOPPINGFEED_CONNECT_EMPLOYEE';

    const CONNECT_SECURE_KEY = 'SHOPPINGFEED_CONNECT_SECRET_KEY';

    const CONNECT_RESTRICTED_IPS = 'SHOPPINGFEED_CONNECT_RESTRICTED_IPS';

    const CONNECT_SLUG = 'SHOPPINGFEED_CONNECT_SLUG';

    public function install()
    {
        Configuration::updateGlobalValue(self::MODULE_NAME, $this->module->name);
        Configuration::updateGlobalValue(self::DIAGNOSTIC_MODULE_NAME, $this->module->name);

        return parent::install();
    }

    public function initExtension()
    {
        parent::initExtension();

        $stubStorage = StubStorage::getInstance();
        $diagnosticRetriever = new DiagnosticRetriever();
        $diagnosticConfig = $diagnosticRetriever->retrieveCurrent();
        if (empty($diagnosticConfig)) {
            return;
        }

        $stubStorage->setModuleConfigModel($diagnosticConfig);
    }
}
