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
if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

/**
 * The Actions class is in charge of synchronizing product prices using the SF API
 * This class can be overriden in the Prestashop override path.
 * - Respect the override path override/modules/shoppingfeed/classes/actions/ShoppingfeedProductSyncPriceActions.php
 * - Name your override class ShoppingfeedProductSyncPriceActionsOverride
 *   extended with ShoppingfeedProductSyncPriceActions
 *
 * @see ShoppingfeedDefaultActions
 */
class ShoppingfeedProductSyncPriceActions extends ShoppingfeedProductSyncActions
{
    /** {@inheritdoc} */
    protected $no_forward_after_save = true;

    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their price using the API.
     * {@inheritdoc}
     */
    public function prepareBatch()
    {
        $this->conveyor['preparedBatch'] = [];
        /** @var Shoppingfeed $sfModule */
        $sfModule = Module::getInstanceByName('shoppingfeed');
        $token = new ShoppingfeedToken($this->conveyor['id_token']);

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
            $price = $sfModule->mapProductPrice(
                $sfProduct,
                $token->id_shop,
                [
                    'price_with_reduction' => true,
                ]
            );
            if (!$price) {
                $sfProduct->delete();
                continue;
            }

            $newData = [
                'reference' => $sfReference,
                'price' => $price,
                'sfProduct' => $sfProduct,
            ];

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
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_token']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedProductSyncPriceActions'),
                'Product'
            );
            Registry::increment('errors');

            return false;
        }
        $limit = (int) Configuration::getGlobalValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);
        $preparedBatch = $this->conveyor['preparedBatch'];

        foreach (array_chunk($preparedBatch, $limit, true) as $products) {
            $res = $shoppingfeedApi->updateMainStorePrices($products, $this->conveyor['shoppingfeed_store_id']);
            /*
             * If we send a product reference that isn't in SF's catalog, the API
             * doesn't send a confirmation for this product.
             * This means we must make a diff between what we sent and what we
             * received to know which product wasn't updated.
             */
            /* @var ShoppingFeed\Sdk\Api\Catalog\InventoryResource $inventoryResource */
            foreach ($res as $pricingResource) {
                $reference = $pricingResource->getReference();
                $sfProduct = $products[$reference]['sfProduct'];

                ProcessLoggerHandler::logInfo(
                    sprintf(
                        static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                            $this->l('Updated %s price: %s', 'ShoppingfeedProductSyncPriceActions'),
                        $reference,
                        $products[$reference]['price']
                    ),
                    'Product',
                    $sfProduct->id_product
                );

                Registry::increment('updatedProducts');

                unset($products[$reference]);

                $sfProduct->delete();
            }

            if (empty($products)) {
                continue;
            }
            foreach ($products as $data) {
                $sfProduct = $data['sfProduct'];

                ProcessLoggerHandler::logInfo(
                    sprintf(
                        static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                            $this->l('%s not in Shopping Feed catalog - price: %s', 'ShoppingfeedProductSyncPriceActions'),
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

    public static function getLogPrefix($id_token)
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Price token:%s]', 'ShoppingfeedProductSyncPriceActions'),
            $id_token
        );
    }
}
