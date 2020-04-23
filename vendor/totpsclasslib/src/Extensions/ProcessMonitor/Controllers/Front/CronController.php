<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 * @version   release/2.3.1
 */

namespace ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Front;

use ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorHandler;

use \Tools;
use \Hook;

abstract class CronController extends \ModuleFrontController
{
    /** @var \Shoppingfeed Instance of your Module, set automatically by ModuleFrontController */
    public $module;

    /** @var bool If set to true, will redirected user to login page during init function. */
    public $auth = false;

    /** @var bool SSL connection flag, can be used to force https */
    public $ssl = false;

    /** @var ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorHandler
     * Instance of ProcessMonitorHandler
     */
    public $processMonitor;

    /**
     * Retrieve the technical name of the task defined in module
     *
     * @return string
     * @throws \Exception
     */
    public function getProcessName()
    {
        $controller = Tools::getValue('controller');
        if (isset($this->module->cronTasks[$controller]) && isset($this->module->cronTasks[$controller]['name'])) {
            return $this->module->cronTasks[$controller]['name'];
        }
        throw new \Exception('Unable to find process name');
    }

    /**
     * This add an additional security to prevent unauthorized people/bot to execute cron
     * To execute this file the secure_key must be set in url params
     * This method is used to prevent spam in some native module like sendtoafriend
     * You have to determine if it can be useful in your case (if you send mail or do hard/long process)
     * @return bool
     */
    public function checkAccess()
    {
        header('Content-type: text/plain');
        $result = parent::checkAccess();
        if (false === Tools::isSubmit('secure_key') || Tools::getValue('secure_key') !== $this->module->secure_key) {
            $result &= false;
        }
        if (false === $result) {
            $return = array('success' => false, 'error' => 'Authentication failed');
            $this->ajaxDie(Tools::jsonEncode($return));
        }

        return $result;
    }

    /**
     * If checkAccess return false, Controller call this instead of initContent()
     */
    public function initCursedPage()
    {
        //Display an error or do a redirect or just exit
        //To prevent bots brute force, usage of sleep(20) can be useful to discourage bots to retry
        exit;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function initContent()
    {
        $this->processMonitor = new ProcessMonitorHandler();
        $processName = $this->getProcessName();
        if (false === ($data = $this->processMonitor->lock($processName))) {
            $return = array('success' => false, 'error' => 'Lock return false. Process ID already in run.');
            $this->ajaxDie(Tools::jsonEncode($return));
        }

        try {
        
            Hook::exec(
                    'actionProcessMonitorExecution',
                    array(
                        'processName' => $processName,
                        'processData' => $data,
                    ),
                    null,
                    true
            );

            Hook::exec(
                    'actionShoppingfeedProcessMonitorExecution',
                    array(
                        'processName' => $processName,
                        'processData' => $data,
                    ),
                    null,
                    true
            );
        
            $data = $this->processCron($data);
        } catch (\Exception $e) {
            throw new \Exception('Process Monitor Failed.', 0, $e);
        }

        $this->processMonitor->unlock($data);

        $return = array('success' => true);
        $this->ajaxDie(Tools::jsonEncode($return));
    }

    /**
     * To be defined your process
     *
     * @param array $data
     * @return array
     */
    protected function processCron($data)
    {
        return $data;
    }
}
