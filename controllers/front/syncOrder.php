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

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Front\CronController;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

class ShoppingfeedSyncOrderModuleFrontController extends ShoppingfeedCronController
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
        if (Configuration::get(ShoppingFeed::ORDER_IMPORT_ENABLED)) {
            $this->importOrders();
        }

        if (Configuration::get(ShoppingFeed::ORDER_SYNC_ENABLED)) {
            $this->syncOrderStatus();
        }

        ProcessLoggerHandler::closeLogger();
        return $data;
    }

    public function syncOrderStatus()
    {
        ProcessLoggerHandler::openLogger($this->processMonitor);

        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        $result = [];
        foreach ($tokens as $token) {
            $logPrefix = '[Shop ' . $token['id_shop'] . ']';

            if (!ShoppingFeed::isOrderSyncAvailable($token['id_shop'])) {
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
            $successfulTicketsStatusTaskOrders = array();
            try {
                Registry::set('ticketsErrors', 0);

                /** @var ShoppingfeedHandler $ticketsHandler */
                $ticketsHandler = new ActionsHandler();
                $ticketsHandler->setConveyor(array(
                    'id_shop' => $token['id_shop'],
                    'id_token' => $token['id_shoppingfeed_token'],
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
                    $successfulTicketsStatusTaskOrders = isset($processData['successfulTaskOrders']) ? $processData['successfulTaskOrders'] : array();

                    ProcessLoggerHandler::logSuccess(
                        sprintf(
                            $logPrefix . ' ' . $this->module->l('%d tickets with success; %d tickets with failure; %d errors', 'syncOrder'),
                            count($successfulTicketsStatusTaskOrders),
                            count($failedTicketsStatusTaskOrders),
                            Registry::get('ticketsErrors')
                        ),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                }

                ProcessLoggerHandler::logSuccess(
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
                    'id_shop' => $token['id_shop'],
                    'id_token' => $token['id_shoppingfeed_token'],
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

                    ProcessLoggerHandler::logSuccess(
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

                if (!empty($failedTaskOrders)) {
                    $errorMailHandler = new ActionsHandler();
                    $errorMailHandler->setConveyor(array(
                        'id_shop' => $token['id_shop'],
                        'id_token' => $token['id_shoppingfeed_token'],
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
                $successfulTicketsStatusTaskOrders,
                $failedTicketsStatusTaskOrders
            );
            foreach ($processedTaskOrders as $taskOrder) {
                $taskOrder->delete();
            }

            //Resend failed tickets.
            $handler = new ActionsHandler();

            foreach ($failedTicketsStatusTaskOrders as $failedTicket) {
                /** @var ShoppingfeedTaskOrder $failedTicket*/
                $handler
                    ->setConveyor(array(
                        'id_order' => $failedTicket->id_order,
                        'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                    ))
                    ->addActions('saveTaskOrder');
                $handler->process('shoppingfeedOrderSync');
            }
        }
    }

    public function importOrders()
    {
        ProcessLoggerHandler::openLogger($this->processMonitor);

        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        foreach ($tokens as $token) {
            $id_shop = (int) $token['id_shop'];

            // If order import is not available
            if (!ShoppingFeed::isOrderImportAvailable($id_shop)) {
                ProcessLoggerHandler::logInfo(
                    $this->module->l('The Shopping Feed module (shoppingfluxexport) is installed on your shop for enabling the orders import synchronization. The “Order importation” option must be disabled in the module for enabling this type of synchronization in this module. If you disable the options in the shoppingfluxexport\'s module and you enable it again later the button "New orders import" will be disabled automatically in the Shopping feed 15 min module.', 'syncOrder'),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }

            try {
                $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($token['id_shoppingfeed_token']);
                if ($shoppingfeedApi == false) {
                    ProcessLoggerHandler::logError(
                        $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedSyncOrderModuleFrontController'),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                    return false;
                }

                $result = $shoppingfeedApi->getUnacknowledgedOrders();
                if (Configuration::get(\Shoppingfeed::ORDER_IMPORT_SHIPPED) == true) {
                    $result = array_merge($result, $shoppingfeedApi->getUnacknowledgedOrders(true));
                }
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $this->module->l('Could not retrieve orders to import : %s.', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
                return false;
            }
            if (!count($result)) {
                ProcessLoggerHandler::logInfo(
                    $this->module->l('No orders to import.', 'syncOrder'),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
                continue;
            }

            Registry::set('errors', 0);
            Registry::set('importedOrders', 0);
            foreach ($result as $apiOrder) {
                $logPrefix = sprintf(
                    $this->module->l('[Order: %s]', 'syncOrder'),
                    $apiOrder->getId()
                );
                $logPrefix .= '[' . $apiOrder->getReference() . '] ';

                try {

                    /** @var ShoppingfeedHandler $handler */
                    $handler = new ActionsHandler();
                    $handler->addActions(
                        'registerSpecificRules',
                        'verifyOrder',
                        'createOrderCart',
                        'validateOrder',
                        'acknowledgeOrder',
                        'recalculateOrderPrices',
                        'postProcess'
                    );

                    $handler->setConveyor(array(
                        'id_shop' => $id_shop,
                        'id_token' => $token['id_shoppingfeed_token'],
                        'apiOrder' => $apiOrder,
                    ));

                    $processResult = $handler->process('shoppingfeedOrderImport');
                    if (!$processResult) {
                        $conveyor = $handler->getConveyor();
                        ProcessLoggerHandler::logError(
                            $logPrefix .
                                $this->module->l('Fail : An error occurred during process.', 'syncOrder'),
                            $this->processMonitor->getProcessObjectModelName(),
                            $this->processMonitor->getProcessObjectModelId()
                        );
                        Registry::increment('errors');
                        continue;
                    }

                    $conveyor = $handler->getConveyor();
                    ProcessLoggerHandler::logSuccess(
                        $logPrefix .
                            $this->module->l('Import successful', 'ShoppingfeedOrderImportActions'),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                    Registry::increment('importedOrders');
                } catch (Exception $e) {
                    ProcessLoggerHandler::logError(
                        $logPrefix .
                            sprintf(
                                $this->module->l('Fail : %s', 'syncOrder'),
                                $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                            ),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                    Registry::increment('errors');
                }
            }
        }

        ProcessLoggerHandler::logSuccess(
            sprintf(
                $this->module->l('%d orders to import; %d success; %d errors', 'ShoppingfeedOrderImportActions'),
                count($result),
                Registry::get('importedOrders'),
                Registry::get('errors')
            ),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
    }
}
