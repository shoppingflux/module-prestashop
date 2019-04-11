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

use ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController;
use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

/**
 * This front controller receives the HTTP call for the CRON. It is used to
 * synchronize the ShoppingfeedProduct's stocks and prices.
 * @see ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController
 */
class ShoppingfeedSyncProductModuleFrontController extends CronController
{
    public $taskDefinition = array(
        'name' => 'shoppingfeed:syncProduct',
        'title' => array(
            'en' => 'Sync shoppingfeed products',
            'fr' => 'Sync produits shoppingfeed'
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
        $actions = array();
        if (Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED)) {
            $actions[ShoppingFeedProduct::ACTION_SYNC_STOCK] = array(
                'actions_suffix' => 'Stock'
            );
        }
        if (Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED)) {
            $actions[ShoppingFeedProduct::ACTION_SYNC_PRICE] = array(
                'actions_suffix' => 'Price'
            );
        }
        
        if (empty($actions)) {
            // The data to be saved in the CRON table
            return $data;
        }
        
        ProcessLoggerHandler::openLogger($this->processMonitor);
        foreach ($actions as $action => $actionData) {
            $this->processAction($action, $actionData['actions_suffix']);
        }
        
        Configuration::updateValue(ShoppingFeed::LAST_CRON_TIME_SYNCHRONIZATION, date("Y-m-d H:i:s"));
        ProcessLoggerHandler::closeLogger();
    }
    
    protected function processAction($action, $actions_suffix)
    {
        $actionClassname = 'ShoppingfeedProductSync' . $actions_suffix . 'Actions';
        $logPrefix = $actionClassname::getLogPrefix();
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' . $this->module->l('Process start', 'syncProduct'),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );

        try {
            Registry::set('updatedProducts', 0);
            Registry::set('not-in-catalog', 0);
            Registry::set('errors', 0);
                    
            /** @var ShoppingfeedHandler $handler */
            $handler = new ActionsHandler();
            $handler->addActions('getBatch');
            $shops = Shop::getShops();
            foreach ($shops as $shop) {
                $handler->setConveyor(array(
                    'id_shop' => $shop['id_shop'],
                    'product_action' => $action,
                ));

                $processResult = $handler->process('shoppingfeedProductSync' . $actions_suffix);
                if (!$processResult) {
                    ProcessLoggerHandler::logError(
                        $logPrefix . ' ' . $this->module->l('Fail : An error occurred during process.', 'syncProduct'),
                        $this->processMonitor->getProcessObjectModelName(),
                        $this->processMonitor->getProcessObjectModelId()
                    );
                    Registry::increment('errors');
                }
            }
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    $logPrefix . ' ' . $this->module->l('Fail : %s', 'syncProduct'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ),
                $this->processMonitor->getProcessObjectModelName(),
                $this->processMonitor->getProcessObjectModelId()
            );
            Registry::increment('errors');
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix . ' ' . $this->module->l('%d products updated - %d not in catalog - %d errors', 'syncProduct'),
                (int)Registry::get('updatedProducts'),
                (int)Registry::get('not-in-catalog'),
                (int)Registry::get('errors')
            ),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
    }
}
