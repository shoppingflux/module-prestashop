<?php

use ShoppingfeedAddon\Services\ProductSerializer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShoppingfeedPreloading extends ObjectModel
{
    public $id_shoppingfeed_preloading;

    public $shop_id;

    public $product_id;

    public $product;

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
            'product' => array(
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
    );

    public function saveProduct($product_id, $shop_id)
    {
        $productSerialize = new ProductSerializer($product_id);
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
        $this->product = json_encode($productSerialize->serialize(), JSON_UNESCAPED_UNICODE);

        return $this->save();
    }

    public static function findAll()
    {
        $result = [];

        $sql = new DbQuery();
        $sql->select('product')
            ->from(self::$definition['table']);
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $result[] = json_decode($row['product'], true);
        }

        return $result;
    }
}