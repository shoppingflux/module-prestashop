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
class ShoppingfeedTaskOrder extends ObjectModel
{
    const ACTION_SYNC_STATUS = 'SYNC_STATUS';
    
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

    public static $definition = array(
        'table' => 'shoppingfeed_task_order',
        'primary' => 'id_shoppingfeed_task_order',
        'fields' => array(
            'action' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'unique' => true,
                'values' => array(self::ACTION_SYNC_STATUS, self::ACTION_CHECK_TICKET_SYNC_STATUS),
            ),
            'id_order' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique'   => true
            ),
            'ticket_number' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
                'allow_null' => true,
                'unique'    => true
            ),
            'batch_id' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'size' => 50,
                'allow_null' => true
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
            'orders' => array(
                'type' => ObjectModel::HAS_ONE,
                'object' => 'Order',
                'association' => 'order_shoppingfeedtask',
                'field' => 'id_order',
                'multishop' => true,
            ),
        ),
    );

    /**
     * Attempts to retrieve an object using its id_order and action. Returns
     * false if none was found.
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
            ->where('id_order = ' . (int)$id_order)
            ->where("action = '" . pSQL($action). "'");
        
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new ShoppingfeedTaskOrder($id);
        }
        
        return false;
    }
}
