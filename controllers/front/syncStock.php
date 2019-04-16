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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/controllers/front/syncProduct.php');

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * This front controller receives the HTTP call for the CRON. It is used to
 * synchronize the ShoppingfeedProduct's stocks.
 * @deprecated The syncProduct controller now manages all product updates.
 * @see ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController
 */
class ShoppingfeedSyncStockModuleFrontController extends ShoppingfeedSyncProductModuleFrontController
{
    protected function processCron($data)
    {
        $deprecatedWarning = $this->module->l('WARNING : This task has been renamed to shoppingfeed:syncProduct. Your CRON task is still using the URL to the shoppingfeed:syncStock task.', 'syncStock');
        
        // Open and close the logger immediately since it's not even supposed
        // to be open when the process starts
        ProcessLoggerHandler::openLogger($this->processMonitor);
        ProcessLoggerHandler::logError(
            $deprecatedWarning,
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
        ProcessLoggerHandler::closeLogger();
        
        parent::processCron($data);
        
        ProcessLoggerHandler::openLogger($this->processMonitor);
        ProcessLoggerHandler::logError(
            $deprecatedWarning,
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
        ProcessLoggerHandler::closeLogger();
    }
}
