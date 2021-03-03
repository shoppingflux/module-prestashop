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

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Install\Installer;

require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedPreloading.php';
require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedToken.php';

function upgrade_module_1_5_3($module)
{
    $sft = new ShoppingfeedToken();
    $tokens = $sft->findAllActive();
    $sfp = new ShoppingfeedPreloading();

    foreach ($tokens as $token) {
        $ids = [];
        $products = [];

        // Find the products with a price spec
        $query = (new DbQuery())
            ->from(ShoppingfeedPreloading::$definition['table'], 'sfp')
            ->leftJoin('specific_price', 'sp', 'sfp.id_product=sp.id_product')
            ->where('sp.id_specific_price IS NOT NULL')
            ->where('sfp.id_token=' . (int)$token['id_shoppingfeed_token'])
            ->select('DISTINCT(sfp.id_product)');

        try {
            $products = Db::getInstance()->executeS($query);
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'Error while find the products with a specific price. Message: %s. File: %s. Line: %d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                null,
                null
            );
        }


        if (empty($products)) {
            continue;
        }

        $ids = array_map(
            function($product) {
                return $product['id_product'];
            },
            $products
        );

        try {
            Db::getInstance()->delete(
                ShoppingfeedPreloading::$definition['table'],
                sprintf('id_product IN (%s)', implode(',', $ids))
            );
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'Error while delete the preloading products. Message: %s. File: %s. Line: %d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                null,
                null
            );
        }

        ProcessLoggerHandler::logInfo(
            sprintf('Preloading products deleted: %s', implode(',', $ids)),
            null,
            null
        );
    }

    return true;
}
