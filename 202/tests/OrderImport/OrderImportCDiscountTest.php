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
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules CDiscount Test
 */
class OrderImportCDiscountTest extends AbstractOrdeTestCase
{
    /**
     * Test to import a standard order CDiscount
     *
     * @return void
     */
    public function testImportCDiscountWithRelais(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-cdiscount-colissimorelais.json');

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
        $this->assertEquals($customer->firstname, 'CLAUDIA');
        $this->assertEquals($customer->lastname, 'GIRARD');

        $sfOrder = $conveyor['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'CDiscount');
        $this->assertEquals($sfOrder->id_order_marketplace, 'TEST-70625LDVJE');

        $psOrder = new \Order((int) $conveyor['id_order']);
        // it's a test > canceled
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'cdiscount');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 40.750000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 40.750000);
        $this->assertEquals($psOrder->total_paid_tax_excl, 34.116700);
        $this->assertEquals($psOrder->total_products, 34.120000);
        $this->assertEquals($psOrder->total_shipping, 0.000000);

        $products = $psOrder->getProducts();
        // two proct one feed + CDiscount fees
        $this->assertEquals(count($products), 3);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);
        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_excl, 0.000000);
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_excl, 34.116700);
            $this->assertEquals($invoice->total_paid_tax_incl, 40.750000);
            $this->assertEquals($invoice->total_products, 34.120000);
            $this->assertEquals($invoice->total_products_wt, 40.750000);
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
        $idColissimoPickupPoint = ColissimoCartPickupPoint::getByCartId($conveyor['cart']->id);
        $pickupPoint = new \ColissimoPickupPoint((int) $idColissimoPickupPoint);

        $this->assertEquals($pickupPoint->colissimo_id, '096772');
        $this->assertEquals($pickupPoint->company_name, 'Relais Pickup L ATELIER BIS');
        $this->assertEquals($pickupPoint->product_code, 'A2P');
        $this->assertEquals($pickupPoint->city, 'DUCEY');
    }
}
