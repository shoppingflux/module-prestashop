<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Tests\OrderImport;

use ColissimoCartPickupPoint;
use Order;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedCarrier;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules AmazonEbay Test
 */
class OrderImportSimpleTest extends AbstractOrdeTestCase
{
    /**
     * Test to import a standard order Amazon
     *
     * @return void
     */
    public function testImportAmazon(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);
        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        $conveyor = $handler->getConveyor();

        $customer = $conveyor['customer'];
        $this->assertEquals($customer->firstname, 'Martin');
        $this->assertEquals($customer->lastname, 'Bernard');

        $sfOrder = $conveyor['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'Amazon');
        $this->assertEquals($sfOrder->id_order_marketplace, 'TEST-ORDER-AMAZON');

        $psOrder = new \Order((int) $conveyor['id_order']);
        // it's a test > canceled
        $this->assertEquals($psOrder->current_state, _PS_OS_CANCELED_);
        $this->assertEquals($psOrder->payment, 'amazon');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 9.400000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 9.400000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 7.830000);
        $this->assertEquals($psOrder->total_products, 3.750000);
        $this->assertEquals($psOrder->total_shipping, 4.900000);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);
        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_excl, 7.830000);
            $this->assertEquals($invoice->total_paid_tax_incl, 9.400000);
            $this->assertEquals($invoice->total_products, 3.750000);
            $this->assertEquals($invoice->total_products_wt, 4.500000);
            $this->assertEquals($invoice->total_shipping_tax_excl, 4.080000);
            $this->assertEquals($invoice->total_shipping_tax_incl, 4.900000);
            $this->assertEquals($invoice->shipping_tax_computation_method, 0);
            $this->assertEquals($invoice->total_wrapping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_wrapping_tax_incl, 0.000000);
        }

        $carrier = new \Carrier($psOrder->id_carrier);
        $this->assertEquals($carrier->id_reference, 1);
    }

    /**
     * Test to import a standard order a second time the same
     *
     * @depends testImportAmazon
     *
     * @return void
     */
    public function testImportAmazonSecondTime(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        // expected not imported a second time !
        $this->assertFalse($processResult);

        $conveyor = $handler->getConveyor();
        $this->assertEquals($conveyor['error'], 'Order not imported; already present.');
    }

    /**
     * Test to import a standard order Nature & Decouverte
     *
     * @return void
     */
    public function testImportNatureDecouverte(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-naturedecouverte.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        $conveyor = $handler->getConveyor();
        $psOrder = $conveyor['psOrder'];
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'natureetdecouvertes');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 9.400000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 9.400000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 7.830000);
        $this->assertEquals($psOrder->total_products, 3.750000);
        $this->assertEquals($psOrder->total_shipping, 4.900000);

        $carrier = new \Carrier($psOrder->id_carrier);
        $this->assertEquals($carrier->id_reference, 2);
    }

    /**
     * Test to import a standard order Zalando
     *
     * @return void
     */
    public function testImportZalando(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-zalando.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        $conveyor = $handler->getConveyor();

        $customer = $conveyor['customer'];
        $this->assertEquals($customer->lastname, 'PETIT-JEAN');
        $this->assertEquals($customer->firstname, 'Corine');

        $sfOrder = $conveyor['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'zalandomyunittest');
        $this->assertEquals($sfOrder->id_order_marketplace, '10301108385651');

        $psOrder = new \Order((int) $conveyor['id_order']);

        // it's a test > canceled
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'zalandomyunittest');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 119.800000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 119.800000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 99.833300);
        $this->assertEquals($psOrder->total_paid_real, 119.800000);
        $this->assertEquals($psOrder->total_products, 99.830000);
        $this->assertEquals($psOrder->total_products_wt, 119.800000);
        $this->assertEquals($psOrder->total_shipping, 0.000000);
        $this->assertEquals($psOrder->carrier_tax_rate, 20.000);
        $this->assertEquals($psOrder->id_carrier, 1);
        $this->assertEquals($psOrder->total_wrapping, 0.000000);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);
        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_excl, 99.833300);
            $this->assertEquals($invoice->total_paid_tax_incl, 119.800000);
            $this->assertEquals($invoice->total_products, 99.830000);
            $this->assertEquals($invoice->total_products_wt, 119.800000);
            $this->assertEquals($invoice->total_shipping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_shipping_tax_incl, 0.000000);
            $this->assertEquals($invoice->shipping_tax_computation_method, 0);
            $this->assertEquals($invoice->total_wrapping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_wrapping_tax_incl, 0.000000);
        }

        $carrier = new \Carrier($psOrder->id_carrier);
        $this->assertEquals($carrier->id_reference, 1);

        $this->assertArrayHasKey('cart', $conveyor);
        $this->assertNotNull($conveyor['cart']->id);
        $idColissimoPickupPoint = ColissimoCartPickupPoint::getByCartId($conveyor['cart']->id);
        $pickupPoint = new \ColissimoPickupPoint((int) $idColissimoPickupPoint);

        $this->assertEquals($pickupPoint->colissimo_id, '100562');
        $this->assertEquals($pickupPoint->company_name, 'PRIMAVERA');
        $this->assertEquals($pickupPoint->product_code, 'BPR');
        $this->assertEquals($pickupPoint->city, 'MARSEILLE');
        $sfCarrier = ShoppingfeedCarrier::getByMarketplaceAndName(
                        'zalandomyunittest',
                        'pickup'
                    );
        $this->assertNotEquals($sfCarrier, false);
    }

    public function testImportColizey(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-colizey-colissimorelais.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);
        $conveyor = $handler->getConveyor();

        $customer = $conveyor['customer'];
        $this->assertEquals($customer->lastname, 'Taquin');
        $this->assertEquals($customer->firstname, 'Godfroy');

        $sfOrder = $conveyor['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'Colizey');
        $this->assertEquals($sfOrder->id_order_marketplace, '216e3d99-3c73-4d1b-a178-110e0b0369f6');

        $psOrder = new \Order((int) $conveyor['id_order']);

        // it's a test > canceled
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'colizey');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 89.000000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 89.000000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 74.166700);
        $this->assertEquals($psOrder->total_paid_real, 89.000000);
        $this->assertEquals($psOrder->total_products, 74.170000);
        $this->assertEquals($psOrder->total_products_wt, 89.000000);
        $this->assertEquals($psOrder->total_shipping, 0.000000);
        $this->assertEquals($psOrder->carrier_tax_rate, 0.000);
        $this->assertEquals($psOrder->id_carrier, 7);
        $this->assertEquals($psOrder->total_wrapping, 0.000000);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);
        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_excl, 74.166700);
            $this->assertEquals($invoice->total_paid_tax_incl, 89.000000);
            $this->assertEquals($invoice->total_products, 74.170000);
            $this->assertEquals($invoice->total_products_wt, 89.000000);
            $this->assertEquals($invoice->total_shipping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_shipping_tax_incl, 0.000000);
            $this->assertEquals($invoice->shipping_tax_computation_method, 0);
            $this->assertEquals($invoice->total_wrapping_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_wrapping_tax_incl, 0.000000);
        }

        $carrier = new \Carrier($psOrder->id_carrier);
        $this->assertEquals($carrier->id_reference, 7);

        $this->assertArrayHasKey('cart', $conveyor);
        $this->assertNotNull($conveyor['cart']->id);
        $this->assertNotEquals(ColissimoCartPickupPoint::getByCartId($conveyor['cart']->id), 0);

        $idColissimoPickupPoint = ColissimoCartPickupPoint::getByCartId($conveyor['cart']->id);
        $pickupPoint = new \ColissimoPickupPoint((int) $idColissimoPickupPoint);
        $this->assertEquals($pickupPoint->colissimo_id, '060559');
        $this->assertEquals($pickupPoint->company_name, 'BUREAU DE POSTE COLAYRAC LPRC RP');
        $this->assertEquals($pickupPoint->product_code, 'A2P');
        $this->assertEquals($pickupPoint->city, 'COLAYRAC ST CIRQ');
    }
}
