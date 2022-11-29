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

    public function checkAccess()
    {
        header('Content-type: text/plain');

        if (empty((new ShoppingfeedToken())->findByToken(Tools::getValue('token', '')))) {
            $return = ['success' => false, 'error' => 'Authentication failed'];
            $this->ajaxDie(json_encode($return));
        }

        return true;
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
            $this->ajaxDie(json_encode($return));
        } else {
            throw $e;
        }
    }
}
