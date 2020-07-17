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

use ShoppingfeedAddon\Services\ProductSerializer;

class ShoppingfeedPreloading extends ObjectModel
{
    const ACTION_SYNC_STOCK = "SYNC_STOCK";
    const ACTION_SYNC_PRICE = "SYNC_PRICE";
    const ACTION_SYNC_ALL = "SYNC_ALL";
    const ACTION_SYNC_PRELODING = "SYNC_PRELODING";

    public $id_shoppingfeed_preloading;

    public $id_product;

    public $id_token;

    public $content;

    public $date_add;

    public $date_upd;

    public $actions;

    public static $definition = array(
        'table' => 'shoppingfeed_preloading',
        'primary' => 'id_shoppingfeed_preloading',
        'fields' => array(
            'id_product' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'unique' => true,
            ),
            'id_token' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'unique' => true,
            ),
            'content' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'size' => 8160,
                'allow_null' => true,
            ),
            'actions' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'allow_null' => true,
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
        ),
        'associations' => array(
            'products' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Product',
                'association' => 'product',
                'field' => 'id_product',
            ),
            'shops' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Shop',
                'association' => 'shop',
                'field' => 'shop_id',
            ),
            'tokens' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'ShoppingfeedToken',
                'association' => 'shoppingfeed_token',
                'field' => 'id_token',
            ),
        ),
    );


    /**
     * save content product in preloading table
     *
     * @param $id_product
     * @param $id_token
     * @param $action
     * @return bool
     * @throws Exception
     */
    public function saveProduct($id_product, $id_token, $id_lang, $id_shop)
    {
        $productSerialize = new ProductSerializer((int)$id_product, $id_lang, $id_shop);
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where('id_token = ' . (int)$id_token)
            ->where('id_product = ' . (int)$id_product);
        $shoppingfeedPreloading = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($shoppingfeedPreloading === false) {
            $this->id = null;
            $this->id_token = $id_token;
            $this->id_product = $id_product;
            $this->content = Tools::jsonEncode($productSerialize->serialize(), JSON_UNESCAPED_UNICODE);
        } else {
            $this->hydrate($shoppingfeedPreloading);
            $actions = Tools::jsonDecode($this->actions, true);
            if (is_array($actions)) {
                foreach ($actions as $action) {
                    switch ($action) {
                        case self::ACTION_SYNC_ALL:
                        default:
                            $this->content = Tools::jsonEncode($productSerialize->serialize(), JSON_UNESCAPED_UNICODE);
                            break;
                        case self::ACTION_SYNC_PRICE:
                            $this->content = Tools::jsonEncode($productSerialize->serializePrice(Tools::jsonDecode($this->content, true)), JSON_UNESCAPED_UNICODE);
                            break;
                        case self::ACTION_SYNC_STOCK:
                            $this->content = Tools::jsonEncode($productSerialize->serializeStock(Tools::jsonDecode($this->content, true)), JSON_UNESCAPED_UNICODE);
                            break;
                    }
                }
            }
        }
        $this->actions = null;

        return $this->save();
    }

    /**
     * get content product in preloading table
     * @param int $limit
     * @param int $from
     * @return array
     */
    public function findAllByToken($token, $from = 0, $limit = 100)
    {
        $result = [];

        $sql = new DbQuery();
        $sql->select('sfp.content')
            ->from(self::$definition['table'], 'sfp')
            ->innerJoin(ShoppingfeedToken::$definition['table'], 'sft', 'sft.id_shoppingfeed_token = sfp.id_token')
            ->where(sprintf('sft.content = "%s"', pSQL($token)))
            ->where('sfp.actions is null')
            ->limit($limit, $from);

        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) as $row) {
            $result[] = Tools::jsonDecode($row['content'], true);
        }

        return $result;
    }

    /**
     * get content product in preloading table
     * @param int $limit
     * @param int $from
     * @return array
     */
    public function findAllByTokenId($id_token, $from = 0, $limit = 100)
    {
        $result = [];

        $sql = new DbQuery();
        $sql->select('content')
            ->from(self::$definition['table'])
            ->where(sprintf('id_token = %d', (int)$id_token))
            ->where('actions is null')
            ->limit($limit, $from);

        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) as $row) {
            $result[] = Tools::jsonDecode($row['content'], true);
        }

        return $result;
    }

    public function getPreloadingCount()
    {
        $sql = new DbQuery();
        $sql->select('COUNT('.self::$definition['primary'].')')
            ->from(self::$definition['table'])
            ->where('actions is null')
        ;
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        return $result;
    }

    /**
     * @param $id_product
     * @param $id_token
     * @param $action
     * @return bool
     */
    public function addAction($id_product, $id_token, $action) {
        $query = (new DbQuery())->select('*')
            ->from(self::$definition['table'])
            ->where('id_token = ' . (int)$id_token)
            ->where('id_product = ' . (int)$id_product);
        $shoppingfeedPreloading = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($shoppingfeedPreloading === false) {
            $this->id = null;
            $this->id_token = $id_token;
            $this->id_product = $id_product;
            $this->actions = Tools::jsonEncode([self::ACTION_SYNC_ALL]);
        } else {
            $this->hydrate($shoppingfeedPreloading);
            $actions = Tools::jsonDecode($this->actions, true);
            if ($actions === null || $action === self::ACTION_SYNC_ALL) {
                $this->actions = Tools::jsonEncode([$action]);
            } else if ($action !== self::ACTION_SYNC_ALL && in_array(self::ACTION_SYNC_ALL, $actions)) {

                return true;
            } else if (in_array($action, $actions) === false) {
                $actions[] = $action;
                $this->actions = Tools::jsonEncode($actions);
            } else {

                return true;
            }
        }

        return $this->save();
    }

    public function deleteProduct($id_product, $id_token)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->delete(
            self::$definition['table'],
            sprintf('id_product  = %d AND id_token = %d', $id_product, $id_token)
        );
    }

    public function purge()
    {
        $sql = 'TRUNCATE ' . _DB_PREFIX_ . self::$definition['table'];
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);
    }
}
