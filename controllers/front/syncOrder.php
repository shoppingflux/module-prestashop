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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedOrderSyncActions.php');

use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController;
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
        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            $logPrefix = '[Shop ' . $shop['id_shop'] . ']';
            
            if (!ShoppingFeed::isOrderSyncAvailable($shop['id_shop'])) {
                ProcessLoggerHandler::logInfo(
                    $logPrefix . ' ' .
                        $this->module->l('Synchronization error : the Shopping Feed Official module (shoppingfluxexport) is enabled for the post-import synchronization. The “Order shipment” & “Order cancellation” options must be disabled in the official module for enabling this type of synchronization in the new module.', 'syncOrder'),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
                continue;
            }

            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->module->l('Process start : check order status tickets', 'syncOrder'),
                $this->processMonitor->getProcessObjectModelName(),
                $this->processMonitor->getProcessObjectModelId()
            );

            $failedTicketsStatusTaskOrders = array();
            $successfulTicketsTaskOrders = array();
            try {
                Registry::set('ticketsErrors', 0);
            
                /** @var ShoppingfeedHandler $ticketsHandler */
                $ticketsHandler = new ActionsHandler();
                $ticketsHandler->setConveyor(array(
                    'id_shop' => $shop['id_shop'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS,
                ));
                $ticketsHandler->addActions(
                    'getTaskOrders',
                    'prepareTaskOrdersCheckTicketsSyncStatus',
                    'sendTaskOrdersCheckTicketsSyncStatus'
                    // Create action to send error mail and delete success ?
                );

                if ($ticketsHandler->process('ShoppingfeedOrderSync')) {
                    $processData = $ticketsHandler->getConveyor();
                    $failedTicketsStatusTaskOrders = isset($processData['failedTaskOrders']) ? $processData['failedTaskOrders'] : array();
                    $successfulTicketsTaskOrders = isset($processData['successfulTaskOrders']) ? $processData['successfulTaskOrders'] : array();
                    
                    ProcessLoggerHandler::logInfo(
                        sprintf(
                            $logPrefix . ' ' . $this->module->l('%d tickets with success; %d tickets with failure; %d errors', 'syncOrder'),
                            count($successfulTicketsTaskOrders),
                            count($failedTicketsStatusTaskOrders),
                            Registry::get('ticketsErrors')
                        ),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                }

                ProcessLoggerHandler::logInfo(
                    $logPrefix . ' ' .
                        $this->module->l('Process finished : check order status tickets', 'syncOrder'),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Error : %s', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }

            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' . $this->module->l('Process start : Order sync status', 'syncOrder'),
                $this->processMonitor->getProcessObjectModelName(),
                $this->processMonitor->getProcessObjectModelId()
            );

            $failedSyncStatusTaskOrders = array();
            $successfulSyncTaskOrders = array();
            try {
                Registry::set('syncStatusErrors', 0);
                
                /** @var ShoppingfeedHandler $orderStatusHandler */
                $orderStatusHandler = new ActionsHandler();
                $orderStatusHandler->setConveyor(array(
                    'id_shop' => $shop['id_shop'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                ));
                $orderStatusHandler->addActions(
                    'getTaskOrders',
                    'prepareTaskOrdersSyncStatus',
                    'sendTaskOrdersSyncStatus'
                );

                if ($orderStatusHandler->process('ShoppingfeedOrderSync')) {
                    $processData = $orderStatusHandler->getConveyor();
                    $failedSyncStatusTaskOrders = isset($processData['failedTaskOrders']) ? $processData['failedTaskOrders'] : array();
                    $successfulSyncTaskOrders = isset($processData['successfulTaskOrders']) ? $processData['successfulTaskOrders'] : array();
                    
                    ProcessLoggerHandler::logInfo(
                        sprintf(
                            $logPrefix . ' ' . $this->module->l('%d tickets created; %d tickets not created; %d errors', 'syncOrder'),
                            count($successfulSyncTaskOrders),
                            count($failedSyncStatusTaskOrders),
                            Registry::get('syncStatusErrors')
                        ),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                }

                ProcessLoggerHandler::logInfo(
                    $logPrefix . ' ' . $this->module->l('Process finished : Order sync status', 'syncOrder'),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Error : %s', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }

            // Send mail
            try {
                $failedTaskOrders = array_merge($failedSyncStatusTaskOrders, $failedTicketsStatusTaskOrders);
                
                $errorMailHandler = new ActionsHandler();
                $errorMailHandler->setConveyor(array(
                    'id_shop' => $shop['id_shop'],
                    'failedTaskOrders' => $failedTaskOrders,
                ));
                $errorMailHandler->addActions(
                    'sendFailedTaskOrdersMail'
                );

                if (!$errorMailHandler->process('ShoppingfeedOrderSync')) {
                    ProcessLoggerHandler::logError(
                        $logPrefix . ' ' . 
                            $this->module->l('Failed to send mail with Orders errors.', 'syncOrder'),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                }
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Failed to send mail with Orders errors : %s', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }
            
            // Delete all processed task orders
            $processedTaskOrders = array_merge(
                $successfulTicketsTaskOrders,
                $successfulSyncTaskOrders,
                $failedTaskOrders
            );
            foreach($processedTaskOrders as $taskOrder) {
                $taskOrder->delete();
            }
            
        }

        ProcessLoggerHandler::closeLogger();
    }
}
