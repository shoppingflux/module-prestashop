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

use ShoppingfeedAddon\Services\SfTools;

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

    public $merchant;

    public $shoppingfeed_store_id;

    public $feed_key;

    public $date_add;

    public $date_upd;

    public static $definition = [
        'table' => 'shoppingfeed_token',
        'primary' => 'id_shoppingfeed_token',
        'fields' => [
            'id_shop' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'id_lang' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'id_currency' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'content' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'unique' => true,
                'required' => true,
            ],
            'active' => [
                'type' => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
            ],
            'merchant' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'size' => 100,
            ],
            'shoppingfeed_store_id' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            'feed_key' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'unique' => true,
                'required' => true,
                'size' => 50,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
        ],
        'associations' => [
            'shops' => [
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Shop',
                'association' => 'shop',
                'field' => 'id_shop',
            ],
            'langs' => [
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Language',
                'association' => 'language',
                'field' => 'id_lang',
            ],
            'currencies' => [
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Currency',
                'association' => 'currency',
                'field' => 'id_currency',
            ],
        ],
    ];

    public function findAllActive()
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('active = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function findAllActiveByShops($idShops)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('id_shop IN(' . implode(', ', array_map('intval', $idShops)) . ')')
            ->where('active = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function addToken($id_shop, $id_lang, $id_currency, $token, $shoppingfeed_store_id, $merchant)
    {
        if ($this->findByToken($token, $shoppingfeed_store_id) !== false) {
            $module = Module::getInstanceByName('shoppingfeed');
            throw new Exception(sprintf($module->l('Duplicate entry for Store ID %d', 'ShoppingfeedToken'), (int) $shoppingfeed_store_id));
        }

        $this->id_shop = (int) $id_shop;
        $this->id_lang = (int) $id_lang;
        $this->id_currency = (int) $id_currency;
        $this->content = $token;
        $this->shoppingfeed_store_id = (int) $shoppingfeed_store_id;
        $this->merchant = pSQL($merchant);
        $this->feed_key = (new SfTools())->hash(uniqid());
        $this->active = true;

        return $this->save();
    }

    public function findByToken($token, $shoppingfeed_store_id = null)
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where('content = "' . pSQL($token) . '"')
        ;

        if ($shoppingfeed_store_id) {
            $query->where('shoppingfeed_store_id = ' . (int) $shoppingfeed_store_id);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }

    public function getDefaultToken()
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where('active = 1')
        ;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }

    public function findAll()
    {
        $sql = new DbQuery();
        $sql
            ->from(self::$definition['table'], 'sft')
            ->innerJoin(Shop::$definition['table'], 's', 's.id_shop = sft.id_shop')
            ->innerJoin(Language::$definition['table'], 'l', 'l.id_lang = sft.id_lang')
            ->innerJoin(Currency::$definition['table'], 'c', 'c.id_currency = sft.id_currency');
        $sql->select('sft.merchant');
        $sql->select('sft.shoppingfeed_store_id');

        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
            $sql->select('sft.id_shop, sft.id_shoppingfeed_token, sft.content as token, sft.active, sft.feed_key, s.name as shop_name, l.name as lang_name, cl.name as currency_name')
                ->innerJoin(Currency::$definition['table'] . '_lang', 'cl', 'cl.id_currency = sft.id_currency and cl.id_lang = ' . Context::getContext()->language->id);
        } else {
            $sql->select('sft.id_shop, sft.id_shoppingfeed_token, sft.content as token, sft.active, sft.feed_key, s.name as shop_name, l.name as lang_name, c.name as currency_name');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function findByFeedKey($feed_key)
    {
        return Db::getInstance()->getRow(
            (new DbQuery())
                ->from(self::$definition['table'])
                ->where(sprintf('feed_key="%s"', pSQL($feed_key)))
        );
    }
}
