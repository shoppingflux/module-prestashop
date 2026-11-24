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

use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\CarrierRule\CarrierRuleResolver;
use ShoppingfeedAddon\CarrierRule\ConditionEvaluator;
use ShoppingfeedAddon\CarrierRule\OrderCriteria;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedClasslib\Registry;

/**
 * Carrier selection rules applied on order import (#34133).
 *
 * Dataset order-amazon.json: Amazon / FR / Colissimo, item "8" x 1.
 * Fixture mapping Amazon + Colissimo => carrier reference 1.
 */
class OrderImportCarrierRulesTest extends AbstractOrdeTestCase
{
    public const DEFAULT_CARRIER_REFERENCE = 2;

    /** @var string */
    private $originalDefaultCarrier;

    /** @var string */
    private $originalProductWeight;

    protected function setUp(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
        $this->originalDefaultCarrier = \Configuration::get(\Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE);
        $this->originalProductWeight = \Db::getInstance()->getValue('SELECT weight FROM ' . _DB_PREFIX_ . 'product WHERE id_product = 8');
        \Configuration::updateValue(\Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE, self::DEFAULT_CARRIER_REFERENCE);
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 1);
    }

    protected function tearDown(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
        \Configuration::updateValue(\Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE, $this->originalDefaultCarrier);
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);
        $this->setProductWeight($this->originalProductWeight);
    }

    public function testEngineDisabledUsesMapping(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);
        $this->createRule(1, 0, 2, [['country', 'in', ['FR']]]);

        $this->assertSame(1, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testAndGroupCountryAndDeliveryMode(): void
    {
        $this->createRule(1, 0, 2, [['country', 'in', ['FR']], ['delivery_mode', 'in', ['Colissimo']]]);

        $this->assertSame(2, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testOrGroupsSecondGroupAloneMatches(): void
    {
        $this->createRule(1, 0, 2, [['country', 'in', ['ES']]], [['marketplace', 'equals', 'amazon']]);

        $this->assertSame(2, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testCountryDistinguishesSameDeliveryMode(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['ES']], ['delivery_mode', 'in', ['Colissimo']]]);
        $this->createRule(1, 1, 2, [['country', 'in', ['FR']], ['delivery_mode', 'in', ['Colissimo']]]);

        $this->assertSame(2, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testFirstMatchingRuleWins(): void
    {
        $this->createRule(1, 1, 2, [['country', 'in', ['FR']]]);
        $this->createRule(1, 0, 5, [['marketplace', 'in', ['Amazon']]]);

        $this->assertSame(5, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testRuleOfAnotherTokenIsIgnored(): void
    {
        $this->createRule(2, 0, 5, [['country', 'in', ['FR']]]);

        $this->assertSame(self::DEFAULT_CARRIER_REFERENCE, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testNoMatchFallsBackToDefaultCarrier(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['ES']]]);

        $this->assertSame(self::DEFAULT_CARRIER_REFERENCE, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testRuleWithDeletedCarrierFallsThrough(): void
    {
        $this->createRule(1, 0, 999999, [['country', 'in', ['FR']]]);
        $this->createRule(1, 1, 2, [['country', 'in', ['FR']]]);

        $this->assertSame(2, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testWeightRule(): void
    {
        $this->setProductWeight(6);
        $this->createRule(1, 0, 5, [['weight', 'gt', 5]]);

        $this->assertSame(5, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testWeightRuleNotMetWhenLighter(): void
    {
        $this->setProductWeight(4);
        $this->createRule(1, 0, 5, [['weight', 'gt', 5]]);

        $this->assertSame(self::DEFAULT_CARRIER_REFERENCE, $this->carrierReferenceAfterVerifyOrder());
    }

    public function testUkOrderMatchesGbRule(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['GB']]]);
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json', ['shippingAddress' => ['country' => 'UK']]);
        $orderData = new OrderData($apiOrder);

        $match = (new CarrierRuleResolver(new ConditionEvaluator()))->resolve(
            1,
            new OrderCriteria('Amazon', $orderData->shippingAddress['country'], 'Colissimo', 0)
        );

        $this->assertNotNull($match);
        $this->assertSame(5, (int) $match['carrier']->id_reference);
        $this->assertSame(1, $match['number']);
    }

    public function testResolverReportsSkippedRulesAndRuleNumber(): void
    {
        $this->createRule(1, 0, 999999, [['country', 'in', ['FR']]]);
        $this->createRule(1, 1, 5, [['country', 'in', ['ES']]]);
        $this->createRule(1, 2, 2, [['country', 'in', ['FR']]]);
        $resolver = new CarrierRuleResolver(new ConditionEvaluator());

        $match = $resolver->resolve(1, new OrderCriteria('Amazon', 'FR', 'Colissimo', 0));

        $this->assertSame(3, $match['number']);
        $this->assertSame(2, (int) $match['rule']->id_carrier_reference);
        $this->assertCount(1, $resolver->getSkippedRules());
        $this->assertSame(999999, (int) $resolver->getSkippedRules()[0]->id_carrier_reference);
    }

    public function testFullImportUsesRuleCarrier(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['FR']], ['marketplace', 'equals', 'Amazon']]);
        $apiOrder = $this->getOrderRessourceFromDataset(
            'order-amazon.json',
            ['id' => $this->uniqueOrderId(), 'reference' => 'TEST-34133-CARRIER-RULE']
        );

        $handler = $this->createHandler($apiOrder, [
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart',
            'validateOrder',
            // no postProcess: its TestingOrder / split-order clean up is unrelated
            // to carrier selection and intermittently fails on a deleted order
        ]);

        $this->assertTrue($handler->process('shoppingfeedOrderImport'), (string) ($handler->getConveyor()['error'] ?? ''));
        $idOrder = (int) $handler->getConveyor()['id_order'];
        $psOrder = new \Order($idOrder);
        // OrderSyncStatusTest counts every task order: don't leave ours behind
        \Db::getInstance()->delete('shoppingfeed_task_order', 'id_order = ' . $idOrder);

        $this->assertSame(5, (int) (new \Carrier($psOrder->id_carrier))->id_reference);
    }

    /**
     * Runs the import chain up to the carrier resolution
     */
    private function carrierReferenceAfterVerifyOrder()
    {
        $handler = $this->createHandler(
            $this->getOrderRessourceFromDataset('order-amazon.json', ['id' => $this->uniqueOrderId()]),
            ['registerSpecificRules', 'verifyOrder']
        );
        $this->assertTrue($handler->process('shoppingfeedOrderImport'));

        return (int) $handler->getConveyor()['carrier']->id_reference;
    }

    /**
     * An order id never imported before, so the order is not skipped as "already present"
     */
    private function uniqueOrderId()
    {
        do {
            $id = 7000000000 + mt_rand(1, 999999999);
        } while (\ShoppingfeedOrder::existsInternalId($id));

        return $id;
    }

    private function setProductWeight($weight)
    {
        \Db::getInstance()->update('product', ['weight' => $weight], 'id_product = 8');
        // Product objects are cached for the whole PHPUnit process
        \Cache::clean('objectmodel_Product_*');
    }

    private function createHandler($apiOrder, array $actions)
    {
        $handler = new ActionsHandler();
        $handler->addActions(...$actions);
        $handler->setConveyor([
            'id_shop' => 1,
            'id_token' => 1,
            'id_lang' => 1,
            'apiOrder' => $apiOrder,
        ]);
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        return $handler;
    }

    /**
     * Each group is a list of [criterion, operator, value] triples
     */
    private function createRule($idToken, $position, $idCarrierReference, array ...$groups)
    {
        $rule = new \ShoppingfeedCarrierRule();
        $rule->id_shoppingfeed_token = $idToken;
        $rule->position = $position;
        $rule->id_carrier_reference = $idCarrierReference;
        $rule->setConditions(['groups' => array_map(
            function ($group) {
                return ['conditions' => array_map(
                    function ($condition) {
                        return ['criterion' => $condition[0], 'operator' => $condition[1], 'value' => $condition[2]];
                    },
                    $group
                )];
            },
            $groups
        )]);
        $this->assertTrue((bool) $rule->add());

        return $rule;
    }
}
