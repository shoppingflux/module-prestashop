<?php
/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedAddon\Actions\ActionsHandler as SfActionsHandler;
use ShoppingfeedAddon\OrderImport\SinceDate;
use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

class ShoppingfeedSyncOrderModuleFrontController extends ShoppingfeedCronController
{
    public $taskDefinition = [
        'name' => 'shoppingfeed:syncOrder',
        'title' => [
            'en' => 'Synchronize orders on Shopping Feed',
            'fr' => 'Synchronisation des commandes sur Shopping Feed',
        ],
    ];

    /**
     * Executed by the CRON
     *
     * @param $data the data saved for this CRON (see totpsclasslib doc)
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function processCron($data)
    {
        if (Configuration::get(Shoppingfeed::ORDER_IMPORT_ENABLED)) {
            $this->importOrders();
        }

        if (Configuration::get(Shoppingfeed::ORDER_SYNC_ENABLED)) {
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

            if (!Shoppingfeed::isOrderSyncAvailable($token['id_shop'])) {
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

            $failedTicketsStatusTaskOrders = [];
            $successfulTicketsStatusTaskOrders = [];
            try {
                Registry::set('ticketsErrors', 0);

                /** @var ShoppingfeedHandler $ticketsHandler */
                $ticketsHandler = new ActionsHandler();
                $ticketsHandler->setConveyor([
                    'id_shop' => $token['id_shop'],
                    'id_token' => $token['id_shoppingfeed_token'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS,
                ]);
                $ticketsHandler->addActions(
                    'getTaskOrders',
                    'prepareTaskOrdersCheckTicketsSyncStatus',
                    'sendTaskOrdersCheckTicketsSyncStatus'
                    // Create action to send error mail and delete success ?
                );

                if ($ticketsHandler->process('ShoppingfeedOrderSync')) {
                    $processData = $ticketsHandler->getConveyor();
                    $failedTicketsStatusTaskOrders = isset($processData['failedTaskOrders']) ? $processData['failedTaskOrders'] : [];
                    $successfulTicketsStatusTaskOrders = isset($processData['successfulTaskOrders']) ? $processData['successfulTaskOrders'] : [];

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
            } catch (Throwable $e) {
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

            $failedSyncStatusTaskOrders = [];
            $successfulSyncTaskOrders = [];
            try {
                Registry::set('syncStatusErrors', 0);

                /** @var ShoppingfeedHandler $orderStatusHandler */
                $orderStatusHandler = new ActionsHandler();
                $orderStatusHandler->setConveyor([
                    'id_shop' => $token['id_shop'],
                    'id_token' => $token['id_shoppingfeed_token'],
                    'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                ]);
                $orderStatusHandler->addActions(
                    'getTaskOrders',
                    'prepareTaskOrdersSyncStatus',
                    'sendTaskOrdersSyncStatus'
                );

                if ($orderStatusHandler->process('ShoppingfeedOrderSync')) {
                    $processData = $orderStatusHandler->getConveyor();
                    $failedSyncStatusTaskOrders = isset($processData['failedTaskOrders']) ? $processData['failedTaskOrders'] : [];
                    $successfulSyncTaskOrders = isset($processData['successfulTaskOrders']) ? $processData['successfulTaskOrders'] : [];

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
            } catch (Throwable $e) {
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
                    $errorMailHandler->setConveyor([
                        'id_shop' => $token['id_shop'],
                        'id_token' => $token['id_shoppingfeed_token'],
                        'failedTaskOrders' => $failedTaskOrders,
                    ]);
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
            } catch (Throwable $e) {
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
                /* @var ShoppingfeedTaskOrder $failedTicket*/
                $handler
                    ->setConveyor([
                        'id_order' => $failedTicket->id_order,
                        'order_action' => ShoppingfeedTaskOrder::ACTION_SYNC_STATUS,
                    ])
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
        $result = [];
        $list_id_shops = [];
        foreach ($tokens as $token) {
            $id_shop = (int) $token['id_shop'];
            if (in_array($id_shop, $list_id_shops) === false) {
                $list_id_shops[] = $id_shop;
            }

            // If order import is not available
            if (!Shoppingfeed::isOrderImportAvailable($id_shop)) {
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
                if (Configuration::get(\Shoppingfeed::ORDER_IMPORT_SHIPPED) == true
                    || Configuration::get(\Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE) == true) {
                    $result = array_merge($result, $shoppingfeedApi->getUnacknowledgedOrders(true));
                }
            } catch (Throwable $e) {
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
            if (empty($result) === true) {
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
                    $handler = new SfActionsHandler();
                    $handler->addActions(
                        'registerSpecificRules',
                        'verifyOrder',
                        'createOrderCart',
                        'validateOrder',
                        'postProcess'
                    );

                    $handler->setConveyor([
                        'id_shop' => $id_shop,
                        'id_token' => $token['id_shoppingfeed_token'],
                        'id_lang' => $token['id_lang'],
                        'apiOrder' => $apiOrder,
                        'sfOrder' => null,
                        'isSkipImport' => false,
                    ]);

                    Registry::set('shoppingfeedOrderImportHandler', $handler);
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
                } catch (Throwable $e) {
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
        $this->setOrderImportSinceDate($list_id_shops);

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

    protected function setOrderImportSinceDate($list_id_shops)
    {
        $sinceDate = new SinceDate();
        $thirtyDaysAgo = (new DateTime())->sub(new DateInterval('P30D'));

        foreach ($list_id_shops as $id_shop) {
            if (new DateTime($sinceDate->get(null, $id_shop)) < $thirtyDaysAgo) {
                $sinceDate->set($thirtyDaysAgo, $id_shop);
            }

            if (new DateTime($sinceDate->getForShipped(null, $id_shop)) < $thirtyDaysAgo) {
                $sinceDate->setForShipped($thirtyDaysAgo, $id_shop);
            }

            if (new DateTime($sinceDate->getForShippedByMarketplace(null, $id_shop)) < $thirtyDaysAgo) {
                $sinceDate->setForShippedByMarketplace($thirtyDaysAgo, $id_shop);
            }
        }
    }
}
