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

/**
 * Specific rules that only apply to their own carrier (ColissimoRule,
 * ManomanoDpdRelais) must follow the carrier actually selected by
 * verifyOrder(), mapping or carrier selection rules (#34133).
 *
 * Dataset order-showroomprive-colissimo.json: showroomprive / Colissimo, relayId 488411,
 * fixture mapping => carrier reference 7 (colissimo module).
 * Dataset order-manomanopro.json: Manomanopro / "DPD Pickup" (no mapping), relay P30911.
 */
class CarrierDependentRulesTest extends AbstractOrdeTestCase
{
    use CarrierRuleFixturesTrait;

    public const COLISSIMO_CARRIER_REFERENCE = 7;

    public const UNMAPPED_CARRIER_NAME = 'TEST-34133 unmapped carrier';

    /** @var \Carrier|null */
    private $dpdCarrier;

    protected function setUp(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 1);
    }

    protected function tearDown(): void
    {
        \ShoppingfeedCarrierRule::deleteAll();
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);
        if ($this->dpdCarrier) {
            $this->dpdCarrier->delete();
            $this->dpdCarrier = null;
        }
        // verifyOrder() auto-creates a mapping for unknown (marketplace, carrier) pairs
        \Db::getInstance()->delete('shoppingfeed_carrier', 'name_carrier IN ("' . pSQL(self::UNMAPPED_CARRIER_NAME) . '", "DPD Pickup")');
    }

    public function testColissimoRuleAppliesWhenCarrierRuleSelectsColissimo(): void
    {
        // the mapping does not know this carrier name: only the carrier rule gives Colissimo
        $this->createRule(1, 0, self::COLISSIMO_CARRIER_REFERENCE, [['marketplace', 'equals', 'showroomprive']]);

        $orderData = $this->verifyOrder('order-showroomprive-colissimo.json', ['shipment' => ['carrier' => self::UNMAPPED_CARRIER_NAME]]);

        $this->assertSame('488411', $orderData->shippingAddress['other']);
    }

    public function testColissimoRuleSkippedWhenCarrierRuleSelectsAnotherCarrier(): void
    {
        // the mapping gives Colissimo, the carrier rule gives "My carrier"
        $this->createRule(1, 0, 2, [['marketplace', 'equals', 'showroomprive']]);

        $orderData = $this->verifyOrder('order-showroomprive-colissimo.json');

        $this->assertSame('', $orderData->shippingAddress['other']);
    }

    public function testColissimoRuleAppliesWithMappingWhenEngineOff(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);

        $orderData = $this->verifyOrder('order-showroomprive-colissimo.json');

        $this->assertSame('488411', $orderData->shippingAddress['other']);
    }

    public function testDpdRuleAppliesWhenCarrierRuleSelectsDpd(): void
    {
        $this->dpdCarrier = $this->createModuleCarrier(\ShoppingfeedAddon\OrderImport\Rules\ManomanoDpdRelais::MODULE_NAME);
        $this->createRule(1, 0, (int) $this->dpdCarrier->id_reference, [['marketplace', 'equals', 'Manomanopro']]);

        $orderData = $this->verifyOrder('order-manomanopro.json');

        $this->assertSame('LE CORNER (P30911)', $orderData->shippingAddress['company']);
        $this->assertSame('P30911', $orderData->shippingAddress['other']);
    }

    public function testDpdRuleSkippedWhenCarrierRuleSelectsAnotherCarrier(): void
    {
        $this->createRule(1, 0, 2, [['marketplace', 'equals', 'Manomanopro']]);

        $orderData = $this->verifyOrder('order-manomanopro.json');

        $this->assertSame('', $orderData->shippingAddress['company']);
    }

    public function testDpdRuleSkippedWhenEngineOffAndCarrierIsNotDpd(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::ORDER_CARRIER_ADVANCED_RULES, 0);

        // no mapping for Manomanopro / "DPD Pickup": the default carrier is not a DPD one
        $orderData = $this->verifyOrder('order-manomanopro.json');

        $this->assertSame('', $orderData->shippingAddress['company']);
    }

    /**
     * Runs the import chain up to verifyOrder() and returns the order data the next steps use
     */
    private function verifyOrder($dataset, array $overrides = [])
    {
        $apiOrder = $this->getOrderRessourceFromDataset($dataset, ['id' => $this->uniqueOrderId()] + $overrides);
        $handler = $this->createHandler($apiOrder, ['registerSpecificRules', 'verifyOrder']);
        $this->assertTrue($handler->process('shoppingfeedOrderImport'), (string) ($handler->getConveyor()['error'] ?? ''));

        return $handler->getConveyor()['orderData'];
    }
}
