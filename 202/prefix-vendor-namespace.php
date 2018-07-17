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
 */

if (!php_sapi_name() === 'cli') {
    return;
}
chdir(__DIR__);

$vendor_dir = '../vendor/';
$dest_dir = '../vendor/testRename/';

$lib_to_prefix = "guzzlehttp";
$libs_to_update = array("shoppingfeed");
$prefix = "SfGuzzle\\";

function createFolder($folder)
{
    $tree = explode('/', $folder);
    $path = '';
    foreach ($tree as $item) {
        $path .= $item;
        if (!file_exists($path)) {
            mkdir($path);
        }
        $path .= '/';
    }
}

$namespaces = array();

$allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor_dir . $lib_to_prefix));
$phpFiles = new RegexIterator($allFiles, '/\.php$/');

$namespaceRegex = "/(namespace\s+)(.+?);/";
$namespaceReplace = '$1' . $prefix . '\\$2;';
foreach ($phpFiles as $phpFile) {
    $content = file_get_contents($phpFile->getRealPath());

    $matches = array();
    preg_match_all($namespaceRegex, $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $namespaces[] = $match[2];
    }
    $namespaces = array_unique($namespaces);

    $newContent = preg_replace($namespaceRegex, $namespaceReplace, $content);

    $destFilePath = $phpFile->getRealPath();
    $destFilePath = str_replace(realpath($vendor_dir) . '/', $dest_dir, $destFilePath);
    createFolder(dirname($destFilePath));

    file_put_contents($destFilePath, $newContent);
}

$namespaceRegex = "/(\s)(" . implode("|", array_map(function ($e) {
        return "(?:" . str_replace('\\', '\\\\', $e) . ")";
    }, $namespaces)) . ")/";
$namespaceReplace = '$1' . $prefix . '\\$2';
foreach ($libs_to_update as $lib_to_update) {
    $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor_dir . $lib_to_update));
    $phpFiles = new RegexIterator($allFiles, '/\.php$/');

    foreach ($phpFiles as $phpFile) {
        $content = file_get_contents($phpFile->getRealPath());
        $newContent = preg_replace($namespaceRegex, $namespaceReplace, $content);

        $destFilePath = $phpFile->getRealPath();
        $destFilePath = str_replace(realpath($vendor_dir) . '/', $dest_dir, $destFilePath);
        createFolder(dirname($destFilePath));

        file_put_contents($destFilePath, $newContent);
    }
}