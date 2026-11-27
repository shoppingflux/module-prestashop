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

/**
 * Carrier selection rules storage (#34133)
 */
class CarrierRuleModelTest extends TestCase
{
    protected function setUp(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
    }

    protected function tearDown(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
    }

    public function testGetByTokenOrdersByPositionAndFiltersToken(): void
    {
        $this->createRule(1, 1, 2);
        $this->createRule(1, 0, 7);
        $this->createRule(2, 0, 1);

        $rules = \ShoppingfeedCarrierRule::getByToken(1);

        $this->assertCount(2, $rules);
        $this->assertSame(7, (int) $rules[0]->id_carrier_reference);
        $this->assertSame(2, (int) $rules[1]->id_carrier_reference);
    }

    public function testGetAllOrdersByTokenThenPosition(): void
    {
        $this->createRule(2, 0, 1);
        $this->createRule(1, 1, 2);
        $this->createRule(1, 0, 7);

        $references = array_map(
            function ($rule) {
                return (int) $rule->id_carrier_reference;
            },
            \ShoppingfeedCarrierRule::getAll()
        );

        $this->assertSame([7, 2, 1], $references);
    }

    public function testConditionsRoundTrip(): void
    {
        $conditions = ['groups' => [['conditions' => [
            ['criterion' => 'country', 'operator' => 'in', 'value' => ['FR', 'BE']],
        ]]]];
        $rule = $this->createRule(1, 0, 2, $conditions);

        $this->assertSame($conditions, (new \ShoppingfeedCarrierRule($rule->id))->getConditions());
    }

    public function testMalformedJsonGivesNoConditions(): void
    {
        $rule = $this->createRule(1, 0, 2);
        \Db::getInstance()->update(
            'shoppingfeed_carrier_rule',
            ['conditions' => '{not json'],
            'id_shoppingfeed_carrier_rule = ' . (int) $rule->id
        );

        $this->assertSame(['groups' => []], (new \ShoppingfeedCarrierRule($rule->id))->getConditions());
    }

    public function testDeleteAll(): void
    {
        $this->createRule(1, 0, 2);
        $this->createRule(2, 0, 2);

        $this->assertTrue(\ShoppingfeedCarrierRule::deleteAll());
        $this->assertSame([], \ShoppingfeedCarrierRule::getAll());
    }

    private function createRule($idToken, $position, $idCarrierReference, array $conditions = ['groups' => []])
    {
        $rule = new \ShoppingfeedCarrierRule();
        $rule->id_shoppingfeed_token = $idToken;
        $rule->position = $position;
        $rule->id_carrier_reference = $idCarrierReference;
        $rule->setConditions($conditions);
        $this->assertTrue((bool) $rule->add());

        return $rule;
    }
}
