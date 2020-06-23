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

/**
 * This class represents a Product to be synchronized with the Shopping Feed API
 */
class ShoppingfeedProduct extends ObjectModel
{
    const ACTION_SYNC_STOCK = "SYNC_STOCK";
    const ACTION_SYNC_PRICE = "SYNC_PRICE";
    const ACTION_SYNC_PRELODING = "ACTION_SYNC_PRELODING";

    /** @var string The action to execute for this product */
    public $action;

    /** @var int The product's id */
    public $id_product;

    /** @var int The combination's id */
    public $id_product_attribute;

    /** @var int The shop to get the product from */
    public $id_shop;

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
            'id_shop' => array(
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
        return $this->id_product . ($this->id_product_attribute ? "_" . $this->id_product_attribute : "");
    }

    /**
     * Attempts to retrieve an object using its unique key; returns false if none was found.
     * @param $action
     * @param $id_product
     * @param $id_product_attribute
     * @param $id_shop
     * @return bool|ShoppingfeedProduct
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFromUniqueKey($action, $id_product, $id_product_attribute, $id_shop)
    {
        $sql = new DbQuery();
        $sql->select('id_shoppingfeed_product')
            ->from(self::$definition['table'])
            ->where('action = \'' . pSQL($action). '\'')
            ->where('id_product = ' . (int)$id_product)
            ->where('id_product_attribute = ' . (int)$id_product_attribute)
            ->where('id_shop = ' . (int)$id_shop);
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new ShoppingfeedProduct($id);
        }
        return false;
    }
}
