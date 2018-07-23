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
 * @version   release/1.0.1
 */

class ShoppingfeedProcessMonitorHandler
{
    /**
     * @desc ProcessMonitorObjectModel
     */
    protected $process;

    /**
     * @desc float microtime
     */
    public $startTime;

    /**
     * @desc Lock process
     * @param string $name
     *
     * @return boolean
     */
    public function lock($name)
    {
        $this->startTime = $this->microtimeFloat();
        $processMonitorObjectModel = TotLoader::getInstance('shoppingfeed\classlib\extensions\ProcessMonitor\ProcessMonitorObjectModel');
        $this->process = $processMonitorObjectModel->findOneByName($name);
        if (empty($this->process->id)) {
            $this->process = new ShoppingfeedProcessMonitorObjectModel();
            $this->process->name = $name;
            $this->process->data = json_encode(array());
        }
        if (false === empty($this->process->pid)) {
            $oldpid = $this->process->pid;
            exec("ps -ef| awk '\$3 == \"$oldpid\" { print \$2 }'", $output, $ret);
            if (false === empty($output)) {

                return false;
            }
        }

        $this->process->pid = getmypid();
        $this->process->save();

        return json_decode($this->process->data, true);
    }

    /**
     * @desc UnLock process
     * @param string $name
     *
     * @return boolean
     */
    public function unlock($data = array())
    {
        if (empty($this->process)) {
            return false;
        }

        if (false === empty($data)) {
            $this->process->data = json_encode($data);
        }
        $this->process->last_update = date('Y-m-d H:i:s');
        $endTime = $this->microtimeFloat();
        $duration = number_format(($endTime - $this->startTime), 3);
        $this->process->duration = $duration;
        $this->process->pid = null;

        return $this->process->save();
    }

    /**
     * @desc get microtime in float value
     *
     * @return float
     */
    public function microtimeFloat() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function getProcessObjectModelName()
    {
        if (empty($this->process)) {
            return null;
        }
        return get_class($this->process);
    }

    public function getProcessObjectModelId()
    {
        if (empty($this->process)) {
            return null;
        }
        return (int)$this->process->id;
    }

    public function getProcessName()
    {
        if (empty($this->process)) {
            return null;
        }
        return $this->process->name;
    }
}
