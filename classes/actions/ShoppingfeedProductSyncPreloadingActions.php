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

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ShoppingfeedProductSyncPreloadingActions extends DefaultActions
{
    public function getBatch()
    {
        $token = new ShoppingfeedToken($this->conveyor['id_token']);

        if (Validate::isLoadedObject($token) === false) {
            ProcessLoggerHandler::logInfo(
                $this->l('unable ton find token.', 'ShoppingfeedProductSyncPreloadingActions'),
                'Product'
            );

            return false;
        }
        $currency = new Currency($token->id_currency);

        if (Validate::isLoadedObject($currency) === false) {
            ProcessLoggerHandler::logInfo(
                $this->l('unable ton find currency.', 'ShoppingfeedProductSyncPreloadingActions'),
                'Product'
            );

            return false;
        }

        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sfp = new ShoppingfeedPreloading();
        $sql = new DbQuery();
        $sql->select('ps.id_product')
            ->from(Product::$definition['table'] . '_shop', 'ps')
            ->innerJoin(Product::$definition['table'], 'p', 'p.id_product = ps.id_product')
            ->leftJoin(ShoppingfeedPreloading::$definition['table'], 'sfp', 'sfp.id_product = ps.id_product')
            ->where(sprintf('(sfp.actions IS NOT NULL AND sfp.id_token = %d) OR (sfp.id_token IS NULL)', $token->id_shoppingfeed_token))
            ->where('ps.id_shop = ' . $token->id_shop)
            ->where('ps.active = 1')
            ->limit(Configuration::get(ShoppingFeed::STOCK_SYNC_MAX_PRODUCTS));

        if ((bool)Configuration::get(ShoppingFeed::PRODUCT_FEED_SYNC_PACK) !== true) {
            $sql->where('p.cache_is_pack = 0');
        }
        $result = $db->executeS($sql);

        foreach ($result as $key => $row) {
            Context::getContext()->currency = $currency;

            try {
                $sfp->saveProduct($row['id_product'], $token->id_shoppingfeed_token, $token->id_lang, $token->id_shop);
            } catch (Exception $exception) {
                ProcessLoggerHandler::logError($exception->getMessage());
            }
        }

        return true;
    }

    public function saveProduct() {
        $logPrefix = static::getLogPrefix();

        if (empty($this->conveyor['product']) || $this->conveyor['product'] instanceof Product === false) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                $this->l('Product not valide for synchronization', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
            return false;
        }

        $product = $this->conveyor['product'];

        if (empty($this->conveyor['product_action'])) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix . ' ' .
                    $this->l('Product %s not registered for synchronization; no Action found', 'ShoppingfeedProductSyncActions'),
                    $product->id
                ),
                'Product'
            );
            return false;
        }
        $action = $this->conveyor['product_action'];
        $tokens = (new ShoppingfeedToken())->findALlActive();
        $sfp = new ShoppingfeedPreloading();

        if ((bool)$product->active !== true ||
            ((bool)Configuration::get(ShoppingFeed::PRODUCT_FEED_SYNC_PACK) !== true && (bool)$product->cache_is_pack === true)) {

            return $this->forward('deleteProduct');
        } else {
            foreach ($tokens as $token) {
                $this->conveyor['id_token'] = $token['id_shoppingfeed_token'];

                $sfp->addAction($product->id, $token['id_shoppingfeed_token'], $action);

                if (Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {

                    $this->forward('getBatch');
                }
            }
        }


        return true;
    }

    public function deleteProduct()
    {
        $logPrefix = static::getLogPrefix();

        if (empty($this->conveyor['product']) || $this->conveyor['product'] instanceof Product === false) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                $this->l('Product not valide for synchronization', 'ShoppingfeedProductSyncActions'),
                'Product'
            );
            return false;
        }

        $product = $this->conveyor['product'];
        $tokens = (new ShoppingfeedToken())->findALlActive(Shop::getContextListShopID());
        $sfp = new ShoppingfeedPreloading();

        foreach ($tokens as $token) {
            $sfp->deleteProduct($product->id, $token['id_shoppingfeed_token']);
        }

        return true;
    }


    public static function getLogPrefix($id_shop = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Preloading shop:%s]', 'ShoppingfeedProductSyncPreloadingActions'),
            $id_shop
        );
    }
}
