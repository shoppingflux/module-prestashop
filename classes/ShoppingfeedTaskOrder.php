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

    /** @var string The action to execute for this order */
    public $action;

    /** @var int The order's id */
    public $id_order;

    /** @var string ticket number of order update */
    public $ticket_number;

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
                'values' => array(self::ACTION_SYNC_STATUS),
            ),
            'id_order' => array(
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'unique' => true
            ),
            'ticket_number' => array(
                'type' => ObjectModel::TYPE_STRING,
                'validate' => 'isString',
                'required' => false
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
    );
}