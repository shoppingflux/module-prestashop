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
 * This class represents an order to be synchronized with the Shopping Feed API
 */
class ShoppingfeedTaskOrder extends ObjectModel
{
    const ACTION_SYNC_STATUS = 'SYNC_STATUS';

    const ACTION_UPLOAD_INVOICE = 'UPLOAD_INVOICE';

    // As in, "check the ticket related to the Order Status synchronization"
    const ACTION_CHECK_TICKET_SYNC_STATUS = 'CHECK_TICKET_SYNC_STATUS';

    /** @var string The action to execute for this order */
    public $action;

    /** @var int The order's id */
    public $id_order;

    /** @var string ticket number of order update */
    public $ticket_number;

    /** @var string batch id of order update in SF's system. A batch is a
     * collection of tickets.
     */
    public $batch_id;

    /** @var string The date and time after which the order will be updated.
     * If null, the order will never be updated.
     */
    public $update_at;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'shoppingfeed_task_order',
        'primary' => 'id_shoppingfeed_task_order',
        'fields' => [
            'action' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'unique' => true,
                'values' => [
                    self::ACTION_SYNC_STATUS,
                    self::ACTION_CHECK_TICKET_SYNC_STATUS,
                    self::ACTION_UPLOAD_INVOICE,
                ],
            ],
            'id_order' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true,
            ],
            'ticket_number' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
                'allow_null' => true,
                'unique' => true,
            ],
            'batch_id' => [
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
                'allow_null' => true,
            ],
            'update_at' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'allow_null' => true,
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
            'orders' => [
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Order',
                'association' => 'order_shoppingfeedtask',
                'field' => 'id_order',
                'multishop' => true,
            ],
        ],
    ];

    /**
     * Attempts to retrieve an object using its id_order and action. Returns
     * false if none was found.
     *
     * @param $id_order
     * @param $action
     *
     * @return bool|ShoppingfeedTaskOrder
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFromOrderAction($id_order, $action)
    {
        $sql = new DbQuery();
        $sql->select('id_shoppingfeed_task_order')
            ->from(self::$definition['table'])
            ->where('id_order = ' . (int) $id_order)
            ->where("action = '" . pSQL($action) . "'");

        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new ShoppingfeedTaskOrder($id);
        }

        return false;
    }

    /**
     * @param string $ticket
     *
     * @return self
     */
    public static function getFromTicketNumber($ticket)
    {
        $sql = (new DbQuery())
            ->from(self::$definition['table'])
            ->where(sprintf('ticket_number LIKE "%s"', pSQL($ticket)));

        $result = Db::getInstance()->getRow($sql);

        if (empty($result)) {
            return new self();
        }

        $obj = new self();
        $obj->hydrate($result);

        return $obj;
    }
}
