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
 *
 * @version   feature/34626_diagnostic
 */

namespace ShoppingfeedAddon\Services;

use ObjectModel;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\Classes\ProcessMonitorObjectModel;
use ShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorHandler;

class SfProcessMonitorHandler extends ProcessMonitorHandler
{
    protected function initProcess($name)
    {
        \Db::getInstance()->insert(
            ProcessMonitorObjectModel::$definition['table'],
            [
                'name' => pSQL($name),
                'data' => json_encode([]),
            ],
            false,
            true,
            \Db::INSERT_IGNORE
        );
    }

    protected function setLock($name, $pid)
    {
        \Db::getInstance()->update(
            ProcessMonitorObjectModel::$definition['table'],
            [
                'pid' => (int) $pid,
                'last_update' => date('Y-m-d H:i:s'),
            ],
            sprintf(
                'name LIKE \'%s\' AND (pid IS NULL OR pid = "")',
                pSQL($name)
            )
        );
    }

    public function lock($name)
    {
        $pid = getmypid();
        $this->startTime = $this->microtimeFloat();
        $this->initProcess($name);
        $this->setLock($name, $pid);

        $processMonitorObjectModel = new ProcessMonitorObjectModel();
        $this->process = $processMonitorObjectModel->findOneByName($name);

        if ($this->process->pid != $pid) {
            $last_update = new \DateTime($this->process->last_update);
            $data_now = new \DateTime('NOW');
            $diff = $data_now->diff($last_update);
            $hours = $diff->h;
            $hours = $hours + ($diff->days * 24);
            if ($hours < 1) {
                return false;
            }
        }

        $this->process->last_update = date('Y-m-d H:i:s');
        $this->process->pid = $pid;

        /**
         * We can't use the ObjectModel's "save", "add" or "update" methods.
         * PS will natively call ObjectModel hooks, using the class name of the
         * ObjectModel. On PS 1.6, the namespace is not escaped from the class name,
         * resulting in an invalid hook name, e.g. :
         * actionObjectShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorObjectModelUpdateBefore
         */
        $definition = \ObjectModel::getDefinition($this->process);
        \Db::getInstance()->update(
            $definition['table'],
            $this->process->getFields(),
            '`' . pSQL($definition['primary']) . '` = ' . (int) $this->process->id,
            0,
            false
        );

        return json_decode($this->process->data, true);
    }
}
