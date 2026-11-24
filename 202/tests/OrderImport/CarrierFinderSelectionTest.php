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

use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\Services\CarrierFinder;

/**
 * CarrierFinder::selectCarrierForOrderImport(): the single entry point that
 * applies the mapping or the carrier selection rules depending on the switch (#34133).
 *
 * Dataset order-amazon.json: Amazon / FR / Colissimo, item "8" x 1.
 * Fixture mapping Amazon + Colissimo => carrier reference 1.
 */
class CarrierFinderSelectionTest extends AbstractOrdeTestCase
{
    use CarrierRuleFixturesTrait;

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

    public function testEngineOffUsesMapping(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);
        $this->createRule(1, 0, 5, [['country', 'in', ['FR']]]);

        $selection = $this->select();

        $this->assertSame(1, (int) $selection->getCarrier()->id_reference);
        $this->assertFalse($selection->isRulesEngine());
        $this->assertNull($selection->getRuleNumber());
    }

    public function testEngineOnUsesFirstMatchingRule(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['ES']]]);
        $this->createRule(1, 1, 2, [['country', 'in', ['FR']]]);

        $selection = $this->select();

        $this->assertSame(2, (int) $selection->getCarrier()->id_reference);
        $this->assertTrue($selection->isRulesEngine());
        $this->assertSame(2, $selection->getRuleNumber());
    }

    public function testEngineOnIgnoresMappingWhenNoRuleMatches(): void
    {
        $this->createRule(1, 0, 5, [['country', 'in', ['ES']]]);

        $selection = $this->select();

        // the mapping would give 1: the rules engine goes straight to the default carrier
        $this->assertSame(self::DEFAULT_CARRIER_REFERENCE, (int) $selection->getCarrier()->id_reference);
        $this->assertTrue($selection->isRulesEngine());
        $this->assertNull($selection->getRuleNumber());
    }

    public function testEngineOnReportsSkippedRules(): void
    {
        $this->createRule(1, 0, 999999, [['country', 'in', ['FR']]]);
        $this->createRule(1, 1, 5, [['country', 'in', ['FR']]]);

        $selection = $this->select();

        $this->assertSame(5, (int) $selection->getCarrier()->id_reference);
        $this->assertSame(2, $selection->getRuleNumber());
        $this->assertCount(1, $selection->getSkippedRules());
        $this->assertSame(999999, (int) $selection->getSkippedRules()[0]->id_carrier_reference);
    }

    public function testWeightIsComputedFromMappedProducts(): void
    {
        $this->setProductWeight(6);
        $this->createRule(1, 0, 5, [['weight', 'gt', 5]]);

        $selection = $this->select(['8' => new \Product(8, true, null, 1)]);

        $this->assertSame(5, (int) $selection->getCarrier()->id_reference);
    }

    private function select(array $prestashopProducts = [])
    {
        $orderData = new OrderData($this->getOrderRessourceFromDataset('order-amazon.json'));

        return (new CarrierFinder())->selectCarrierForOrderImport($orderData, 1, 'Amazon', 'Colissimo', $prestashopProducts);
    }
}
