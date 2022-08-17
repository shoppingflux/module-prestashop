<?php
/*
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
 * @version   feature/34626_diagnostic
 */

namespace ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Handler;

use ShoppingfeedClasslib\Utils\Translate\TranslateTrait;
use ShoppingfeedClasslib\Extensions\Diagnostic\DiagnosticExtension;
use Context;
use Db;
use DbQuery;
use ZipArchive;

class LogsStubHandler extends AbstractStubHandler
{
    use TranslateTrait;

    public function handle()
    {
        return [
            'logs' => $this->getListLogs(),
        ];
    }

    protected function getListLogs()
    {
        $logs = [
            'files' => [],
            'db' => [],
        ];

        if (is_dir(_PS_ROOT_DIR_ . '/var/logs')) {
            $logFiles = glob(_PS_ROOT_DIR_ . '/var/logs/*.log');
            if (!empty($logFiles)) {
                $logs['files'] = array_merge($logs['files'], $logFiles);
            }
        }

        if (is_dir(_PS_ROOT_DIR_ . '/log')) {
            $logFiles = glob(_PS_ROOT_DIR_ . '/log/*.log');
            if (!empty($logFiles)) {
                $logs['files'] = array_merge($logs['files'], $logFiles);
            }
        }

        $logs['db']['prestashop'] = true;
        if ($this->isTableExist(\Configuration::get(DiagnosticExtension::MODULE_NAME))) {
            $logs['db']['module'] = true;
        }

        return $logs;
    }

    public function loadLogs($data)
    {
        $listLogs = $this->getListLogs();
        $type = $data['type'];
        $value = $data['value'];
        $logsContentTpl = _PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/diagnostic/logs-content.tpl';

        if (empty($listLogs[$type]) || !in_array($value, $listLogs[$type])) {
            echo json_encode(['content' => Context::getContext()->smarty->fetch($logsContentTpl)]);
            die;
        }

        if ($type == 'files') {
            Context::getContext()->smarty->assign([
                'fileContent' => file_get_contents($value),
            ]);
            echo json_encode(['content' => Context::getContext()->smarty->fetch($logsContentTpl)]);
            die;
        }

        if ($value == 'prestashop') {
            $psLogs = $this->getTableLogs('log');
            if (!empty($psLogs)) {
                Context::getContext()->smarty->assign([
                    'dbContent' => [
                        'headers' => array_keys($psLogs[0]),
                        'content' => $psLogs,
                    ],
                ]);
                echo json_encode(['content' => Context::getContext()->smarty->fetch($logsContentTpl)]);
                die;
            }
        } else {
            $tableLogs = $this->getTableLogs(\Configuration::get(DiagnosticExtension::MODULE_NAME) . '_processlogger');
            if (!empty($tableLogs)) {
                Context::getContext()->smarty->assign([
                    'dbContent' => [
                        'headers' => array_keys($tableLogs[0]),
                        'content' => $tableLogs,
                    ],
                ]);
                echo json_encode(['content' => Context::getContext()->smarty->fetch($logsContentTpl)]);
                die;
            }
        }
    }

    public function downloadLog($data)
    {
        $listLogs = $this->getListLogs();
        $type = $data['type'];
        $value = $data['value'];
        $content = '';

        if (empty($listLogs[$type]) || !in_array($value, $listLogs[$type])) {
            return false;
        }

        if ($type == 'files') {
            $fileName = basename($value);
            $content = file_get_contents($value);
        } else {
            $tableName = $value == 'prestashop' ? 'log' : \Configuration::get(DiagnosticExtension::MODULE_NAME) . '_processlogger';
            $tableLogs = $this->getTableLogs($tableName);
            $fileName = $tableName . '.csv';

            if (!empty($tableLogs)) {
                $content = '';
                $content .= implode(";", array_keys($tableLogs[0])) . PHP_EOL;
                foreach ($tableLogs as $log) {
                    $content .= implode(";", $log) . PHP_EOL;
                }
            }
        }

        return [
            'fileName' => $fileName,
            'content' => $content,
        ];
    }

    public function export($download = true)
    {
        $listLogs = $this->getListLogs();
        $logs = [];
        foreach ($listLogs['files'] as $file) {
            $data = $this->downloadLog([
                'type' => 'files',
                'value' => $file,
            ]);
            $logs[$data['fileName']] = $data['content'];
        }

        foreach ($listLogs['db'] as $dbName => $db) {
            $data = $this->downloadLog([
                'type' => 'db',
                'value' => $dbName,
            ]);
            $logs[$data['fileName']] = $data['content'];
        }

        if (!$download) {
            return $logs;
        }

        $zipPath = _PS_MODULE_DIR_ . 'shoppingfeed/export.logs.zip';
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        $zip = new ZipArchive();

        if (!$zip->open($zipPath, ZipArchive::CREATE)) {
            throw new \PrestaShopException('Failed to create zip file');
        }

        foreach ($logs as $name => $log) {
            $zip->addFromString($name, $log);
        }

        $zip->close();

        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=export.shoppingfeed.logs.zip");
        header("Content-length: " . filesize($zipPath));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($zipPath);
        unlink($zipPath);
    }

    protected function isTableExist($logName)
    {
        if (empty($logName)) {
            return false;
        }

        $sql = 'SELECT COUNT(*)
                FROM information_schema.tables 
                WHERE table_schema = "' . _DB_NAME_ . '"
                AND table_name = "' . _DB_PREFIX_ . $logName . '_processlogger"';

        return !empty(\Db::getInstance()->executeS($sql));
    }

    protected function getTableLogs($table)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from($table);
        $query->orderBy('date_add DESC');
        $query->limit(100);

        return Db::getInstance()->executeS($query);
    }
}
