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
