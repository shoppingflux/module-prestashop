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
 * The Actions class is in charge of synchronizing product stocks using the SF API
 * This class can be overriden in the Prestashop override path.
 * - Respect the override path override/modules/shoppingfeed/classes/actions/ShoppingfeedProductSyncStockActions.php
 * - Name your override class ShoppingfeedProductSyncStockActionsOverride
 *   extended with ShoppingfeedProductSyncStockActions
 * @see ShoppingfeedDefaultActions
 */
class ShoppingfeedProductSyncStockActions extends ShoppingfeedProductSyncActions
{
    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their stock using the API.
     * {@inheritdoc}
     */
    public function prepareBatch()
    {
        $this->conveyor['preparedBatch'] = array();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        $token = new ShoppingfeedToken($this->conveyor['id_token']);

        /** @var ShoppingfeedProduct $sfProduct */
        foreach ($this->conveyor['batch'] as $sfProduct) {
            $sfReference = $sfModule->mapReference($sfProduct);

            // The developer can skip products to sync by overriding
            // ShoppingFeed::mapReference and have it return false
            if (empty($sfReference)) {
                $sfProduct->delete();
                continue;
            }

            $newData = array(
                'reference' => $sfReference,
                'quantity' => StockAvailable::getQuantityAvailableByProduct(
                    $sfProduct->id_product,
                    (empty($sfProduct->id_product_attribute) === false) ? $sfProduct->id_product_attribute : null,
                    $token->id_shop
                ),
                'sfProduct' => $sfProduct,
            );

            $this->conveyor['preparedBatch'][$newData['reference']] = $newData;
        }

        return $this->forward('executeBatch');
    }

    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their stock using the API.
     * {@inheritdoc}
     */
    public function executeBatch()
    {
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_token']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedProductSyncStockActions'),
                'Product'
            );
            Registry::increment('errors');

            return false;
        }
        $res = $shoppingfeedApi->updateMainStoreInventory($this->conveyor['preparedBatch']);

        /**
         * If we send a product reference that isn't in SF's catalog, the API
         * doesn't send a confirmation for this product.
         * This means we must make a diff between what we sent and what we
         * received to know which product wasn't updated.
         */
        $preparedBatchShop = $this->conveyor['preparedBatch'];
        /** @var ShoppingFeed\Sdk\Api\Catalog\InventoryResource $inventoryResource */
        foreach ($res as $inventoryResource) {
            $reference = $inventoryResource->getReference();
            $sfProduct = $preparedBatchShop[$reference]['sfProduct'];

            ProcessLoggerHandler::logInfo(
                sprintf(
                    static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                        $this->l('Updated %s qty: %s', 'ShoppingfeedProductSyncStockActions'),
                    $reference,
                    $preparedBatchShop[$reference]['quantity']
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
                        static::getLogPrefix($this->conveyor['id_token']) . ' ' .
                            $this->l('%s not in Shopping Feed catalog - qty: %s', 'ShoppingfeedProductSyncStockActions'),
                        $data['reference'],
                        $data['quantity']
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
            Translate::getModuleTranslation('shoppingfeed', '[Stock token:%s]', 'ShoppingfeedProductSyncStockActions'),
            $id_token
        );
    }
}
