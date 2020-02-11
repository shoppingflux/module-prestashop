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
 * This class represents an order to be synchronized with the Shopping Feed API
 */
class ShoppingfeedOrder extends ObjectModel
{
    /** @var int The order's id in Shopping Feed's internal system */
    public $id_internal_shoppingfeed;
    
    /** @var int The order's id on the original marketplace */
    public $id_order_marketplace;

    /** @var string name_marketplace */
    public $name_marketplace;

    /** @var int The order's id */
    public $id_order;
    
    /** @var string The payment method used on the marketplace */
    public $payment_method;

    /** @var bool Status command send */
    public $shipped_sent;

    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'shoppingfeed_order',
        'primary' => 'id_shoppingfeed_order',
        'fields' => array(
            'id_internal_shoppingfeed' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'allow_null' => true,
            ),
            'id_order_marketplace' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'allow_null' => true,
            ),
            'name_marketplace' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
            ),
            'id_order' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true,
            ),
            'payment_method' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
            ),
            'date_marketplace_creation' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'required' => true,
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ),
        ),
    );
    
    public static function getByIdOrder($id_order)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_order')
            ->where("id_order = " . (int)$id_order);
        $shoppingfeed_order_data = DB::getInstance()->getRow($query);

        if ($shoppingfeed_order_data) {
            $shoppingfeedOrder = new ShoppingfeedOrder();
            $shoppingfeedOrder->hydrate($shoppingfeed_order_data);
            return $shoppingfeedOrder;
        }
        
        return false;
    }
    
    public function setReferenceFromOrder($force = false)
    {
        if ($this->id_order_marketplace && !$force) {
            return true;
        }
        
        $messages = Message::getMessagesByOrderId($this->id_order, true);
        if (empty($messages)) {
            return false;
        }
        
        // Check messages from first to last
        $id_order_marketplace = null;
        foreach (array_reverse($messages) as $message) {
            $explodedMessage = explode(':', $message['message']);
            if (!empty($explodedMessage[1])) {
                $id_order_marketplace = trim($explodedMessage[1]);
                break;
            }
        }

        if (!$id_order_marketplace) {
            return false;
        }
        
        $this->id_order_marketplace = $id_order_marketplace;
        $this->save();
        return true;
    }
    
    public static function existsInternalId($id_internal_shoppingfeed)
    {
        $query = new DbQuery();
        $query->select('1')
            ->from('shoppingfeed_order')
            ->where("id_internal_shoppingfeed = " . (int)$id_internal_shoppingfeed);
        $shoppingfeed_order_data = DB::getInstance()->getRow($query);

        return $shoppingfeed_order_data ? true : false;
    }
    
    /**
     * Checks if a given marketplace manages quantities on its own
     * 
     * @param string $name_marketplace
     * @return bool true if the marketplace manages the quantities
     */
    public static function isMarketplaceManagedQuantities($name_marketplace)
    {
        return in_array(Tools::strtolower($name_marketplace), array(
            'amazon fba',
            'epmm',
            'clogistique'
        ));
    }
}