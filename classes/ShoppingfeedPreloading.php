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

use ShoppingfeedAddon\Services\ProductSerializer;

class ShoppingfeedPreloading extends ObjectModel
{
    const ACTION_SYNC_STOCK = "SYNC_STOCK";
    const ACTION_SYNC_PRICE = "SYNC_PRICE";
    const ACTION_SYNC_ALL = "SYNC_ALL";
    const ACTION_SYNC_PRELODING = "SYNC_PRELODING";

    public $id_shoppingfeed_preloading;

    public $product_id;

    public $token_id;

    public $content;

    public $date_add;

    public $date_upd;

    public $actions;

    public static $definition = array(
        'table' => 'shoppingfeed_preloading',
        'primary' => 'id_shoppingfeed_preloading',
        'fields' => array(
            'product_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'unique' => true,
            ),
            'token_id' => array(
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
                'field' => 'product_id',
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
                'field' => 'token_id',
            ),
        ),
    );


    /**
     * save content product in preloading table
     *
     * @param $product_id
     * @param $token_id
     * @param $action
     * @return bool
     * @throws Exception
     */
    public function saveProduct($product_id, $token_id, $id_lang, $id_shop)
    {
        $productSerialize = new ProductSerializer((int)$product_id, $id_lang, $id_shop);
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where('token_id = ' . (int)$token_id)
            ->where('product_id = ' . (int)$product_id);
        $shoppingfeedPreloading = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($shoppingfeedPreloading === false) {
            $this->id = null;
            $this->token_id = $token_id;
            $this->product_id = $product_id;
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
    public function findALlByToken($token, $limit = 100, $from = 0)
    {
        $result = [];

        $sql = new DbQuery();
        $sql->select('sfp.content')
            ->from(self::$definition['table'], 'sfp')
            ->innerJoin(ShoppingfeedToken::$definition['table'], 'sft', 'sft.id_shoppingfeed_token = sfp.token_id')
            ->where(sprintf('sft.content = "%s"', $token))
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
            ->from(self::$definition['table']);
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        return $result;
    }

    /**
     * @param $product_id
     * @param $token_id
     * @param $action
     * @return bool
     */
    public function addAction($product_id, $token_id, $action) {
        $query = (new DbQuery())->select('*')
            ->from(self::$definition['table'])
            ->where('token_id = ' . (int)$token_id)
            ->where('product_id = ' . (int)$product_id);
        $shoppingfeedPreloading = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($shoppingfeedPreloading === false) {
            $this->id = null;
            $this->token_id = $token_id;
            $this->product_id = $product_id;
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

    public function deleteProduct($product_id, $token_id)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->delete(
            self::$definition['table'],
            sprintf('product_id  = %d AND token_id = %d', $product_id, $token_id)
        );
    }
}
