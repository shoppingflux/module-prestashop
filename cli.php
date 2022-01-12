#!/bin/php
<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

if (php_sapi_name() !== 'cli') {
    exit;
}

$_SERVER['REQUEST_METHOD'] = 'POST';

$pathDirs = explode('/', $_SERVER['argv'][0]);
$path = '';
foreach ($pathDirs as $key => $pathDir) {
    if (count($pathDirs) - 3 > $key) {
        $path .= $pathDir . '/';
    }
}


$params = array();
foreach($_SERVER['argv'] as $argv) {
    if (strpos($argv, '=') == true) {
        $args = explode('=',$argv);
        $params[$args[0]]=$args[1];
    }
}

if (empty($params['controller']) || empty($params['secure_key']) || empty($path) === true) {
    echo 'Please define cli in absolute path, controller and secure_key as expected.'."\r\n";
    echo 'Usage: php '. dirname(__FILE__) . '/cli.php controller=syncAll secure_key=db7a3a582a43e797a55842eced563d4a'."\r\n";
    exit;
}

$_GET['fc']         = 'module';
$_GET['controller'] = $params['controller'];
$_GET['module']     = 'shoppingfeed';
$_GET['secure_key'] = $params['secure_key'];

require_once $path.'/index.php';
