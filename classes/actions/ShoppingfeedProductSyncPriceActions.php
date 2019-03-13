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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/ShoppingfeedProductSyncActions.php');

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

/**
 * The Actions class is in charge of synchronizing product prices using the SF API
 * This class can be overriden in the Prestashop override path.
 * - Respect the override path override/modules/shoppingfeed/classes/actions/ShoppingfeedProductSyncPriceActions.php
 * - Name your override class ShoppingfeedProductSyncPriceActionsOverride extended with ShoppingfeedProductSyncPriceActions
 * @see ShoppingfeedDefaultActions
 */
class ShoppingfeedProductSyncPriceActions extends ShoppingfeedProductSyncActions
{
    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their price using the API.
     * {@inheritdoc}
     */
    public function prepareBatch()
    {
        $this->conveyor['preparedBatch'] = array();
        /** @var Shoppingfeed $sfModule */
        $sfModule = Module::getInstanceByName('shoppingfeed');

        /** @var ShoppingfeedProduct $sfProduct */
        foreach ($this->conveyor['batch'] as $sfProduct) {
            // The developer can skip products to sync by overriding
            // ShoppingFeed::mapReference and have it return false (weak
            // comparison)
            $sfReference = $sfModule->mapReference($sfProduct);
            if (empty($sfReference)) {
                $sfProduct->delete();
                continue;
            }
            
            // The developer can skip products to sync by overriding
            // ShoppingFeed::mapProductPrice and have it return false (strict
            // comparison)
            $price = $sfModule->mapProductPrice($sfProduct);
            if (false === $price) {
                $sfProduct->delete();
                continue;
            }

            $newData = array(
                'reference' => $sfReference,
                'price' => $price,
                'sfProduct' => $sfProduct,
            );

            $this->conveyor['preparedBatch'][$newData['reference']] = $newData;
        }

        return $this->forward('executeBatch');
    }

    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their price using the API.
     * {@inheritdoc}
     */
    public function executeBatch()
    {
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                static::getLogPrefix($this->conveyor['id_shop']) . ' ' . $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedProductSyncPriceActions'),
                'Product'
            );
            Registry::increment('errors');
            return false;
        }
        $res = $shoppingfeedApi->updateMainStorePrices($this->conveyor['preparedBatch']);

        /**
         * If we send a product reference that isn't in SF's catalog, the API
         * doesn't send a confirmation for this product.
         * This means we must make a diff between what we sent and what we
         * received to know which product wasn't updated.
         */
        $preparedBatchShop = $this->conveyor['preparedBatch'];
        /** @var ShoppingFeed\Sdk\Api\Catalog\InventoryResource $inventoryResource */
        foreach ($res as $pricingResource) {
            $reference = $pricingResource->getReference();
            $sfProduct = $preparedBatchShop[$reference]['sfProduct'];
            
            ProcessLoggerHandler::logInfo(
                sprintf(
                    static::getLogPrefix($this->conveyor['id_shop']) . ' ' . $this->l('Updated %s price: %s', 'ShoppingfeedProductSyncPriceActions'),
                    $reference,
                    $preparedBatchShop[$reference]['price']
                ),
                'Product',
                $sfProduct->id_product
            );

            Registry::increment('updatedProducts');

            unset($preparedBatchShop[$reference]);

            $sfProduct->delete();
        }

        if (!empty($preparedBatchShop)) {
            foreach ($preparedBatchShop as $data) {
                $sfProduct = $data['sfProduct'];

                ProcessLoggerHandler::logInfo(
                    sprintf(
                        static::getLogPrefix($this->conveyor['id_shop']) . $this->l('%s not in Shopping Feed catalog - price: %d', 'ShoppingfeedProductSyncPriceActions'),
                        $data['reference'],
                        $data['price']
                    ),
                    'Product',
                    $sfProduct->id_product
                );
                Registry::increment('not-in-catalog');

                $sfProduct->delete();
            }
        }

        return true;
    }
    
    public static function getLogPrefix($id_shop = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Price shop:%s]', 'ShoppingfeedProductSyncPriceActions'),
            $id_shop
        );
    }
}
