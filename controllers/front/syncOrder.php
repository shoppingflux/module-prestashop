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

class ShoppingfeedSyncOrderModuleFrontController extends CronController
{
    public $taskDefinition = array(
        'name' => 'shoppingfeed:syncOrder',
        'title' => array(
            'en' => 'Synchronize orders on Shopping Feed',
            'fr' => 'Synchronisation des commandes sur Shopping Feed'
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

        $max_order = Configuration::get(ShoppingFeed::STATUS_MAX_ORDERS);

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "shoppingfeed_task_order WHERE ticket_number IS NULL ORDER BY date_upd ASC LIMIT " . (int)$max_order;
        $requests = DB::getInstance()->executeS($sql);


        foreach ($requests as $request) {

            try {
                $handler = new ActionsHandler();
                $handler->setConveyor(
                    array(
                        'request' => $request,
                        'url' => "urldelapi"
                    )
                )
                ->addActions('sendOrder')
                ->process('shoppingfeedOrderSync');
            } catch (Exception $e) {
                \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
                    sprintf(
                        ShoppingfeedOrderSyncActions::getLogPrefix() . ' ' . $this->l('Order %s not sended for synchronization: %s', 'ShoppingfeedOrderActions'), $request['id_order'],
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    'Order',
                    $request['id_order']
                );
            }
        }

        ProcessLoggerHandler::closeLogger();
    }
}
