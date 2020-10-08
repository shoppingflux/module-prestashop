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

class ShoppingfeedToken extends ObjectModel
{
    public $id_shoppingfeed_token;

    public $id_shop;

    public $id_lang;

    public $id_currency;

    public $content;

    public $active;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_token',
        'primary' => 'id_shoppingfeed_token',
        'fields' => array(
            'id_shop' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'id_lang' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'id_currency' => array(
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
                'field' => 'id_shop',
            ),
            'langs' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Language',
                'association' => 'language',
                'field' => 'id_lang',
            ),
            'currencies' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Currency',
                'association' => 'currency',
                'field' => 'id_currency',
            ),
        ),
    );

    public function findAllActive()
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('active = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ;
    }

    public function findAllActiveByShops($idShops)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('id_shop IN('.implode(', ', $idShops).')')
            ->where('active = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ;
    }

    public function addToken($id_shop, $id_lang, $id_currency, $token)
    {
        if ($this->findByToken($token) !== false) {

            throw new Exception("Duplicate entry for token $token");
        }
        $this->id_shop = $id_shop;
        $this->id_lang = $id_lang;
        $this->id_currency = $id_currency;
        $this->content = $token;
        $this->active = true;

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

    public function getDefaultToken()
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
        ;

        return  Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }

    public function findAll()
    {
        $sql = new DbQuery();
        $sql
            ->from(self::$definition['table'], 'sft')
            ->innerJoin(\Shop::$definition['table'], 's', 's.id_shop = sft.id_shop')
            ->innerJoin(\Language::$definition['table'], 'l', 'l.id_lang = sft.id_lang')
            ->innerJoin(\Currency::$definition['table'], 'c', 'c.id_currency = sft.id_currency')
        ;

        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
            $sql->select('sft.id_shoppingfeed_token, sft.content as token, sft.active, s.name as shop_name, l.name as lang_name, cl.name as currency_name')
                ->innerJoin(\Currency::$definition['table'] . '_lang', 'cl', 'cl.id_currency = sft.id_currency and cl.id_lang = ' . Context::getContext()->language->id);
        } else {
            $sql->select('sft.id_shoppingfeed_token, sft.content as token, sft.active, s.name as shop_name, l.name as lang_name, c.name as currency_name');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ;
    }
}
