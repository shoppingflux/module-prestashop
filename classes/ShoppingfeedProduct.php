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

class ShoppingfeedProduct extends ObjectModel
{
    const ACTION_SYNC_STOCK = "SYNC_STOCK";

    // The action to execute for this product
    public $action;

    // The product's id
    public $id_product;

    // The combination
    // TODO : what if it's not specified ? Apply to all products ? None ?
    public $id_product_attribute;

    // Multishop
    public $id_shop;

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
            ),
            'id_product' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ),
            'id_product_attribute' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ),
            'id_shop' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
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

    public static function getActionsArray()
    {
        return array(
            self::ACTION_SYNC_STOCK
        );
    }

    public static function saveAction($action, $id_product, $id_shop_list, $id_product_attribute = null)
    {
        if (!in_array($action, self::getActionsArray())) {
            return false;
        }

        foreach ($id_shop_list as $id_shop) {
            $query = "INSERT IGNORE INTO " . _DB_PREFIX_ . "shoppingfeed_product(`action`, `id_product`, `id_product_attribute`, `id_shop`) " .
                "VALUES (" .
                "'" . pSQL($action) . "', "
                . (int)$id_product . ", "
                . ((int)$id_product_attribute ? (int)$id_product_attribute : 0) . ", "
                . (int)$id_shop
                . ")";
            Db::getInstance()->execute($query);
        }
    }

    public static function getProductsToSync() {
        $query = new DbQuery();
        $query->select('id_shoppingfeed_product, action, id_product, id_product_attribute, id_shop')
            ->from('shoppingfeed_product')
            ->where("action = '" . pSQL(ShoppingfeedProduct::ACTION_SYNC_STOCK) . "'")
            ->limit(Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS))
            ->orderBy('date_add ASC');
        return Db::getInstance()->executeS($query);
    }
}