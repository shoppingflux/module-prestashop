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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * Base abstract class for syncing product fields
 */
abstract class ShoppingfeedProductSyncActions extends DefaultActions
{
    /** @var bool $no_save_forward Should we stop after saving regardless of
     * realtime sync ?
     */
    protected $no_forward_after_save = false;

    /**
     * Saves a ShoppingfeedProduct to be synchronized. Runs the synchronization
     * if real-time is enabled.
     * @return bool
     */
    public function saveProduct()
    {
        if (empty($this->conveyor['id_product'])) {
            ProcessLoggerHandler::logInfo(
                    $this->l('Product not registered for synchronization; no ID product found', 'ShoppingfeedProductSyncActions'),
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
                    $this->l('Product %s not registered for synchronization; no Action found', 'ShoppingfeedProductSyncActions'),
                    $id_product . ($id_product_attribute ? '_' . $id_product_attribute : '')
                ),
                'Product'
            );
            return false;
        }
        $action = $this->conveyor['product_action'];

        // Save the product for each token
        $sft = new ShoppingfeedToken();
        $tokens = $sft->findALlActive();
        foreach ($tokens as $token) {
            $this->conveyor['id_token'] = $token['id_shoppingfeed_token'];
            $sfProduct = ShoppingfeedProduct::getFromUniqueKey(
                $action,
                $id_product,
                $id_product_attribute,
                $token['id_shoppingfeed_token']
            );
            if (false === $sfProduct || !Validate::isLoadedObject($sfProduct)) {
                $sfProduct = new ShoppingfeedProduct();
                $sfProduct->action = $action;
                $sfProduct->id_product = (int)$id_product;
                $sfProduct->id_product_attribute = (int)$id_product_attribute;
                $sfProduct->id_token = $token['id_shoppingfeed_token'];
            }

            $sfProduct->update_at = date('Y-m-d H:i:s');
            $sfProduct->save();

            if (!$this->no_forward_after_save && true == Configuration::getGlobalValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
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
        $logPrefix = static::getLogPrefix($this->conveyor['id_token']);

        if (empty($this->conveyor['product_action'])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Could not retrieve batch for synchronization; no product action found', 'ShoppingfeedProductSyncActions'),
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
            ->where('id_token ='. (int) $this->conveyor['id_token'])
            ->where("update_at <= '" . date('Y-m-d H:i:s') . "'")
            ->limit(Configuration::getGlobalValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS))
            ->orderBy('date_add ASC');
        $sfProductsRows = Db::getInstance()->executeS($query);

        if (empty($sfProductsRows)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' . $this->l('Nothing to synchronize.', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
            return true;
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

    public static function getLogPrefix($id_token)
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Product token:%s]', 'ShoppingfeedProductSyncActions'),
            $id_token
        );
    }
}
