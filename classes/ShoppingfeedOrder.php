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

    /** @var int */
    public $id_shoppingfeed_token;

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
                'size' => 50,
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
                'size' => 50,
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
                'size' => 50,
            ),
            'date_marketplace_creation' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'allow_null' => true,
            ),
            'id_shoppingfeed_token' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isInt',
                'required' => true,
                'allow_null' => true
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

    public static function getByShoppingfeedInternalId($id_internal_shoppingfeed)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('shoppingfeed_order')
            ->where("id_internal_shoppingfeed = " . (int)$id_internal_shoppingfeed);
        $shoppingfeed_order_data = DB::getInstance()->getRow($query);

        if ($shoppingfeed_order_data) {
            $shoppingfeedOrder = new ShoppingfeedOrder();
            $shoppingfeedOrder->hydrate($shoppingfeed_order_data);
            return $shoppingfeedOrder;
        }

        return false;
    }

}
