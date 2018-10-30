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

TotLoader::import('shoppingfeed\classlib\extensions\ProcessMonitor\CronController');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');

/**
 * This front controller receives the HTTP call for the CRON. It is used to synchronize the ShoppingfeedProduct's stocks.
 * @see ShoppingfeedCronController
 */
class ShoppingfeedSyncStockModuleFrontController extends ShoppingfeedCronController
{
    public $taskDefinition = array(
        'name' => 'shoppingfeed:syncStock',
        'title' => array(
            'en' => 'Sync shoppingfeed stock',
            'fr' => 'Sync shoppingfeed stock'
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
        TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\ProcessLoggerHandler');
        TotLoader::import('shoppingfeed\classlib\registry');

        ShoppingfeedProcessLoggerHandler::openLogger($this->processMonitor);
        ShoppingfeedProcessLoggerHandler::logInfo(
            $this->module->l('[Stock] Process start', 'syncStock'),
            $this->processMonitor->getProcessObjectModelName(),
            $this->processMonitor->getProcessObjectModelId()
        );

        try {
            /** @var ShoppingfeedHandler $handler */
            $handler = TotLoader::getInstance('shoppingfeed\classlib\actions\handler');
            $handler->addActions('getBatch');
            $shops = Shop::getShops();
            foreach ($shops as $shop) {
                $handler->setConveyor(array('id_shop' => $shop['id_shop']));
                $handler->process('shoppingfeedProductStockSync');
            }
        } catch (Exception $e) {
            ShoppingfeedProcessLoggerHandler::logError(
                sprintf(
                    $this->module->l('[Stock] Fail : %s', 'syncStock'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                )
            );
            ShoppingfeedRegistry::increment('errors');
        }

        ShoppingfeedProcessLoggerHandler::closeLogger(
            sprintf(
                $this->module->l('[Stock] %d products updated - %d not in catalog - %d errors', 'syncStock'),
                (int)ShoppingfeedRegistry::get('updatedProducts'),
                (int)ShoppingfeedRegistry::get('not-in-catalog'),
                (int)ShoppingfeedRegistry::get('errors')
            )
        );
        Configuration::updateValue(shoppingfeed::LAST_CRON_TIME_SYNCHRONIZATION, date("Y-m-d H:i:s"));
        // The data to be saved in the CRON table
        return $data;
    }
}
