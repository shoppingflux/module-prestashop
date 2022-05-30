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
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedPreloading.php';

function upgrade_module_1_8_0($module)
{
    $isIdTokenExiste = 0 < (int) Db::getInstance()->getValue('SELECT count(*) 
	    FROM INFORMATION_SCHEMA.COLUMNS
		WHERE `TABLE_NAME` = "' . _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table'] . '"
		AND `TABLE_SCHEMA` = "' . _DB_NAME_ . '"
		AND `COLUMN_NAME` = "id_shoppingfeed_token"');

    if ($isIdTokenExiste === false) {
        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table'] . '
            ADD COLUMN `etag` VARCHAR(255) NOT NULL;';
        Db::getInstance()->execute($sql);
    }

    $tokens = (new ShoppingfeedToken())->findAllActive();
    foreach ($tokens as $token) {
        $fileXml = sprintf(_PS_ROOT_DIR_ . '/file-%d.xml', $token['id_shoppingfeed_token']);
        if (file_exists($fileXml)) {
            unlink($fileXml);
        }
    }

    $sql = 'UPDATE `' . _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table'] . '` SET etag = md5(CONCAT(CURRENT_DATE, content))';
    Db::getInstance()->execute($sql);

    return true;
}
