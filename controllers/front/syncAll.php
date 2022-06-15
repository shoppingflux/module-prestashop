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
//backword-compatibility with php5.6
require_once _PS_MODULE_DIR_ . 'shoppingfeed/backword-compatibility.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedAddon\Exception\ProcessLockedException;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Front\CronController;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorHandler;

/**
 * This front controller receives the HTTP call for the CRON. It is used to
 * synchronize the ShoppingfeedProduct's stocks and prices.
 *
 * @see ShoppingfeedClasslib\Extensions\ProcessMonitor\CronController
 */
class ShoppingfeedSyncAllModuleFrontController extends ShoppingfeedCronController
{
    /** @var ShoppingfeedSyncProductModuleFrontController */
    protected $syncProductCron;

    /** @var ShoppingfeedSyncOrderModuleFrontController */
    protected $syncOrderCron;

    public $taskDefinition = [
        'name' => 'shoppingfeed:syncAll',
        'title' => [
            'en' => 'Sync shoppingfeed products and orders',
            'fr' => 'Sync produits et commandes shoppingfeed',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->syncOrderCron = new ShoppingfeedSyncOrderModuleFrontController();
        $this->syncProductCron = new ShoppingfeedSyncProductModuleFrontController();
    }

    protected function processCron($data)
    {
        try {
            $this->execute($this->syncProductCron, $this->module->cronTasks['syncProduct']['name']);
            $this->execute($this->syncOrderCron, $this->module->cronTasks['syncOrder']['name']);
        } catch (\Throwable $e) {
            $this->handleExeption($e);
        }

        return $data;
    }

    /**
     * @param CronController $cron
     *
     * @return void
     *
     * @throws ProcessLockedException
     * @throws Exception
     */
    protected function execute(CronController $cron, $processName)
    {
        $cron->processMonitor = new ProcessMonitorHandler();

        if (false === ($data = $cron->processMonitor->lock($processName))) {
            throw new ProcessLockedException(sprintf('Lock return false. Process %s already in run.', $processName));
        }

        try {
            Hook::exec(
                'actionProcessMonitorExecution',
                [
                    'processName' => $processName,
                    'processData' => $data,
                ],
                null,
                true
            );

            Hook::exec(
                'actionShoppingfeedProcessMonitorExecution',
                [
                    'processName' => $processName,
                    'processData' => $data,
                ],
                null,
                true
            );

            $processCron = new ReflectionMethod($cron, 'processCron');
            $processCron->setAccessible(true);
            $data = $processCron->invoke($cron, $data);
        } catch (\Exception $e) {
            throw new \Exception('Process Monitor Failed.', 0, $e);
        }

        $cron->processMonitor->unlock($data);
    }

    protected function handleExeption(Exception $e)
    {
        if ($e instanceof ProcessLockedException) {
            $return = ['success' => false, 'error' => $e->getMessage()];
            $this->ajaxDie(Tools::jsonEncode($return));
        } else {
            throw $e;
        }
    }
}
