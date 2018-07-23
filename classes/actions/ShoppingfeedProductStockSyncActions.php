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

TotLoader::import('shoppingfeed\classlib\actions\defaultActions');

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');

/**
 * The Actions class responsible for synchronizing product stocks using the SF API
 * Class ShoppingfeedProductStockSyncActions
 */
class ShoppingfeedProductStockSyncActions extends ShoppingfeedDefaultActions
{
    public function getBatch()
    {

        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_product')
            ->where("action = '" . pSQL(ShoppingfeedProduct::ACTION_SYNC_STOCK) . "'")
            ->limit(Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS))
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

        return true;
    }

    public function prepareBatch()
    {
        $this->conveyor['preparedBatch'] = array();
        foreach ($this->conveyor['batch'] as $sfProduct) {
            $newData = array(
                'reference' => $sfProduct->id_product . ($sfProduct->id_product_attribute ? "_" . $sfProduct->id_product_attribute : "")
            );

            $newData['quantity'] = StockAvailable::getQuantityAvailableByProduct(
                $sfProduct->id_product,
                $sfProduct->id_product_attribute ? $sfProduct->id_product_attribute : null,
                $sfProduct->id_shop
            );

            if (!isset($this->conveyor['preparedBatch'][$sfProduct->id_shop])) {
                $this->conveyor['preparedBatch'][$sfProduct->id_shop] = array();
            }

            $this->conveyor['preparedBatch'][$sfProduct->id_shop][] = $newData;
        }

        return true;
    }

    public function executeBatch()
    {
        // TODO : for now, only use the default shop... But we'll have to support multishop at some point...
        $id_shop_default = Configuration::get('PS_SHOP_DEFAULT');

        $res = ShoppingfeedApi::getInstanceByToken($id_shop_default)->updateMainStoreInventory($this->conveyor['preparedBatch'][$id_shop_default]);

        /** @var ShoppingFeed\Sdk\Api\Catalog\InventoryResource $inventoryResource */
        foreach ($res as $inventoryResource) {
            $explodedReference = explode('_', $inventoryResource->getReference());
            $id_product = $explodedReference[0];
            $id_product_attribute = isset($explodedReference[1]) ? $explodedReference[1] : 0;

            ShoppingfeedProcessLoggerHandler::logSuccess(
                '[Stock] ' .
                "Updated $id_product _ $id_product_attribute",
                'Product',
                $id_product
            );
            ShoppingfeedRegistry::increment('updatedProducts');

            foreach ($this->conveyor['batch'] as $sfProduct) {
                if ($sfProduct->id_product == $id_product
                    && $sfProduct->id_product_attribute == $id_product_attribute
                    && $sfProduct->id_shop == $id_shop_default
                ) {
                    $sfProduct->delete();
                }
            }
        }

        return true;
    }
}
