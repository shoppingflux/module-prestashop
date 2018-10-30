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
 * @version   release/1.2.0
 */

class ShoppingfeedProcessMonitorHandler
{
    /**
     * @var ShoppingfeedProcessMonitorObjectModel $process
     */
    protected $process;
    
    /**
     * @var float $startTime microtime
     */
    public $startTime;
    
    /**
     * Lock process
     *
     * @param string $name
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function lock($name)
    {
        $this->startTime = $this->microtimeFloat();
        $processMonitorObjectModel = TotLoader::getInstance('shoppingfeed\classlib\extensions\ProcessMonitor\ProcessMonitorObjectModel');
        $this->process = $processMonitorObjectModel->findOneByName($name);
        if (empty($this->process->id)) {
            $this->process = new ShoppingfeedProcessMonitorObjectModel();
            $this->process->name = $name;
            $this->process->data = Tools::jsonEncode(array());
        }
        if (!empty($this->process->pid)) {
            $oldpid = $this->process->pid;
            exec("ps -ef| awk '\$3 == \"$oldpid\" { print \$2 }'", $output, $ret); //get pid of cron process
            if (false === empty($output)) {
                return false;
            }
        }

        $this->process->pid = getmypid();
        $this->process->save();

        return Tools::jsonDecode($this->process->data, true);
    }
    
    /**
     * UnLock process
     *
     * @param array $data
     * @return bool
     * @throws PrestaShopException
     */
    public function unlock($data = array())
    {
        if (empty($this->process)) {
            return false;
        }

        if (false === empty($data)) {
            $this->process->data = Tools::jsonEncode($data);
        }
        $this->process->last_update = date('Y-m-d H:i:s');
        $endTime = $this->microtimeFloat();
        $duration = number_format(($endTime - $this->startTime), 3);
        $this->process->duration = $duration;
        $this->process->pid = null;

        return $this->process->save();
    }
    
    /**
     * Get microtime in float value
     *
     * @return float
     */
    public function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * @return null|string
     */
    public function getProcessObjectModelName()
    {
        if (empty($this->process)) {
            return null;
        }
        return get_class($this->process);
    }

    /**
     * @return int|null
     */
    public function getProcessObjectModelId()
    {
        if (empty($this->process)) {
            return null;
        }
        return (int)$this->process->id;
    }

    /**
     * @return null|string
     */
    public function getProcessName()
    {
        if (empty($this->process)) {
            return null;
        }
        return $this->process->name;
    }
}
