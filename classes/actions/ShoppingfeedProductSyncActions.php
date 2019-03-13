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

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * Base abstract class for syncing product fields
 */
abstract class ShoppingfeedProductSyncActions extends DefaultActions
{
    /**
     * Saves a ShoppingfeedProduct to be synchronized. Runs the synchronization if real-time is enabled.
     * @return bool
     */
    public function saveProduct()
    {
        $logPrefix = static::getLogPrefix();
            
        if (empty($this->conveyor['id_product'])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' . $this->l('Product not registered for synchronization; no ID product found', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
            return false;
        }
        $id_product = $this->conveyor['id_product'];
        
        $id_product_attribute = null;
        if (!empty($this->conveyor['id_product_attribute'])) {
            $id_product_attribute = $this->conveyor['id_product_attribute'];
        }
        
        if (empty($this->conveyor['product_action'])) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix . ' ' . $this->l('Product %s not registered for synchronization; no Action found', 'ShoppingfeedProductSyncActions'),
                    $id_product . ($id_product_attribute ? '_' . $id_product_attribute : '')
                ),
                'Product'
            );
            return false;
        }
        $action = $this->conveyor['product_action'];
        
        // Save the product for each shop
        $shops = Shop::getShops();
        foreach ($shops as $shop) {
            $this->conveyor['id_shop'] = $shop['id_shop'];

            $token = Configuration::get(Shoppingfeed::AUTH_TOKEN, null, null, $shop['id_shop']);
            if (false == $token) {
                continue;
            }

            $sfProduct = ShoppingfeedProduct::getFromUniqueKey(
                $action,
                $id_product,
                $id_product_attribute,
                $this->conveyor['id_shop']
            );
            if (false === $sfProduct || !Validate::isLoadedObject($sfProduct)) {
                $sfProduct = new ShoppingfeedProduct();
                $sfProduct->action = $action;
                $sfProduct->id_product = (int)$id_product;
                $sfProduct->id_product_attribute = (int)$id_product_attribute;
                $sfProduct->id_shop = (int)$this->conveyor['id_shop'];
            }
            
            $sfProduct->update_at = date('Y-m-d H:i:s');
            $sfProduct->save();
            
            if (true == Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, null, null, $this->conveyor['id_shop'])) {
                $this->forward('getBatch');
            }
        }

        return true;
    }

    /**
     * Gets a batch of ShoppindfeedProduct requiring synchronization, and saves it
     * in the conveyor at ['batch'].
     * Forwards to prepareBatch.
     * @see prepareBatch
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBatch()
    {
        $logPrefix = static::getLogPrefix($this->conveyor['id_shop']);
            
        if (empty($this->conveyor['product_action'])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' . $this->l('Could not retrieve batch for synchronization; no product action found', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
            return false;
        }
        $action = $this->conveyor['product_action'];
        
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_product')
            ->where("action = '" . pSQL($action) . "'")
            ->where('update_at IS NOT NULL')
            ->where('id_shop ='. (int) $this->conveyor['id_shop'])
            ->where("update_at <= '" . date('Y-m-d H:i:s') . "'")
            ->limit(Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, null, null, $this->conveyor['id_shop']))
            ->orderBy('date_add ASC');
        $sfProductsRows = Db::getInstance()->executeS($query);

        if (empty($sfProductsRows)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' . $this->l('Nothing to synchronize.', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
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
     * Prepares a batch of ShoppingfeedProduct to be used with API calls.
     * @see getBatch
     * @see executeBatch
     * @return bool
     */
    abstract public function prepareBatch();

    /**
     * Gets prepared ShoppingfeedProduct from the conveyor and synchronizes
     * them using the API.
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
     */
    abstract public function executeBatch();
    
    public static function getLogPrefix($id_shop = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Product shop:%s]', 'ShoppingfeedProductSyncActions'),
            $id_shop
        );
    }
}
