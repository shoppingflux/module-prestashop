<?php
/**
 *
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *
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
