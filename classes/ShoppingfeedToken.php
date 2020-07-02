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

class ShoppingfeedToken extends ObjectModel
{
    public $id_shoppingfeed_token;

    public $shop_id;

    public $lang_id;

    public $currency_id;

    public $content;

    public $active;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_token',
        'primary' => 'id_shoppingfeed_token',
        'fields' => array(
            'shop_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'lang_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'currency_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'content' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'unique' => true,
                'required' => true,
            ),
            'active' => array(
                'type' => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
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
            'shops' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Shop',
                'association' => 'shop',
                'field' => 'shop_id',
            ),
            'langs' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Language',
                'association' => 'language',
                'field' => 'lang_id',
            ),
            'currencies' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Currency',
                'association' => 'currency',
                'field' => 'currency_id',
            ),
        ),
    );

    public function findALlActiveByShops($idShops)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('shop_id IN('.implode(', ', $idShops).')')
            ->where('active = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ;
    }

    public function addToken($shop_id, $lang_id, $currency_id, $token)
    {
        if ($this->findByToken($token) !== false) {

            throw new Exception("Duplicate entry for token $token");
        }
        $this->shop_id = $shop_id;
        $this->lang_id = $lang_id;
        $this->currency_id = $currency_id;
        $this->content = $token;

        return $this->save();

    }

    public function findByToken($token)
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where("content = '$token'")
        ;

        return  Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }


    public function findALl()
    {
        $sql = new DbQuery();
        $sql->select('sft.content as token, sft.active, s.name as shop_name, l.name as lang_name, c.name as currency_name')
            ->from(self::$definition['table'], 'sft')
            ->innerJoin(\Shop::$definition['table'], 's', 's.id_shop = sft.shop_id')
            ->innerJoin(\Language::$definition['table'], 'l', 'l.id_lang = sft.lang_id')
            ->innerJoin(\Currency::$definition['table'], 'c', 'c.id_currency = sft.currency_id');


        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ;
    }
}