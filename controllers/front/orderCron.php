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

use ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController;
use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

/**
 * This front controller receives the HTTP call for the CRON. It is used to
 * synchronize the ShoppingfeedProduct's stocks and prices.
 * @see ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController
 */
class ShoppingfeedOrderCronModuleFrontController extends CronController
{
    public $taskDefinition = array(
        'name' => 'shoppingfeed:orderCron',
        'title' => array(
            'en' => 'Order cron',
            'fr' => 'Commande cron'
        ),
    );

    /**
     * Executed by the CRON
     * @param $data the data saved for this CRON (see totpsclasslib doc)
     * @return mixed
     * @throws Exception
     */
    protected function processCron($data)
    {
        ProcessLoggerHandler::openLogger($this->processMonitor);
        if (!ShoppingFeed::checkImportExportValidity()) {
            ProcessLoggerHandler::logInfo(
                'Synchronization error : the Shopping Feed Official module (shoppingfluxexport) is enabled for the post-import synchronization. The “Order shipment” & “Order cancellation” options must be disabled in the official module for enabling this type of synchronization in the new module.',
                $this->processMonitor->getProcessObjectModelName(),
                $this->processMonitor->getProcessObjectModelId()
            );
            ProcessLoggerHandler::closeLogger();
            return null;
        }

        ProcessLoggerHandler::closeLogger();
    }

    protected function processAction($action, $actions_suffix)
    {
    }
}
