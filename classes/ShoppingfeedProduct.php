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
class ShoppingfeedProduct extends ObjectModel
{
    const ACTION_SYNC_STOCK = "SYNC_STOCK";
    const ACTION_SYNC_PRICE = "SYNC_PRICE";

    /** @var string The action to execute for this product */
    public $action;

    /** @var int The product's id */
    public $id_product;

    /** @var int The combination's id */
    public $id_product_attribute;

    public $id_token;

    /** @var string The date and time after which the product will be updated.
     * If null, the product will never be updated.
     */
    public $update_at;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_product',
        'primary' => 'id_shoppingfeed_product',
        'fields' => array(
            'action' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'unique' => true,
                'values' => array(self::ACTION_SYNC_STOCK, self::ACTION_SYNC_PRICE),
            ),
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
            'update_at'=> array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'allow_null' => true,
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
        'associations' => array(
            'products' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Product',
                'association' => 'product_shoppingfeed',
                'field' => 'id_product',
                'multishop' => true,
            ),
        ),
    );


    /**
     * Returns the product's Shopping Feed reference
     * @return string
     */
    public function getShoppingfeedReference()
    {
        $reference = $this->id_product . ($this->id_product_attribute ? "_" . $this->id_product_attribute : "");
        $reference_format = Configuration::get(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT);
        if (empty($reference_format) === true) {
            return $reference;
        }
        $sql = new DbQuery();
        if (empty($this->id_product_attribute) === true) {
            $sql->from('product', 'p');
            $sql->join(Shop::addSqlAssociation('product', 'p'));
            if ($reference_format === 'supplier_reference') {
                $sql->select('sp.`product_supplier_reference`');
                $sql->leftJoin('product_supplier', 'sp', 'sp.`id_product` = p.`id_product`');
            } else {
                $sql->select('p.`'.pSQL($reference_format).'`');
            }
            $where = 'p.`id_product` = ' . (int) $this->id_product;
        } else {
            $sql->from('product_attribute', 'pa');
            if ($reference_format === 'supplier_reference') {
                $sql->select('sp.`product_supplier_reference`');
                $sql->leftJoin('product_supplier', 'sp', 'sp.`id_product_attribute` = pa.`id_product_attribute`');
            } else {
                $sql->select('pa.`'.pSQL($reference_format).'`');
            }
            $where = 'pa.`id_product_attribute` = ' . (int) $this->id_product_attribute .
                ' AND pa.`id_product` = ' . (int) $this->id_product;
        }
        $sql->where($where);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }


    /**
     * Returns the product id and combination ID from Shopping Feed reference
     * @param string $sfReference
     * @return string
     */
    public function getReverseShoppingfeedReference($sfReference)
    {
        $reference_format = Configuration::get(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT);

        if (empty($reference_format) === true) {
            return $sfReference;
        }
        if (Combination::isFeatureActive() == true) {
            $sql = new DbQuery();
            $sql->select('p.`id_product`, pa.`id_product_attribute`');
            $sql->from('product', 'p');
            $sql->join(Shop::addSqlAssociation('product', 'p'));
            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product` = p.`id_product`');

            if ($reference_format === 'supplier_reference') {
                $where = 'EXISTS(
                        SELECT * FROM `' . _DB_PREFIX_ . 'product_supplier` sp
                        WHERE sp.`id_product_attribute` = pa.`id_product_attribute` AND `product_supplier_reference` = "' . pSQL($sfReference) . '"
                    )';
            } else {
                $where = 'pa.`'.pSQL($reference_format).'` = "' . pSQL($sfReference) . '"';
            }
            $sql->where($where);
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

            if (empty($result) === false) {
                return $result['id_product'] . '_' . $result['id_product_attribute'];
            }
        }

        $sql = new DbQuery();
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->select('p.`id_product`');
        if ($reference_format === 'supplier_reference') {
            $where = 'EXISTS(
                    SELECT * FROM `' . _DB_PREFIX_ . 'product_supplier` sp
                    WHERE sp.`id_product` = p.`id_product` AND `product_supplier_reference` = "' . pSQL($sfReference) . '"
                )';
        } else {
            $where = 'p.`'.pSQL($reference_format).'` = "' . pSQL($sfReference) . '"';
        }
        $sql->where($where);
        $value = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        if (empty($value) === false) {
            return $value;
        }

        \ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler::logInfo(
            sprintf(
                'Product with %s: %s is not retrieved in your catalog.',
                $reference_format,
                $sfReference
            ),
            'Product'
        );

        return null;
    }

    /**
     * Attempts to retrieve an object using its unique key; returns false if none was found.
     * @param $action
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_token
     * @return bool|ShoppingfeedProduct
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFromUniqueKey($action, $id_product, $id_product_attribute, $id_token)
    {
        $sql = new DbQuery();
        $sql->select('id_shoppingfeed_product')
            ->from(self::$definition['table'])
            ->where('action = \'' . pSQL($action). '\'')
            ->where('id_product = ' . (int)$id_product)
            ->where('id_product_attribute = ' . (int)$id_product_attribute)
            ->where('id_token = ' . (int)$id_token);
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new ShoppingfeedProduct($id);
        }
        return false;
    }
}
