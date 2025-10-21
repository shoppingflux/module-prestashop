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
    public function testImportMondialRelais()
    {
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
        $this->assertEquals($carrier->id_reference, 1);
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
     * @depends testIsCartValid
     *
     * @return void
     */
    public function testDataMondialrelay($cart)
    {
        $mrsr = \MondialrelaySelectedRelay::getFromIdCart($cart->id);

        $this->assertTrue(\Validate::isLoadedObject($mrsr));
        $this->assertEquals($mrsr->id_mondialrelay_carrier_method, 1);
        $this->assertEquals($mrsr->selected_relay_num, '012082');
        $this->assertEquals($mrsr->selected_relay_adr1, 'CARREFOUR EXPRESS');
        $this->assertEquals($mrsr->selected_relay_adr3, 'ESPL. SAINTE-GERMAINE');
        $this->assertEquals($mrsr->selected_relay_postcode, '31820');
        $this->assertEquals($mrsr->selected_relay_city, 'PIBRAC');
        $this->assertEquals($mrsr->selected_relay_country_iso, 'FR');
    }
}
