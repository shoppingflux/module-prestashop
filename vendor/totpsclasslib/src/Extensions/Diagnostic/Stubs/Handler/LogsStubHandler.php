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

    private const LIMITE_DB_NUMBER_OF_DAYS = 30;

    private const LIMITE_DB_RAWS_ON_SCREEN = 100;

    private const LIMITE_FILE_RAWS_ON_SCREEN = 100;

    private const LIMITE_SIZE_FILE = 2*1000000;

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
            if (empty($logFiles) === false) {
                foreach ($logFiles as $file => $path) {
                    $logs['files'][$file]['path'] = $path;
                    $logs['files'][$file]['size'] = $this->fileGetSize($path, true, false);
                    $logs['files'][$file]['downloadYes'] = filesize($path) < self::LIMITE_SIZE_FILE;
                }
            }
        }

        if (is_dir(_PS_ROOT_DIR_ . '/log')) {
            $logFiles = glob(_PS_ROOT_DIR_ . '/log/*.log');
            if (empty($logFiles) === false) {
                foreach ($logFiles as $file => $path) {
                    $logs['files'][$file]['path'] = $path;
                    $logs['files'][$file]['size'] = $this->fileGetSize($path, true, false);
                    $logs['files'][$file]['downloadYes'] = filesize($path) < LIMITE_SIZE_FILE;
                }
            }
        }

        $logs['db']['prestashop']['whichDb'] = 'prestashop';
        $logs['db']['prestashop']['available'] = true;
        $logs['db']['prestashop']['xLastDays'] = self::LIMITE_DB_NUMBER_OF_DAYS;
        $logs['db']['prestashop']['countLines'] = $this->countTableLogs('log');
        if ($this->isTableExist(\Configuration::get(DiagnosticExtension::MODULE_NAME))) {
            $logs['db']['module']['whichDb'] = 'module';
            $logs['db']['module']['available'] = true;
            $logs['db']['module']['xLastDays'] = self::LIMITE_DB_NUMBER_OF_DAYS;
            $logs['db']['module']['countLines'] = $this->countTableLogs(\Configuration::get(DiagnosticExtension::MODULE_NAME) . '_processlogger');
        }

        return $logs;
    }

    public function loadLogs($data)
    {
        $listLogs = $this->getListLogs();
        $type = $data['type'];
        $value = $data['value'];
        foreach ($listLogs['files'] as $file) {
            $paths[] = $file['path'];
        }
        $dbName = array_keys($listLogs['db']);

        $logsContentTpl = _PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/diagnostic/logs-content.tpl';

        if (empty($listLogs[$type]) || (!in_array($value, $dbName) && !in_array($value, $paths))) {
            echo json_encode(['content' => Context::getContext()->smarty->fetch($logsContentTpl)]);
            die;
        }

        if ($type == 'files') {
            Context::getContext()->smarty->assign([
                'fileContent' => $this->FileCountLines($value),
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
        foreach ($listLogs['files'] as $file) {
            $paths[] = $file['path'];
        }
        $dbName = array_keys($listLogs['db']);
        $content = '';

        if (empty($listLogs[$type]) || (!in_array($value, $dbName) && !in_array($value, $paths))) {
            return false;
        }

        if ($type == 'files') {
            $path = basename($value);
            $content = file_get_contents($value);

        } else {
            $tableName = $value == 'prestashop' ? 'log' : \Configuration::get(DiagnosticExtension::MODULE_NAME) . '_processlogger';
            $tableLogs = $this->getTableLogs($tableName);
            $path = $tableName . '.csv';

            if (!empty($tableLogs)) {
                $content .= implode(";", array_keys($tableLogs[0])) . PHP_EOL;
                foreach ($tableLogs as $log) {
                    $content .= implode(";", $log) . PHP_EOL;
                }
            }
        }

        return [
            'path' => $path,
            'content' => $content,
        ];
    }

    public function export($download = true)
    {
        $listLogs = $this->getListLogs();
        $logs = [];

        foreach ($listLogs['files'] as $file) {
            if ($file['downloadYes']) {
                $data = $this->downloadLog([
                    'type' => 'files',
                    'value' => $file['path'],
                ]);
                $logs[$data['path']] = $data['content'];
            }
        }

        foreach ($listLogs['db'] as $db) {
            $data = $this->downloadLog([
                'type' => 'db',
                'value' => $db['whichDb'],
            ]);
            $logs[$data['path']] = $data['content'];
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
        header("Content-Disposition: attachment; path=export.shoppingfeed.logs.zip");
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
                AND table_name = "' . _DB_PREFIX_ . pSQL($logName) . '_processlogger"';

        return !empty(\Db::getInstance()->executeS($sql));
    }

    protected function getTableLogs($table)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from($table);
        $query->where('date_add BETWEEN DATE_SUB(NOW(), INTERVAL ' . self::LIMITE_DB_NUMBER_OF_DAYS . ' DAY) AND NOW()');
        $query->orderBy('date_add DESC');
        $query->limit(self::LIMITE_DB_RAWS_ON_SCREEN);

        return Db::getInstance()->executeS($query);
    }

    protected function countTableLogs($table)
    {
        $query = new DbQuery();
        $query->select('count(*)');
        $query->from($table);
        $query->where('date_add BETWEEN DATE_SUB(NOW(), INTERVAL ' . self::LIMITE_DB_NUMBER_OF_DAYS . ' DAY) AND NOW()');

        return Db::getInstance()->getValue($query);
    }

    function fileGetSize($path, $isShort, $checkIfFileExist)
    {
        if($checkIfFileExist && !file_exists($path)) {
            return 0;
        }

        $size = filesize($path);

        if(empty($size)) {
            return  '0 ' . ($isShort ? 'o':'octets');
        }

        $l = array();
        $l[] = array('name'=>'octets',      'abbr'=>'o',  'size'=>1);
        $l[] = array('name'=>'kilo octets', 'abbr'=>'ko', 'size'=>1024);
        $l[] = array('name'=>'mega octets', 'abbr'=>'Mo', 'size'=>1048576);
        $l[] = array('name'=>'giga octets', 'abbr'=>'Go', 'size'=>1073741824);
        $l[] = array('name'=>'tera octets', 'abbr'=>'To', 'size'=>1099511627776);
        $l[] = array('name'=>'peta octets', 'abbr'=>'Po', 'size'=>1125899906842620);

        foreach($l as $k=>$v){
            if($size<$v['size']){
                return round($size/$l[$k-1]['size'], 2) . ' ' . ($isShort ? $l[$k-1]['abbr']:$l[$k-1]['name']);
            }
        }

        $l = end($l);
        return round($size/$l['size'], 2) . ' ' . ($isShort ? $l['abbr']:$l['name']);
    }

    function FileCountLines($path)
    {
        $textLog = "";
        $lines = [];
        $f = fopen($path, "r");

        while(!feof($f)) {
            $line = fgets($f, 4096);
            array_push($lines, $line);
            if (count($lines) >= self::LIMITE_FILE_RAWS_ON_SCREEN +2) {
                array_shift($lines);
            }
        }

        fclose($f);
        foreach ($lines as $l) {
            $textLog = $textLog . $l;
        }

        return $textLog;
    }

}