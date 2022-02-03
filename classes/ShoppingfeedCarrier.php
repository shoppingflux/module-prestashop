<?php
/**
 *
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This class represents a carrier from a marketplace
 */
class ShoppingfeedCarrier extends ObjectModel
{
    /** @var string $name_marketplace */
    public $name_marketplace;

    /** @var string $name_carrier The carrier's name on the Shopping Feed platform */
    public $name_carrier;

    /** @var int $id_carrier_reference The linked Prestashop carrier id_reference */
    public $id_carrier_reference;

    /** @var bool $is_new Is this a new SF carrier ? Set to false when settings are saved */
    public $is_new;

    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_carrier',
        'primary' => 'id_shoppingfeed_carrier',
        'fields' => array(
            'name_marketplace' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'unique' => true,
                'size' => 50,
            ),
            'name_carrier' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'unique' => true,
                'size' => 190,
            ),
            'id_carrier_reference' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ),
            'is_new' => array(
                'type' => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
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

    public static function getByMarketplaceAndName($marketplace, $name)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_carrier')
            ->where('name_marketplace = "' . pSQL($marketplace) . '"')
            ->where('name_carrier = "' . pSQL($name) . '"');
        $result = Db::getInstance()->getRow($query);

        if (!$result) {
            return false;
        }

        $sfCarrier = new ShoppingfeedCarrier();
        $sfCarrier->hydrate($result);
        return $sfCarrier;
    }

    public static function getAllMarketplaces()
    {
        $query = new DbQuery();
        $query->select('name_marketplace')
            ->from('shoppingfeed_carrier')
            ->groupBy('name_marketplace');
        $result = Db::getInstance()->executeS($query);

        if (!$result) {
            return array();
        }

        return array_map(
            function ($i) {
                return $i['name_marketplace'];
            },
            $result
        );
    }

    public static function getAllCarriers($marketplace_filter = '')
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_carrier');

        if ($marketplace_filter) {
            $query->where('name_marketplace = "' . pSQL($marketplace_filter) . '"');
        }
        $result = Db::getInstance()->executeS($query);

        if (!$result) {
            return array();
        }

        return array_map(
            function ($i) {
                $o = new ShoppingfeedCarrier();
                $o->hydrate($i);
                return $o;
            },
            $result
        );
    }
}
