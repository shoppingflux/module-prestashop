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

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

class ShoppingfeedProductSyncPreloadingActions extends DefaultActions
{
    public function getBatch()
    {
        $token = new ShoppingfeedToken($this->conveyor['id_token']);
        $logPrefix = static::getLogPrefix($this->conveyor['id_token']);

        if (Validate::isLoadedObject($token) === false) {
            ProcessLoggerHandler::logInfo(
                $this->l('Unable to find token.', 'ShoppingfeedProductSyncPreloadingActions'),
                'Product'
            );
            Registry::increment('errors');

            return false;
        }
        $currency = new Currency($token->id_currency);

        if (Validate::isLoadedObject($currency) === false) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                $this->l('Unable to find currency.', 'ShoppingfeedProductSyncPreloadingActions'),
                'Product'
            );
            Registry::increment('errors');

            return false;
        }
        Context::getContext()->currency = $currency;

        $sfModule = Module::getInstanceByName('shoppingfeed');
        $limit = Configuration::getGlobalValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);
        $nb_total_product = $sfModule->countProductsOnFeed($token->id_shop);
        $nb_preloaded_product = (new ShoppingfeedPreloading())->getPreloadingCountForSync($token->id_shoppingfeed_token);
        if ($nb_total_product == $nb_preloaded_product) {
            return true;
        }
        $iterations = range(0, floor((($nb_total_product - $nb_preloaded_product) / $limit)));

        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sfp = new ShoppingfeedPreloading();
        foreach ($iterations as $iteration) {
            $sql = $sfModule->sqlProductsOnFeed($token->id_shop)
                ->select('ps.id_product')
                ->limit($limit)
                ->where(sprintf('ps.`id_product` NOT IN (SELECT spf.`id_product` FROM `' . _DB_PREFIX_ . 'shoppingfeed_preloading` spf WHERE `id_token` = %d AND (spf.`actions`  IS NULL OR  spf.`actions` = ""))', $token->id_shoppingfeed_token));
            $result = $db->executeS($sql, true, false);
            $ids = '';
            foreach ($result as $key => $row) {
                $ids .= $row['id_product'] . ', ';
                try {
                    $sfp->saveProduct($row['id_product'], $token->id_shoppingfeed_token, $token->id_lang, $token->id_shop, $token->id_currency);
                    Registry::increment('updatedProducts');
                } catch (Exception $exception) {
                    ProcessLoggerHandler::logError($exception->getMessage());
                }
            }
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                $this->l('products added: ', 'ShoppingfeedProductSyncPreloadingActions') . $ids,
                'Product'
            );
        }

        $this->removeProductFeed();

        return true;
    }

    public function saveProduct()
    {
        if (empty($this->conveyor['product_action'])) {
            ProcessLoggerHandler::logInfo(
                '[Preloading] ' .
                $this->l('Product not registered for synchronization; no action found', 'ShoppingfeedProductSyncPreloadingActions')
            );

            return false;
        }
        $action = $this->conveyor['product_action'];
        $tokens = (new ShoppingfeedToken())->findAllActive();
        $sfp = new ShoppingfeedPreloading();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sql = $sfModule->sqlProductsOnFeed()
                ->select('ps.id_product')
                ->where('ps.id_product IN (' . implode(', ', array_map('intval', $this->conveyor['products_id'])) . ')');

        $result = $db->executeS($sql, true, false);
        $productsAvailable = ($result === []) ? [] : array_column($result, 'id_product');

        foreach ($this->conveyor['products_id'] as $product_id) {
            if (in_array($product_id, $productsAvailable)) {
                foreach ($tokens as $token) {
                    $this->conveyor['id_token'] = $token['id_shoppingfeed_token'];
                    $sfp->addAction($product_id, $token['id_shoppingfeed_token'], $action);
                }
                continue;
            }
            foreach ($tokens as $token) {
                $sfp->deleteProduct($product_id, $token['id_shoppingfeed_token']);
            }
        }

        foreach ($tokens as $token) {
            $this->conveyor['id_token'] = $token['id_shoppingfeed_token'];
            if (Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
                $this->forward('getBatch');
            }
        }

        return true;
    }

    public function purge()
    {
        $preloading = new ShoppingfeedPreloading();
        $preloading->purge();
        $tokens = (new ShoppingfeedToken())->findAllActive();

        foreach ($tokens as $token) {
            $this->conveyor['id_token'] = $token['id_shoppingfeed_token'];
            if (Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
                $this->forward('getBatch');
            }
        }
    }

    public function removeProductFeed()
    {
        $tokens = (new ShoppingfeedToken())->findAllActive();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        foreach ($tokens as $token) {
            $sql = $sfModule->sqlProductsOnFeed($token['id_shop'])
                             ->select('ps.id_product');
            $result = $db->executeS($sql, true, false);
            if ($result === []) {
                continue;
            }
            $db->delete(
                ShoppingfeedPreloading::$definition['table'],
                sprintf(
                    'id_token = %s AND id_product NOT IN (%s)',
                    $token['id_shoppingfeed_token'],
                    implode(',', array_column($result, 'id_product'))
                )
            );
        }
    }

    public function deleteProduct()
    {
        if (empty($this->conveyor['product']) || $this->conveyor['product'] instanceof Product === false) {
            ProcessLoggerHandler::logInfo(
                '[Preloading] ' . $this->l('Product not valid for synchronization', 'ShoppingfeedProductSyncPreloadingActions'),
                'Product'
            );

            return false;
        }

        $product = $this->conveyor['product'];
        $tokens = (new ShoppingfeedToken())->findAllActive(Shop::getContextListShopID());
        $sfp = new ShoppingfeedPreloading();

        foreach ($tokens as $token) {
            $sfp->deleteProduct($product->id, $token['id_shoppingfeed_token']);
        }

        if (Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)) {
            return $this->forward('getBatch');
        }

        return true;
    }

    public static function getLogPrefix($id_token)
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Preloading token:%s]', 'ShoppingfeedProductSyncPreloadingActions'),
            $id_token
        );
    }
}
