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

namespace Tests\OrderImport;

use PHPUnit\Framework\TestCase;
use ShoppingfeedAddon\CarrierRule\ConditionEvaluator;
use ShoppingfeedAddon\CarrierRule\OrderCriteria;

/**
 * Carrier selection rules: condition evaluation (#34133)
 */
class CarrierRuleConditionEvaluatorTest extends TestCase
{
    /** @var ConditionEvaluator */
    private $evaluator;

    /** @var OrderCriteria */
    private $criteria;

    protected function setUp(): void
    {
        $this->evaluator = new ConditionEvaluator();
        $this->criteria = new OrderCriteria('Amazon', 'FR', 'Colissimo', 3.0);
    }

    public function testSingleAndGroup(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([
            ['country', 'in', ['FR', 'BE']],
            ['delivery_mode', 'in', ['Colissimo']],
        ])));
        $this->assertFalse($this->ruleMatches($this->rule([
            ['country', 'in', ['ES']],
            ['delivery_mode', 'in', ['Colissimo']],
        ])));
    }

    public function testOrGroupsSecondGroupAloneMatches(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule(
            [['country', 'in', ['ES']]],
            [['marketplace', 'in', ['amazon']], ['weight', 'gt', 2]]
        )));
    }

    public function testContains(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['delivery_mode', 'contains', 'lissi']])));
        $this->assertFalse($this->ruleMatches($this->rule([['delivery_mode', 'contains', 'relais']])));
        $this->assertFalse($this->ruleMatches($this->rule([['delivery_mode', 'contains', '']])));
    }

    public function testEquals(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['marketplace', 'equals', 'amazon']])));
        $this->assertFalse($this->ruleMatches($this->rule([['marketplace', 'equals', 'Amazon Business']])));
    }

    public function testIn(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['country', 'in', ['fr']]])));
        $this->assertFalse($this->ruleMatches($this->rule([['country', 'in', ['ES', 'IT']]])));
    }

    public function testNotIn(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['country', 'not_in', ['ES']]])));
        $this->assertFalse($this->ruleMatches($this->rule([['country', 'not_in', ['FR']]])));
        $this->assertTrue($this->ruleMatches($this->rule([['country', 'not_in', []]])));
    }

    public function testWeightGreaterThanIsStrict(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['weight', 'gt', 2.99]])));
        $this->assertFalse($this->ruleMatches($this->rule([['weight', 'gt', 3]])));
        $this->assertFalse($this->ruleMatches($this->rule([['weight', 'gt', '3']])));
    }

    public function testTextComparisonIsCaseInsensitiveAndTrimmed(): void
    {
        $this->assertTrue($this->ruleMatches($this->rule([['marketplace', 'equals', '  AMAZON ']])));
        $this->assertTrue($this->ruleMatches($this->rule([['delivery_mode', 'in', [' COLISSIMO']]])));
    }

    public function testEmptyGroupsNeverMatch(): void
    {
        $this->assertFalse($this->ruleMatches(['groups' => []]));
        $this->assertFalse($this->ruleMatches(['groups' => [['conditions' => []]]]));
    }

    public function testUnknownCriterionOrOperatorIsFalse(): void
    {
        set_error_handler(function ($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        });
        try {
            $this->assertFalse($this->ruleMatches($this->rule([['zipcode', 'equals', '75001']])));
            $this->assertFalse($this->ruleMatches($this->rule([['country', 'gt', 1]])));
            $this->assertFalse($this->ruleMatches($this->rule([['weight', 'equals', 3]])));
            $this->assertFalse($this->ruleMatches($this->rule([['country', 'in', 'FR']])));
            $this->assertFalse($this->ruleMatches($this->rule([['weight', 'gt', 'heavy']])));
            $this->assertFalse($this->ruleMatches(['groups' => [['conditions' => [['criterion' => 'country']]]]]));
            $this->assertFalse($this->ruleMatches(['groups' => ['not an array']]));
        } finally {
            restore_error_handler();
        }
    }

    public function testOperatorsByCriterion(): void
    {
        $text = ['contains', 'equals', 'in', 'not_in'];
        $this->assertSame(
            ['marketplace' => $text, 'country' => $text, 'delivery_mode' => $text, 'weight' => ['gt']],
            ConditionEvaluator::getOperatorsByCriterion()
        );
    }

    public function testCriteriaValues(): void
    {
        $this->assertSame('Amazon', $this->criteria->getValue(ConditionEvaluator::CRITERION_MARKETPLACE));
        $this->assertSame('FR', $this->criteria->getValue(ConditionEvaluator::CRITERION_COUNTRY));
        $this->assertSame('Colissimo', $this->criteria->getValue(ConditionEvaluator::CRITERION_DELIVERY_MODE));
        $this->assertSame(3.0, $this->criteria->getValue(ConditionEvaluator::CRITERION_WEIGHT));
        $this->assertNull($this->criteria->getValue('zipcode'));
    }

    private function ruleMatches(array $conditions)
    {
        return $this->evaluator->matches($conditions, $this->criteria);
    }

    /**
     * Each argument is a group: a list of [criterion, operator, value] triples
     */
    private function rule(array ...$groups)
    {
        return ['groups' => array_map(
            function ($group) {
                return ['conditions' => array_map(
                    function ($condition) {
                        return ['criterion' => $condition[0], 'operator' => $condition[1], 'value' => $condition[2]];
                    },
                    $group
                )];
            },
            $groups
        )];
    }
}
