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

use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Front\CronController;
use ShoppingfeedClasslib\Actions\ActionsHandler;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

/**
 * This front controller receives the HTTP call for the CRON. It is used to
 * synchronize the ShoppingfeedProduct's stocks and prices.
 * @see ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController
 */
class ShoppingfeedSyncProductModuleFrontController extends ShoppingfeedCronController
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
        if ((bool)Configuration::get(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD)) {
            $this->addFlagUpdatePreloadingTableByDateUpdate();
            $this->addTaskSyncProduct();
        } else if ($this->ifPreloadingTableNeedToBeUpdateAllTime()) {
            $this->addFlagUpdatePreloadingTableByLastUpdate();
        }

        $actions = array();
        if (Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED)) {
            $actions[ShoppingfeedProduct::ACTION_SYNC_STOCK] = array(
                'actions_suffix' => 'Stock'
            );
        }
        if (Configuration::get(Shoppingfeed::PRICE_SYNC_ENABLED)) {
            $actions[ShoppingfeedProduct::ACTION_SYNC_PRICE] = array(
                'actions_suffix' => 'Price'
            );
        }

        $actions[ShoppingfeedPreloading::ACTION_SYNC_PRELODING] = array(
            'actions_suffix' => 'Preloading'
        );

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
        ProcessLoggerHandler::logInfo(
            '[' . $actionClassname  . '] ' . $this->module->l('Process start', 'syncProduct'),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
        Registry::set('updatedProducts', 0);
        Registry::set('not-in-catalog', 0);
        Registry::set('errors', 0);

        /** @var ShoppingfeedHandler $handler */
        $handler = new ActionsHandler();
        $handler->addActions('getBatch');
        $sft = new ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        try {
            foreach ($tokens as $token) {
                $logPrefix = $actionClassname::getLogPrefix($token['id_shoppingfeed_token']);
                $handler->setConveyor(array(
                    'id_token' => $token['id_shoppingfeed_token'],
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
                '[' . $actionClassname  . '] ' . $this->module->l('%d products updated - %d not in catalog - %d errors', 'syncProduct'),
                (int)Registry::get('updatedProducts'),
                (int)Registry::get('not-in-catalog'),
                (int)Registry::get('errors')
            ),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );
    }

    private function addFlagUpdatePreloadingTableByDateUpdate()
    {
        Db::getInstance()->execute($this->getSqlUpdatePreloadingTable(\Product::$definition['table']));

        if (Shop::isFeatureActive()) {
            Db::getInstance()->execute($this->getSqlUpdatePreloadingTable(\Product::$definition['table'] . '_shop'));
        }
    }

    private function getSqlUpdatePreloadingTable($tableProduct)
    {
        return sprintf('
            UPDATE %1$s%2$s sfp
            INNER JOIN %1$s%3$s p ON p.id_product = sfp.id_product and sfp.date_upd < p.date_upd
            SET `actions` = "[\"SYNC_ALL\"]"',
            _DB_PREFIX_,
            ShoppingfeedPreloading::$definition['table'],
            $tableProduct
        );
    }

    private function addTaskSyncProduct()
    {
        return Db::getInstance()->execute($this->getSqlAddTaskSyncProduct('SYNC_PRICE'))
            && Db::getInstance()->execute($this->getSqlAddTaskSyncProduct('SYNC_STOCK'))
        ;
    }

    private function getSqlAddTaskSyncProduct($task)
    {
        return sprintf(
        '
            INSERT IGNORE INTO %1$sshoppingfeed_product
            (action, id_product, id_product_attribute, id_token, update_at, date_add, date_upd)
            select "%2$s", sp.id_product, IFNULL(sa.id_product_attribute, 0), sp.id_token, now(), now(), now()
            from %1$sshoppingfeed_preloading sp
            LEFT JOIN %1$sproduct_attribute sa on sp.id_product = sa.id_product
            JOIN %1$sproduct p on p.id_product = sp.id_product
            join %1$sshoppingfeed_token st on st.id_shoppingfeed_token = sp.id_token
            JOIN %1$sproduct_shop ps on ps.id_product = sp.id_product and ps.id_shop = st.id_shop
            where sp.date_upd < p.date_upd or sp.date_upd < ps.date_upd;
            ',
            _DB_PREFIX_,
            $task
        );
    }

    private function ifPreloadingTableNeedToBeUpdateAllTime()
    {
        $interval_full_update  = (int)Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE);
        $interval_cron = (int)Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON);

        return (bool)Configuration::get(Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD) === false
               && $interval_full_update > 0 && $interval_cron > 0;
    }

    private function addFlagUpdatePreloadingTableByLastUpdate()
    {
        $tokens = (new ShoppingfeedToken())->findAllActive();
        $interval_full_update  = (int)Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_TIME_FULL_UPDATE);
        $interval_cron = (int)Configuration::getGlobalValue(Shoppingfeed::PRODUCT_FEED_INTERVAL_CRON);
        $shoppingfeedPreloading = new ShoppingfeedPreloading();

        foreach ($tokens as $token) {
            $countPreloading = (int)$shoppingfeedPreloading->getPreloadingCountForSync($token['id_shoppingfeed_token']);

            $sql = sprintf('
                UPDATE %s%s SET actions = "[\"SYNC_ALL\"]" where id_token = %d order by date_upd, id_product ASC limit 1',
                _DB_PREFIX_,
                ShoppingfeedPreloading::$definition['table'],
                $token['id_shoppingfeed_token'],
                ceil($countPreloading * $interval_cron / ($interval_full_update * 60))
            );

            Db::getInstance()->execute($sql);
        }
    }
}
