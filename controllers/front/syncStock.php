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

if (!defined('_PS_VERSION_')) {
    exit;
}

TotLoader::import('shoppingfeed\classlib\extensions\ProcessMonitor\CronController');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');

class ShoppingfeedSyncStockModuleFrontController extends ShoppingfeedCronController
{
    public $taskDefinition = array(
        'name' => 'shoppingfeed:syncStock',
        'title' => array(
            'en' => 'Sync shoppingfeed stock',
            'fr' => 'Sync shoppingfeed stock'
        ),
    );

    protected function processCron($data)
    {
        $logger = TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\ProcessLoggerHandler');
        $logger::openLogger($this->processMonitor);
        $logger::logInfo("start syncStock");

        $sfProducts = ShoppingfeedProduct::getProductsToSync();

        if (empty($sfProducts)) {
            $logger::logInfo("syncStock : nothing to synchronize");
            $logger::closeLogger();
            return $data;
        }

        $productsData = array();
        $processedLinesIds = array();
        foreach ($sfProducts as $sfProduct) {
            $newData = array();
            if ($sfProduct['id_product_attribute']) {
                $combination = new Combination($sfProduct['id_product_attribute']);
                $newData['reference'] = $combination->reference;
            } else {
                $product = new Product($sfProduct['id_product']);
                $newData['reference'] = $product->reference;
            }

            if (empty($newData['reference'])) {
                continue;
            }

            $newData['quantity'] = StockAvailable::getQuantityAvailableByProduct(
                $sfProduct['id_product'],
                $sfProduct['id_product_attribute'] ? $sfProduct['id_product_attribute'] : null,
                $sfProduct['id_shop']
            );

            if (!is_array($productsData[$sfProduct['id_shop']])) {
                $productsData[$sfProduct['id_shop']] = array();
            }

            $productsData[$sfProduct['id_shop']] = $newData;
            $processedLinesIds[] = $sfProduct['id_shoppingfeed_product'];
        }

        // TODO : for now, only use the default shop...
        $id_shop_default = Configuration::get('PS_SHOP_DEFAULT');
        try {
            $res = TotShoppingFeed\ShoppingfeedApi::getInstance($id_shop_default)->updateMainStoreInventory($productsData[$id_shop_default]);
        } catch (Exception $e) {
            $logger::logError("Fail syncStock : " . $e->getMessage() . " " . $e->getFile() . ":" . $e->getLine());
            $logger::closeLogger();
            return $data;
        }

        // TODO : uncomment line
        //Db::getInstance()->delete('shoppingfeed_product', 'id_shoppingfeed_product IN (' . implode(", ", $processedLinesIds) . ")");
        // TODO : set number of updated products using the response
        $logger::logSuccess("syncStock : " . count($productsData) . " updated");
        $logger::closeLogger();

        /** The data to be saved in the CRON table */
        return $data;
    }
}