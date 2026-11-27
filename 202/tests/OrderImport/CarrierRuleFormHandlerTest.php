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
use ShoppingfeedAddon\CarrierRule\CarrierRuleFormHandler;

/**
 * Carrier selection rules: admin form validation and saving (#34133).
 * Fixtures: active token 1, carriers with references 2 and 5.
 */
class CarrierRuleFormHandlerTest extends TestCase
{
    /** @var CarrierRuleFormHandler */
    private $handler;

    protected function setUp(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
        $this->handler = new CarrierRuleFormHandler();
    }

    protected function tearDown(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
    }

    public function testValidRuleIsKeptAndPositioned(): void
    {
        $result = $this->handler->normalize([
            $this->rule(5, [['country', 'in', ['FR']]]),
            $this->rule(2, [['marketplace', 'equals', 'Amazon']]),
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertTrue($this->handler->save($result['rules']));

        $rules = \ShoppingfeedCarrierRule::getByToken(1);
        $this->assertCount(2, $rules);
        $this->assertSame([0, 1], [(int) $rules[0]->position, (int) $rules[1]->position]);
        $this->assertSame([5, 2], [(int) $rules[0]->id_carrier_reference, (int) $rules[1]->id_carrier_reference]);
        $this->assertSame(
            ['groups' => [['conditions' => [['criterion' => 'country', 'operator' => 'in', 'value' => ['FR']]]]]],
            $rules[0]->getConditions()
        );
    }

    public function testSaveReplacesExistingRules(): void
    {
        $this->handler->save($this->handler->normalize([$this->rule(5, [['country', 'in', ['FR']]])])['rules']);
        $this->handler->save($this->handler->normalize([$this->rule(2, [['country', 'in', ['FR']]])])['rules']);

        $rules = \ShoppingfeedCarrierRule::getAll();
        $this->assertCount(1, $rules);
        $this->assertSame(2, (int) $rules[0]->id_carrier_reference);
    }

    public function testCountryValuesAreStoredAsIso(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['country', 'in', ['fr', ' es ']]])]);
        $this->assertSame([], $result['errors']);
        $this->assertSame(['FR', 'ES'], $this->firstValue($result));

        $result = $this->handler->normalize([$this->rule(2, [['country', 'in', ['France']]])]);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('France', $result['errors'][0]);
    }

    public function testWeightDecimalCommaAndTrimming(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['weight', 'gt', ' 5,5 ']])]);
        $this->assertSame([], $result['errors']);
        $this->assertSame(5.5, $this->firstValue($result));

        $result = $this->handler->normalize([$this->rule(2, [['weight', 'gt', 'abc']])]);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Rule #1', $result['errors'][0]);

        $result = $this->handler->normalize([$this->rule(2, [['weight', 'gt', '-1']])]);
        $this->assertCount(1, $result['errors']);
    }

    public function testTextValuesTrimmed(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['marketplace', 'equals', '  Amazon ']])]);

        $this->assertSame('Amazon', $this->firstValue($result));
    }

    public function testListValuesDeduplicated(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['marketplace', 'in', ['Amazon', 'Amazon', '', ' Amazon ']]])]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(['Amazon'], $this->firstValue($result));
    }

    public function testConditionWithoutValueRejected(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['marketplace', 'in', ['', ' ']]])]);
        $this->assertNotEmpty($result['errors']);

        $result = $this->handler->normalize([$this->rule(2, [['delivery_mode', 'contains', '  ']])]);
        $this->assertNotEmpty($result['errors']);
    }

    public function testInvalidCriterionOrOperatorRejected(): void
    {
        $result = $this->handler->normalize([$this->rule(2, [['zipcode', 'equals', '75001']])]);
        $this->assertNotEmpty($result['errors']);

        $result = $this->handler->normalize([$this->rule(2, [['weight', 'in', ['5']]])]);
        $this->assertNotEmpty($result['errors']);
    }

    public function testEmptyGroupsDroppedAndEmptyRuleRejected(): void
    {
        $rule = $this->rule(2, [], [['country', 'in', ['FR']]]);
        $result = $this->handler->normalize([$rule]);
        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['rules'][0]['groups']);

        $result = $this->handler->normalize([$this->rule(2, [], [])]);
        $this->assertSame(['Rule #1 has no condition.'], $result['errors']);
        $this->assertSame([], $result['rules']);
    }

    public function testUnknownCarrierOrTokenRejected(): void
    {
        $result = $this->handler->normalize([$this->rule(999999, [['country', 'in', ['FR']]])]);
        $this->assertSame(['Rule #1: select a carrier.'], $result['errors']);

        $rule = $this->rule(2, [['country', 'in', ['FR']]]);
        $rule['id_shoppingfeed_token'] = 999;
        $result = $this->handler->normalize([$rule]);
        $this->assertSame(['Rule #1: select a token.'], $result['errors']);
    }

    public function testMalformedInputDoesNotCrash(): void
    {
        $result = $this->handler->normalize(['not a rule', ['groups' => 'nope'], ['groups' => [['conditions' => ['x']]]]]);

        $this->assertNotEmpty($result['errors']);
        $this->assertSame([], $result['rules']);
    }

    public function testToFormDataRoundTrip(): void
    {
        $result = $this->handler->normalize([
            $this->rule(5, [['country', 'in', ['FR']], ['weight', 'gt', '2']], [['marketplace', 'equals', 'Amazon']]),
            $this->rule(2, [['delivery_mode', 'not_in', ['Colissimo']]]),
        ]);
        $this->handler->save($result['rules']);

        $this->assertSame($result['rules'], $this->handler->toFormData());
    }

    /**
     * Each group is a list of [criterion, operator, value] triples
     */
    private function rule($idCarrierReference, array ...$groups)
    {
        return [
            'id_shoppingfeed_token' => 1,
            'id_carrier_reference' => $idCarrierReference,
            'groups' => array_map(
                function ($group) {
                    return ['conditions' => array_map(
                        function ($condition) {
                            return ['criterion' => $condition[0], 'operator' => $condition[1], 'value' => $condition[2]];
                        },
                        $group
                    )];
                },
                $groups
            ),
        ];
    }

    private function firstValue(array $result)
    {
        return $result['rules'][0]['groups'][0]['conditions'][0]['value'];
    }
}
