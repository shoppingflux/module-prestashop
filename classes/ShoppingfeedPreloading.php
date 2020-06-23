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
    public $id_shoppingfeed_preloading;

    public $shop_id;

    public $product_id;

    public $content;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_preloading',
        'primary' => 'id_shoppingfeed_preloading',
        'fields' => array(
            'product_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'unique' => true,
            ),
            'shop_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'unique' => true,
            ),
            'content' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'size' => 8160,
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
        ),
    );


    /**
     * save content product in preloading table
     *
     * @param $product_id
     * @param $shop_id
     * @return bool
     * @throws Exception
     */
    public function saveProduct($product_id, $shop_id)
    {
        $productSerialize = new ProductSerializer((int)$product_id);
        $query = (new DbQuery())->select('*')
            ->from(self::$definition['table'])
            ->where('shop_id = ' . (int)$shop_id)
            ->where('product_id = ' . (int)$product_id);
        $shoppingfeedPreloading = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
        if ($shoppingfeedPreloading === false) {
            $this->shop_id = $shop_id;
            $this->product_id = $product_id;
        } else {
            $this->hydrate($shoppingfeedPreloading);
        }
        $this->content = Tools::jsonEncode($productSerialize->serialize(), JSON_UNESCAPED_UNICODE);

        return $this->save();
    }

    /**
     * get content product in preloading table
     * @param int $limit
     * @param int $from
     * @return array
     */
    public static function findAll($limit = 100, $from = 0)
    {
        $result = [];

        $sql = new DbQuery();
        $sql->select('content')
            ->from(self::$definition['table'])
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

}
