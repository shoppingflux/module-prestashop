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
 * A carrier selection rule (#34133): for orders imported with a given token,
 * "if the conditions are met, then select this carrier".
 *
 * Conditions are stored as JSON, as groups joined by OR, each group being a
 * list of conditions joined by AND:
 * {"groups": [{"conditions": [{"criterion": "...", "operator": "...", "value": ...}]}]}
 */
class ShoppingfeedCarrierRule extends ObjectModel
{
    /** @var int */
    public $id_shoppingfeed_token;

    /** @var int Order of evaluation among the rules of the same token */
    public $position;

    /** @var string JSON, see getConditions() */
    public $conditions;

    /** @var int The selected Prestashop carrier id_reference */
    public $id_carrier_reference;

    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'shoppingfeed_carrier_rule',
        'primary' => 'id_shoppingfeed_carrier_rule',
        'fields' => [
            'id_shoppingfeed_token' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'position' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            // TYPE_HTML without size is created as MEDIUMTEXT by the installer.
            // Values are validated by the admin form handler, not here.
            'conditions' => [
                'type' => ObjectModel::TYPE_HTML,
                'required' => true,
            ],
            'id_carrier_reference' => [
                'type' => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
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
    ];

    /**
     * @return array ['groups' => [['conditions' => [['criterion' => ..., 'operator' => ..., 'value' => ...]]]]]
     */
    public function getConditions()
    {
        $conditions = json_decode((string) $this->conditions, true);

        if (!is_array($conditions) || !isset($conditions['groups']) || !is_array($conditions['groups'])) {
            return ['groups' => []];
        }

        return $conditions;
    }

    public function setConditions(array $conditions)
    {
        $this->conditions = json_encode($conditions);
    }

    /**
     * @param int $idToken
     *
     * @return ShoppingfeedCarrierRule[]
     */
    public static function getByToken($idToken)
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->where('id_shoppingfeed_token = ' . (int) $idToken)
            ->orderBy('position ASC, ' . self::$definition['primary'] . ' ASC');

        return self::hydrateRules(Db::getInstance()->executeS($query));
    }

    /**
     * @return ShoppingfeedCarrierRule[]
     */
    public static function getAll()
    {
        $query = (new DbQuery())
            ->select('*')
            ->from(self::$definition['table'])
            ->orderBy('id_shoppingfeed_token ASC, position ASC, ' . self::$definition['primary'] . ' ASC');

        return self::hydrateRules(Db::getInstance()->executeS($query));
    }

    /**
     * @return bool
     */
    public static function deleteAll()
    {
        return Db::getInstance()->delete(self::$definition['table']);
    }

    /**
     * @param array|false $rows
     *
     * @return ShoppingfeedCarrierRule[]
     */
    protected static function hydrateRules($rows)
    {
        if (!$rows) {
            return [];
        }

        return array_map(
            function ($row) {
                $rule = new ShoppingfeedCarrierRule();
                $rule->hydrate($row);

                return $rule;
            },
            $rows
        );
    }
}
