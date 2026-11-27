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
 * Validates and saves the carrier selection rules posted by the admin form.
 *
 * A rule, as posted and as returned by toFormData():
 * ['id_shoppingfeed_token' => int, 'id_carrier_reference' => int,
 *  'groups' => [['conditions' => [['criterion' => string, 'operator' => string, 'value' => mixed]]]]]
 */
class CarrierRuleFormHandler
{
    /**
     * @param array $postedRules rules in display order
     *
     * @return array ['rules' => valid rules, normalized, 'errors' => string[]]
     */
    public function normalize(array $postedRules)
    {
        $activeTokenIds = $this->getActiveTokenIds();
        $rules = [];
        $errors = [];

        foreach (array_values($postedRules) as $index => $postedRule) {
            $number = $index + 1;
            $ruleErrors = [];
            $postedRule = is_array($postedRule) ? $postedRule : [];

            $idToken = isset($postedRule['id_shoppingfeed_token']) ? (int) $postedRule['id_shoppingfeed_token'] : 0;
            if (!in_array($idToken, $activeTokenIds, true)) {
                $ruleErrors[] = sprintf($this->l('Rule #%d: select a token.'), $number);
            }

            $idCarrierReference = isset($postedRule['id_carrier_reference']) ? (int) $postedRule['id_carrier_reference'] : 0;
            if ($idCarrierReference <= 0 || !\Validate::isLoadedObject(\Carrier::getCarrierByReference($idCarrierReference))) {
                $ruleErrors[] = sprintf($this->l('Rule #%d: select a carrier.'), $number);
            }

            $groups = [];
            $postedGroups = isset($postedRule['groups']) && is_array($postedRule['groups']) ? $postedRule['groups'] : [];
            foreach ($postedGroups as $postedGroup) {
                $postedConditions = is_array($postedGroup) && isset($postedGroup['conditions']) && is_array($postedGroup['conditions'])
                    ? $postedGroup['conditions']
                    : [];
                $conditions = [];
                foreach ($postedConditions as $postedCondition) {
                    $condition = $this->normalizeCondition($postedCondition, $number, $ruleErrors);
                    if ($condition !== null) {
                        $conditions[] = $condition;
                    }
                }
                if (!empty($conditions)) {
                    $groups[] = ['conditions' => $conditions];
                }
            }

            if (empty($groups) && empty($ruleErrors)) {
                $ruleErrors[] = sprintf($this->l('Rule #%d has no condition.'), $number);
            }

            if (!empty($ruleErrors)) {
                $errors = array_merge($errors, $ruleErrors);
                continue;
            }

            $rules[] = [
                'id_shoppingfeed_token' => $idToken,
                'id_carrier_reference' => $idCarrierReference,
                'groups' => $groups,
            ];
        }

        return ['rules' => $rules, 'errors' => $errors];
    }

    /**
     * Replaces all the stored rules. Positions follow the order of the rules within each token.
     *
     * @param array $normalizedRules rules returned by normalize()
     *
     * @return bool
     */
    public function save(array $normalizedRules)
    {
        $success = \ShoppingfeedCarrierRule::deleteAll();
        $positions = [];

        foreach ($normalizedRules as $normalizedRule) {
            $idToken = (int) $normalizedRule['id_shoppingfeed_token'];
            $positions[$idToken] = isset($positions[$idToken]) ? $positions[$idToken] + 1 : 0;

            $rule = new \ShoppingfeedCarrierRule();
            $rule->id_shoppingfeed_token = $idToken;
            $rule->position = $positions[$idToken];
            $rule->id_carrier_reference = (int) $normalizedRule['id_carrier_reference'];
            $rule->setConditions(['groups' => $normalizedRule['groups']]);
            $success = $rule->add() && $success;
        }

        return $success;
    }

    /**
     * @return array the stored rules, in the shape of normalize()'s rules
     */
    public function toFormData()
    {
        return array_map(
            function (\ShoppingfeedCarrierRule $rule) {
                $conditions = $rule->getConditions();

                return [
                    'id_shoppingfeed_token' => (int) $rule->id_shoppingfeed_token,
                    'id_carrier_reference' => (int) $rule->id_carrier_reference,
                    'groups' => $conditions['groups'],
                ];
            },
            \ShoppingfeedCarrierRule::getAll()
        );
    }

    /**
     * @param mixed $postedCondition
     * @param int $number rule number, for error messages
     * @param array $errors
     *
     * @return array|null null when the condition is invalid (an error is added)
     */
    protected function normalizeCondition($postedCondition, $number, array &$errors)
    {
        $operatorsByCriterion = ConditionEvaluator::getOperatorsByCriterion();
        $criterion = is_array($postedCondition) && isset($postedCondition['criterion']) ? $postedCondition['criterion'] : null;
        $operator = is_array($postedCondition) && isset($postedCondition['operator']) ? $postedCondition['operator'] : null;
        $value = is_array($postedCondition) && isset($postedCondition['value']) ? $postedCondition['value'] : null;

        if (!is_string($criterion) || !isset($operatorsByCriterion[$criterion])
            || !in_array($operator, $operatorsByCriterion[$criterion], true)) {
            $errors[] = sprintf($this->l('Rule #%d: invalid condition.'), $number);

            return null;
        }

        if ($operator === ConditionEvaluator::OPERATOR_GREATER_THAN) {
            $weight = is_scalar($value) ? str_replace(',', '.', trim((string) $value)) : '';
            if (!is_numeric($weight) || (float) $weight < 0) {
                $errors[] = sprintf($this->l('Rule #%d: the weight must be a positive number.'), $number);

                return null;
            }

            return ['criterion' => $criterion, 'operator' => $operator, 'value' => (float) $weight];
        }

        $isList = in_array($operator, [ConditionEvaluator::OPERATOR_IN, ConditionEvaluator::OPERATOR_NOT_IN], true);
        $values = $this->normalizeTextValues($isList ? (is_array($value) ? $value : []) : [$value]);

        if ($criterion === ConditionEvaluator::CRITERION_COUNTRY) {
            if ($operator !== ConditionEvaluator::OPERATOR_CONTAINS) {
                foreach ($values as $country) {
                    if (!\Validate::isLanguageIsoCode($country) || !\Country::getByIso($country)) {
                        $errors[] = sprintf($this->l('Rule #%d: unknown country code %s.'), $number, $country);

                        return null;
                    }
                }
            }
            $values = array_values(array_unique(array_map('strtoupper', $values)));
        }

        if (empty($values)) {
            $errors[] = sprintf($this->l('Rule #%d: a condition has no value.'), $number);

            return null;
        }

        return ['criterion' => $criterion, 'operator' => $operator, 'value' => $isList ? $values : $values[0]];
    }

    /**
     * @param array $values
     *
     * @return string[] trimmed, non empty, unique
     */
    protected function normalizeTextValues(array $values)
    {
        $normalized = [];

        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return int[]
     */
    protected function getActiveTokenIds()
    {
        $tokens = (new \ShoppingfeedToken())->findAllActive();

        return array_map(
            function ($token) {
                return (int) $token['id_shoppingfeed_token'];
            },
            is_array($tokens) ? $tokens : []
        );
    }

    /**
     * @param string $msg
     *
     * @return string
     */
    protected function l($msg)
    {
        return \Translate::getModuleTranslation('shoppingfeed', $msg, 'CarrierRuleFormHandler');
    }
}
