<?php
/**
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
 */

namespace ShoppingfeedAddon\CarrierRule;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Evaluates the conditions of a carrier selection rule against an order.
 *
 * A rule matches when at least one of its groups (OR) has at least one
 * condition and all its conditions (AND) are true. Malformed conditions
 * are false.
 */
class ConditionEvaluator
{
    public const CRITERION_MARKETPLACE = 'marketplace';
    public const CRITERION_COUNTRY = 'country';
    public const CRITERION_DELIVERY_MODE = 'delivery_mode';
    public const CRITERION_WEIGHT = 'weight';

    public const OPERATOR_CONTAINS = 'contains';
    public const OPERATOR_EQUALS = 'equals';
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = 'not_in';
    public const OPERATOR_GREATER_THAN = 'gt';

    /**
     * @return array [criterion => allowed operators]
     */
    public static function getOperatorsByCriterion()
    {
        $textOperators = [
            self::OPERATOR_CONTAINS,
            self::OPERATOR_EQUALS,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];

        return [
            self::CRITERION_MARKETPLACE => $textOperators,
            self::CRITERION_COUNTRY => $textOperators,
            self::CRITERION_DELIVERY_MODE => $textOperators,
            self::CRITERION_WEIGHT => [self::OPERATOR_GREATER_THAN],
        ];
    }

    /**
     * @param array $conditions see \ShoppingfeedCarrierRule::getConditions()
     * @param OrderCriteria $criteria
     *
     * @return bool
     */
    public function matches(array $conditions, OrderCriteria $criteria)
    {
        if (empty($conditions['groups']) || !is_array($conditions['groups'])) {
            return false;
        }

        foreach ($conditions['groups'] as $group) {
            if ($this->groupMatches($group, $criteria)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $group
     * @param OrderCriteria $criteria
     *
     * @return bool
     */
    protected function groupMatches($group, OrderCriteria $criteria)
    {
        if (!is_array($group) || empty($group['conditions']) || !is_array($group['conditions'])) {
            return false;
        }

        foreach ($group['conditions'] as $condition) {
            if (!$this->conditionMatches($condition, $criteria)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $condition
     * @param OrderCriteria $criteria
     *
     * @return bool
     */
    protected function conditionMatches($condition, OrderCriteria $criteria)
    {
        if (!is_array($condition) || !isset($condition['criterion'], $condition['operator']) || !array_key_exists('value', $condition)) {
            return false;
        }

        $operatorsByCriterion = self::getOperatorsByCriterion();
        $criterion = $condition['criterion'];
        $operator = $condition['operator'];
        $expected = $condition['value'];

        if (!is_string($criterion) || !isset($operatorsByCriterion[$criterion])
            || !in_array($operator, $operatorsByCriterion[$criterion], true)) {
            return false;
        }

        $actual = $criteria->getValue($criterion);

        switch ($operator) {
            case self::OPERATOR_GREATER_THAN:
                return is_numeric($expected) && (float) $actual > (float) $expected;
            case self::OPERATOR_CONTAINS:
                if (!is_scalar($expected) || $this->normalize($expected) === '') {
                    return false;
                }

                return mb_strpos($this->normalize($actual), $this->normalize($expected)) !== false;
            case self::OPERATOR_EQUALS:
                return is_scalar($expected) && $this->normalize($actual) === $this->normalize($expected);
            case self::OPERATOR_IN:
                return is_array($expected) && $this->inList($actual, $expected);
            case self::OPERATOR_NOT_IN:
                return is_array($expected) && !$this->inList($actual, $expected);
        }

        return false;
    }

    /**
     * @param mixed $actual
     * @param array $list
     *
     * @return bool
     */
    protected function inList($actual, array $list)
    {
        $actual = $this->normalize($actual);

        foreach ($list as $item) {
            if (is_scalar($item) && $this->normalize($item) === $actual) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function normalize($value)
    {
        return mb_strtolower(trim((string) $value));
    }
}
