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
use ShoppingfeedAddon\OrderImport\Rules\MondialrelayRule;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules MondialRelais Test
 */
class OrderImportMondialRelaisTest extends AbstractOrdeTestCase
{
    use CarrierRuleFixturesTrait;

    public const MONDIALRELAY_MODULE_NAME = 'mondialrelay';

    /** @var int Carrier of the mondialrelay module created for this test case */
    private static $idCarrier = 0;

    /** @var int */
    private static $idCarrierReference = 0;

    /** @var int */
    private static $idCarrierMethod = 0;

    public static function tearDownAfterClass(): void
    {
        if (self::$idCarrier) {
            \Db::getInstance()->delete('shoppingfeed_carrier', 'name_marketplace = "Colizey" AND name_carrier = "Mondial Relay"');
            \Db::getInstance()->delete('mondialrelay_carrier_method', 'id_mondialrelay_carrier_method = ' . (int) self::$idCarrierMethod);
            (new \Carrier(self::$idCarrier))->delete();
            self::$idCarrier = self::$idCarrierReference = self::$idCarrierMethod = 0;
        }
    }

    public function testImportMondialRelais()
    {
        $this->createMondialRelayCarrier();
        $apiOrder = $this->getOrderRessourceFromDataset('order-colizey-mondialrelais.json');
        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart',
            'validateOrder',
            'postProcess'
        );

