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
                    $failedTicketsStatusTaskOrders = $processData['failedTaskOrders'];
                    $successfulTicketsTaskOrders = $processData['successfulTaskOrders'];
                }
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Fail : %s', 'syncOrder'),
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
                    // Create action to send error mail and delete success ?
                );

                if ($orderStatusHandler->process('ShoppingfeedOrderSync')) {
                    $processData = $ticketsHandler->getConveyor();
                    $failedSyncStatusTaskOrders = $processData['failedTaskOrders'];
                    $successfulSyncTaskOrders = $processData['successfulTaskOrders'];
                }
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Fail : %s', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }

            // Send mail
            // TODO : move this somewhere else; actions class ?
            try {
                $id_lang = (int)Configuration::get('PS_LANG_DEFAULT', null, null, $shop['id_shop']);
                $failedTaskOrders = array_merge($failedSyncStatusTaskOrders, $failedTicketsStatusTaskOrders);
                
                $listFailuresHtml = $this->getEmailTemplateContent(
                    'order-sync-errors-list.tpl',
                    $id_lang,
                    $shop['id_shop'],
                    array('failedTaskOrders' => $failedTaskOrders)
                );
                $listFailuresTxt = $this->getEmailTemplateContent(
                    'order-sync-errors-list.txt',
                    $id_lang,
                    $shop['id_shop'],
                    array('failedTaskOrders' => $failedTaskOrders)
                );
                
                Mail::Send(
                    (int)$id_lang,
                    'order-sync-errors',
                    $this->module->l('Shopping Feed synchronization errors', 'syncOrder'),
                    array(
                        'list_order_sync_errors_html' => $listFailuresHtml,
                        'list_order_sync_errors_txt' => $listFailuresTxt,
                        'cron_task_url' => $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor'),
                        'log_page_url' => $this->context->link->getAdminLink('AdminShoppingfeedProcessLogger'),
                    ),
                    Configuration::get('PS_SHOP_EMAIL', null, null, $shop['id_shop']),
                    null, null, null, null, null,
                    _PS_MODULE_DIR_ . $this->module->name . '/mails/',
                    false,
                    $shop['id_shop']
                );
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' . $this->module->l('Failed to send mail with errors.', 'syncOrder'),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    $this->processMonitor->getProcessObjectModelName(),
                    $this->processMonitor->getProcessObjectModelId()
                );
            }
        }

        ProcessLoggerHandler::closeLogger();
    }
    
    /**
     * Finds a module mail template for the specified lang and shop
     * 
     * @param string $template_name template name with extension
     * @param int $id_lang
     * @param int $id_shop
     * @param array $var will be assigned to the smarty template
     *
     * @return string the template's content, or an empty string if no template
     * was found
     */
    protected function getEmailTemplateContent($template_name, $id_lang, $id_shop, $var)
    {
        $isoLang = Language::getIsoById($id_lang);
        $shop = new Shop($id_shop);
        if (isset($shop->theme)) {
            // PS17
            $themeName = $this->context->shop->theme->getName();
        } else {
            // PS16
            $themeName = $shop->theme_name;
        }
        
        $pathsToCheck = array(
            _PS_ALL_THEMES_DIR_ . $themeName . '/' . $this->module->name . '/mails/' . $isoLang . '/' . $template_name,
            _PS_MODULE_DIR_ . $this->module->name . '/mails/' . $isoLang . '/' . $template_name,
            _PS_ALL_THEMES_DIR_ . $themeName . '/' . $this->module->name . '/mails/en/' . $template_name,
            _PS_MODULE_DIR_ . $this->module->name . '/mails/en/' . $template_name,
        );
        
        $templatePath = '';
        foreach ($pathsToCheck as $path) {
            if (Tools::file_exists_cache($path)) {
                $templatePath = $path;
                break;
            }
        }
        
        if (!$templatePath) {
            return '';
        }
        
        // Multi-shop / multi-theme might not work properly when using
        // the basic "$context->smarty->createTemplate($tpl_name)" syntax, as
        // the template's compile_id will be the same for every shop / theme
        // See https://github.com/PrestaShop/PrestaShop/pull/13804
        $scope = $this->context->smarty->createData($this->context->smarty);
        $scope->assign($var);

        return $this->context->smarty->createTemplate(
            $templatePath,
            $scope,
            $themeName
        )->fetch();
    }
}
