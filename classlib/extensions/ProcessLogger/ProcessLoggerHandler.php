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

class ShoppingfeedProcessLoggerHandler
{
    /**
     * @var ShoppingfeedProcessMonitorHandler Instance of ProcessMonitorHandler
     */
    private static $process;

    /**
     * @var array logs
     */
    private static $logs = array();

    /**
     * Set process name and remove oldest logs
     *
     * @param ShoppingfeedProcessMonitorHandler|null $process
     */
    public static function openLogger($process = null)
    {
        self::$process = $process;
        self::autoErasingLogs();
    }

    /**
     * @param string|null $msg
     */
    public static function closeLogger($msg = null)
    {
        if (self::$process != null && false === empty($msg)) {
            self::logInfo($msg, self::$process->getProcessObjectModelName(), self::$process->getProcessObjectModelId()); // summary
        }
        self::saveLogsInDb();
    }

    /**
     * @param string $msg
     * @param string|null $objectModel
     * @param int|null $objectId
     * @param string|null $name
     * @param string $level
     */
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

    /**
     * @param string $msg
     * @param string|null $objectModel
     * @param int|null $objectId
     * @param string $name
     */
    public static function logSuccess($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'success');
    }

    /**
     * @param string $msg
     * @param string|null $objectModel
     * @param int|null $objectId
     * @param string $name
     */
    public static function logError($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'error');
    }

    /**
     * @param string $msg
     * @param string|null $objectModel
     * @param int|null $objectId
     * @param string $name
     */
    public static function logInfo($msg, $objectModel = null, $objectId = null, $name = 'default')
    {
        if (self::$process != null) {
            $name = self::$process->getProcessName();
        }
        self::addLog($msg, $objectModel, $objectId, $name, 'info');
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    public static function autoErasingLogs()
    {
        if (self::isAutoErasingEnabled()) {
            return Db::getInstance()->delete(
                'shoppingfeed_processlogger',
                sprintf(
                    'date_add <= NOW() - INTERVAL %d DAY',
                    self::getAutoErasingDelayInDays()
                )
            );
        }

        return true;
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
}
