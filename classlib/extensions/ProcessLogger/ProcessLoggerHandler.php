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

class ShoppingfeedProcessLoggerHandler
{
    /** @var ShoppingfeedProcessMonitorHandler Instance of ProcessMonitorHandler */
    private static $process;
    /** @var array logs */
    private static $logs = array();

    public static function openLogger($process = null)
    {
        self::$process = $process;
        self::autoErasingLogs();
    }

    public static function closeLogger($msg = null)
    {
        if (self::$process != null && false === empty($msg)) {
            self::logInfo($msg, self::$process->getProcessObjectModelName(), self::$process->getProcessObjectModelId()); // summary
        }
        self::saveLogsInDb();
    }

    public static function addLog($msg, $objectModel = null, $objectId = null, $name = null, $level = 'info')
    {
        self::$logs[] = array(
            'name' => pSQL($name),
            'msg' => pSQL($msg),
            'level' => pSQL($level),
            'object_name' => pSQL($objectModel),
            'object_id' => pSQL($objectId),
            'date_add' => date("Y-m-d H:i:s"),
        );

        if (100 === count(self::$logs)) {
            self::saveLogsInDb();
        }
    }

    public static function logSuccess($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'success');
    }

    public static function logError($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'error');
    }

    public static function logInfo($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'info');
    }

    public static function saveLogsInDb()
    {
        $result = true;
        if (false === empty(self::$logs)) {
            $result = Db::getInstance()->insert(
                'shoppingfeed_processlogger',
                self::$logs
            );

            if ($result) {
                self::$logs = array();
            }
        }

        return $result;
    }

    public static function autoErasingLogs()
    {
        if (self::canExecErasing()) {
            $result = Db::getInstance()->delete(
                'shoppingfeed_processlogger',
                sprintf(
                    'date_add <= NOW() - INTERVAL %d DAY',
                    self::getAutoErasingDelayInDays()
                )
            );
            if ($result) {
                self::setLastDateErasingExec();
            }
        }
    }

    /**
     * @return bool
     */
    public static function isAutoErasingEnabled()
    {
        return false === (bool)Configuration::get('SHOPPINGFEED_EXTLOGS_ERASING_DISABLED');
    }

    /**
     * @return int
     */
    public static function getAutoErasingDelayInDays()
    {
        $numberOfDays = Configuration::get('SHOPPINGFEED_EXTLOGS_ERASING_DAYSMAX');

        if (empty($numberOfDays) || false === is_numeric($numberOfDays)) {
            return 5;
        }

        return (int)$numberOfDays;
    }

    /**
     * @return bool
     */
    protected static function setLastDateErasingExec()
    {
        return Configuration::updateValue('SHOPPINGFEED_EXTLOGS_ERASING_LASTEXEC', date("Y-m-d H:i:s"));
    }

    /**
     * @return bool
     */
    protected static function isSetLastDateErasingExec()
    {
        return Validate::isDate(self::getLastDateErasingExec());
    }

    /**
     * @return string
     */
    protected static function getLastDateErasingExec()
    {
        return Configuration::get('SHOPPINGFEED_EXTLOGS_ERASING_LASTEXEC');
    }

    /**
     * @return bool
     */
    protected static function canExecErasing()
    {
        if (self::isAutoErasingEnabled()) {
            if (false === self::isSetLastDateErasingExec()) {
                return true;
            }
            $dateLastRefresh = new DateTime(self::getLastDateErasingExec());
            $dateNow         = new DateTime();
            $dateInterval    = $dateLastRefresh->diff($dateNow);
            if ($dateInterval->d > self::getAutoErasingDelayInDays()) {
                return true;
            }
        }
        return false;
    }
}
