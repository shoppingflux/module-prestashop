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


/**
 * This class represents a Product to be synchronized with the Shopping Feed API
 */
class ShoppingfeedStockAndPrices extends ObjectModel
{
    public $id_product;

    public $id_product_attribute;

    public $id_token;

    public $stock;

    public $price;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_stock_and_prices',
        'primary' => 'id_shoppingfeed_stock_and_prices',
        'fields' => array(
            'id_product' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true,
            ),
            'id_product_attribute' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true,
            ),
            'id_token' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true,
            ),
            'stock' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isInt',
                'required' => true,
            ),
            'price' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true,
                'size' => 20,
                'scale' => 6,
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
        ),
    );

    public function findProduct($id_product, $id_product_attribute, $id_token) {
        $query = new DbQuery();
        $query->select('*')
            ->from(self::$definition['table'])
            ->where("id_product = " . (int)$id_product)
            ->where("id_product_attribute = " . (int)$id_product_attribute)
            ->where("id_token = " . (int)$id_token);
        $result = DB::getInstance()->getRow($query);
        if ($result === false) {
            return null;
        }
        $this->hydrate($result);

        return $this;
    }
}