        $handler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'id_lang' => 1,
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);
        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        return $handler->getConveyor();
    }

    public function testRulesMondialRelais()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-colizey-mondialrelais.json');
        $rules = new MondialrelayRule();
        $this->assertTrue($rules->isApplicable($apiOrder));
    }

    /**
     * @depends testImportMondialRelais
     */
    public function testIsOrderValid($onveyor)
    {
        // dump(array_keys($onveyor));
        $psOrder = new \Order((int) $onveyor['id_order']);

        $this->assertTrue(\Validate::isLoadedObject($psOrder));

        return $psOrder;
    }

    /**
     * @depends testIsOrderValid
     */
    public function testIsCartValid($psOrder)
    {
        $cart = new \cart($psOrder->id_cart);

        $this->assertTrue(\Validate::isLoadedObject($cart));

        return $cart;
    }

    /**
     * @depends testImportMondialRelais
     */
    public function testIsCustomerValid($onveyor)
    {
        $customer = new \Customer((int) $onveyor['customer']->id);

        $this->assertTrue(\Validate::isLoadedObject($customer));

        return $customer;
    }

    /**
     * @depends testImportMondialRelais
     */
    public function testIsCarrierValid($onveyor)
    {
        $carrier = new \Carrier((int) $onveyor['carrier']->id);

        $this->assertTrue(\Validate::isLoadedObject($carrier));

        return $carrier;
    }

    /**
     * @depends testImportMondialRelais
     */
    public function testIsSfOrderValid($onveyor)
    {
        $sfOrder = new \ShoppingfeedOrder((int) $onveyor['sfOrder']->id);

        $this->assertTrue(\Validate::isLoadedObject($sfOrder));

        return $sfOrder;
    }

    /**
     * @depends testIsOrderValid
     */
    public function testDataOrder($psOrder)
    {
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'colizey');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 67.000000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 67.000000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 55.830000);
        $this->assertEquals($psOrder->total_products, 52.500000);
        $this->assertEquals($psOrder->total_shipping, 4.000000);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);
        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_excl, 55.830000);
            $this->assertEquals($invoice->total_paid_tax_incl, 67.000000);
            $this->assertEquals($invoice->total_products, 52.500000);
            $this->assertEquals($invoice->total_products_wt, 63.000000);
            $this->assertEquals($invoice->total_shipping_tax_excl, 3.330000);
            $this->assertEquals($invoice->total_shipping_tax_incl, 4.000000);
            $this->assertEquals($invoice->shipping_tax_computation_method, 0);
            $this->assertEquals($invoice->total_wrapping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_wrapping_tax_incl, 0.000000);
        }
    }

    /**
     * @depends testIsCustomerValid
     */
    public function testDataCustomer($customer)
    {
        $this->assertEquals($customer->lastname, 'ROULAI');
        $this->assertEquals($customer->firstname, 'STEPHANE');
    }

    /**
     * @depends testIsCarrierValid
     */
    public function testDataCarrier($carrier)
    {
        $this->assertEquals(self::$idCarrierReference, $carrier->id_reference);
        $this->assertSame(self::MONDIALRELAY_MODULE_NAME, $carrier->external_module_name);
    }

    /**
     * @depends testIsSfOrderValid
     */
    public function testDataSfOrder($sfOrder)
    {
        $this->assertEquals($sfOrder->name_marketplace, 'Colizey');
        $this->assertEquals($sfOrder->id_order_marketplace, 'fb4d807c-f248-49c7-ac23-e8bc70c9a8d7');
    }

    /**
     * The relay details come from the Mondial Relay webservice: it is replaced by
     * a stub so the test does not depend on the network nor on a live account.
     *
     * @depends testIsCartValid
     * @depends testImportMondialRelais
     *
     * @return void
     */
    public function testDataMondialrelay($cart, $conveyor)
    {
        // the import itself called the real webservice: start from a clean state
        \Db::getInstance()->delete('mondialrelay_selected_relay', 'id_cart = ' . (int) $cart->id);
        $soapClient = $this->createRelaySoapClientStub();
        $rule = new class extends MondialrelayRule {
            public $soapClient;

            protected function initSoapClient()
            {
                return $this->soapClient;
            }
        };
        $rule->soapClient = $soapClient;
        $this->assertTrue($rule->isApplicable($conveyor['apiOrder']));

        $rule->afterCartCreation(['apiOrder' => $conveyor['apiOrder'], 'cart' => $cart]);

        $this->assertSame('012082', $soapClient->requests[0]['Num']);
        $this->assertSame('FR', $soapClient->requests[0]['Pays']);
        $mrsr = \MondialrelaySelectedRelay::getFromIdCart($cart->id);
        $this->assertTrue(\Validate::isLoadedObject($mrsr));
        $this->assertEquals($mrsr->id_mondialrelay_carrier_method, self::$idCarrierMethod);
        $this->assertEquals($mrsr->selected_relay_num, '012082');
        $this->assertEquals($mrsr->selected_relay_adr1, 'CARREFOUR EXPRESS');
        $this->assertEquals($mrsr->selected_relay_adr3, 'ESPL. SAINTE-GERMAINE');
        $this->assertEquals($mrsr->selected_relay_postcode, '31820');
        $this->assertEquals($mrsr->selected_relay_city, 'PIBRAC');
        $this->assertEquals($mrsr->selected_relay_country_iso, 'FR');
    }

    /**
     * A carrier owned by the mondialrelay module with its carrier method, and the
     * mapping of the dataset's Colizey / "Mondial Relay" to it
     */
    private function createMondialRelayCarrier()
    {
        $carrier = $this->createModuleCarrier(self::MONDIALRELAY_MODULE_NAME);
        self::$idCarrier = (int) $carrier->id;
        self::$idCarrierReference = (int) $carrier->id_reference;
        \Db::getInstance()->insert('carrier_zone', ['id_carrier' => self::$idCarrier, 'id_zone' => 9], false, true, \Db::REPLACE);
        // shipping taxed like the shop's carriers (the order totals below include shipping tax)
        $carrier->setTaxRulesGroup((int) \Carrier::getIdTaxRulesGroupByIdCarrier(1));
        \Db::getInstance()->insert('mondialrelay_carrier_method', [
            'id_carrier' => self::$idCarrier,
            'id_reference' => self::$idCarrierReference,
            'delivery_mode' => '24R',
            'delivery_type' => 'MR',
            'insurance_level' => '0',
            'is_deleted' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ]);
        self::$idCarrierMethod = (int) \Db::getInstance()->Insert_ID();
        \Db::getInstance()->insert('shoppingfeed_carrier', [
            'name_marketplace' => 'Colizey',
            'name_carrier' => 'Mondial Relay',
            'id_carrier_reference' => self::$idCarrierReference,
            'is_new' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ], false, true, \Db::REPLACE);
    }

    /**
     * Answers WSI2_AdressePointRelais like the Mondial Relay webservice does for relay 012082
     */
    private function createRelaySoapClientStub()
    {
        return new class {
            public $soap_defencoding;

            public $decode_utf8;

            public $requests = [];

            public function WSI2_AdressePointRelais(array $params)
            {
                $this->requests[] = $params;

                return (object) ['WSI2_AdressePointRelaisResult' => (object) [
                    'STAT' => '0',
                    'LgAdr1' => 'CARREFOUR EXPRESS',
                    'LgAdr2' => '',
                    'LgAdr3' => 'ESPL. SAINTE-GERMAINE',
                    'LgAdr4' => '',
                    'CP' => '31820',
                    'Ville' => 'PIBRAC',
                ]];
            }
        };
    }
}
