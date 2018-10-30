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

TotLoader::import('shoppingfeed\classlib\actions\defaultActions');

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');

/**
 * The Actions class is in charge of synchronizing product stocks using the SF API
 * This class can be overide in the Prestashop overide path.
 * - Respect the override path override/modules/shoppingfeed/classes/actions/ShoppingfeedProductStockSyncActions.php
 * - Name your override class ShoppingfeedProductStockSyncActionsOverride extended with ShoppingfeedProductStockSyncActions
 * @see ShoppingfeedDefaultActions
 */
class ShoppingfeedProductStockSyncActions extends ShoppingfeedDefaultActions
{
    /**
     * Saves a ShoppingfeedProduct to be synchronized. Runs the synchronization if real-time is enabled.
     * @return bool
     */
    public function saveProduct()
    {
        // Save the product for each shop
        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            $this->conveyor['id_shop'] = $shop['id_shop'];

            if (ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']) == false) {
                continue;
            }

            $sfProduct = new ShoppingfeedProduct();
            $sfProduct->action = ShoppingfeedProduct::ACTION_SYNC_STOCK;
            $sfProduct->id_product = $this->conveyor['id_product'];
            $sfProduct->id_product_attribute = $this->conveyor['id_product_attribute'];
            $sfProduct->id_shop = $this->conveyor['id_shop'];
            $sfProduct->update_at = date('Y-m-d H:i:s');
            try {
                $sfProduct->save();
            } catch (Exception $e) {
                // We can't do an "insert ignore", so use a try catch for when in debug mode...
            }
            if (true == Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, null, null, $this->conveyor['id_shop'])) {
                $this->forward('getBatch');
            }
        }

        return true;
    }

    /**
     * Gets a batch of ShoppindfeedProduct requiring their stock to be synchronized, and saves it
     * in the conveyor at ['batch'].
     * Forwards to prepareBatch.
     * @see prepareBatch
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBatch()
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_product')
            ->where("action = '" . pSQL(ShoppingfeedProduct::ACTION_SYNC_STOCK) . "'")
            ->where('update_at IS NOT NULL')
            ->where('id_shop ='. (int) $this->conveyor['id_shop'])
            ->where("update_at <= '" . date('Y-m-d H:i:s') . "'")
            ->limit(Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, null, null, $this->conveyor['id_shop']))
            ->orderBy('date_add ASC');
        $sfProductsRows = Db::getInstance()->executeS($query);

        if (empty($sfProductsRows)) {
            return false;
        }

        $this->conveyor['batch'] = array();
        foreach ($sfProductsRows as $row) {
            $sfProduct = new ShoppingfeedProduct();
            $sfProduct->hydrate($row);
            $this->conveyor['batch'][] = $sfProduct;
        }

        return $this->forward('prepareBatch');
    }

    /**
     * Prepares a batch of ShoppingfeedProduct to be used with API calls. Splits the batch in multiple arrays using the id_shop
     * and saves them in the conveyor at ['preparedBatch'][id_shop][reference].
     * Forwards to executeBatch.
     * @see getBatch
     * @see executeBatch
     * @return bool
     */
    public function prepareBatch()
    {
        $this->conveyor['preparedBatch'] = array();
        /** @var ShoppingfeedProduct $sfProduct */
        foreach ($this->conveyor['batch'] as $sfProduct) {
            $newData = array(
                'reference' => $sfProduct->getShoppingfeedReference()
            );

            $newData['quantity'] = StockAvailable::getQuantityAvailableByProduct(
                $sfProduct->id_product,
                $sfProduct->id_product_attribute ? $sfProduct->id_product_attribute : null,
                $sfProduct->id_shop
            );

            if (!isset($this->conveyor['preparedBatch'])) {
                $this->conveyor['preparedBatch'] = array();
            }

            $this->conveyor['preparedBatch'][$newData['reference']] = $newData;
        }

        return $this->forward('executeBatch');
    }

    /**
     * Gets prepared ShoppingfeedProduct from the conveyor at ['preparedBatch']
     * and synchronizes their stock using the API.<br/>
     * <br/>
     * Notes :
     * <ul>
     * <li> If we send a product reference that isn't in Shopping Feed's catalog,
     *      the API doesn't send back a confirmation for this product.
     * </li>
     * <li> If no products could be updated, the SDK throws an exception
     *      on array_push as it's trying to add the (non-existent) responses to
     *      its own responses array. <br/>
     *      TODO : should be fixed soon, remove this part then
     * </li>
     * <li> If the quantity for a product is not a number, Shopping Feed will
     *      set it to 0. A response is still sent, without any error.
     * </li>
     * </ul>
     * @see prepareBatch
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function executeBatch()
    {
        $res = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop'])->updateMainStoreInventory($this->conveyor['preparedBatch']);

        /**
         * If we send a product reference that isn't in SF's catalog, the API doesn't send a confirmation for this product.
         * This means we must make a diff between what we sent and what we received to know which product wasn't
         * updated.
         */
        $preparedBatchShop = $this->conveyor['preparedBatch'];
        /** @var ShoppingFeed\Sdk\Api\Catalog\InventoryResource $inventoryResource */
        foreach ($res as $inventoryResource) {
            $reference = $inventoryResource->getReference();
            $explodedReference = explode('_', $reference);
            $id_product = $explodedReference[0];
            $id_product_attribute = isset($explodedReference[1]) ? $explodedReference[1] : 0;

            ShoppingfeedProcessLoggerHandler::logInfo(
                sprintf(
                    $this->l('[Stock shop:%s] Updated %s qty: %s', 'ShoppingfeedProductStockSyncActions'),
                    $this->conveyor['id_shop'],
                    $reference,
                    $preparedBatchShop[$reference]['quantity']
                ),
                'Product',
                $id_product
            );

            ShoppingfeedRegistry::increment('updatedProducts');

            unset($preparedBatchShop[$reference]);

            $sfProduct = ShoppingFeedProduct::getFromUniqueKey($id_product, $id_product_attribute, $this->conveyor['id_shop']);
            $sfProduct->delete();
        }

        if (!empty($preparedBatchShop)) {
            foreach ($preparedBatchShop as $data) {
                $explodedReference = explode('_', $data['reference']);
                $id_product = $explodedReference[0];
                $id_product_attribute = isset($explodedReference[1]) ? $explodedReference[1] : 0;

                ShoppingfeedProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->l('[Stock shop:%s] %s not in Shopping Feed catalog - qty: %d', 'ShoppingfeedProductStockSyncActions'),
                        $this->conveyor['id_shop'],
                        $data['reference'],
                        $data['quantity']
                    ),
                    'Product',
                    $id_product
                );
                ShoppingfeedRegistry::increment('not-in-catalog');

                $sfProduct = ShoppingFeedProduct::getFromUniqueKey($id_product, $id_product_attribute, $this->conveyor['id_shop']);
                $sfProduct->delete();
            }
        }

        return true;
    }
}
