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

use ShoppingfeedClasslib\Actions\ActionsHandler;

/**
 * Order Rules AmazonEbay Test
 */
class OrderImportSimpleTest extends AbstractOrdeTestCase
{
    /**
     * Test to import a standard order
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
            'acknowledgeOrder',
            'recalculateOrderPrices',
            'postProcess'
        );

        $handler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'apiOrder' => $apiOrder,
            ]
        );
        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        $conveyor = $handler->getConveyor();

        $customer = $conveyor['customer'];
        $this->assertEquals($customer->lastname, 'Martin');
        $this->assertEquals($customer->firstname, 'Bernard');

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
    }

    /**
     * Test to import a standard order
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
            'acknowledgeOrder',
            'recalculateOrderPrices',
            'postProcess'
        );

        $handler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'apiOrder' => $apiOrder,
            ]
        );

        $processResult = $handler->process('shoppingfeedOrderImport');

        // expected not imported a second time !
        $this->assertFalse($processResult);

        $conveyor = $handler->getConveyor();
        $this->assertEquals($conveyor['error'], 'Order not imported; already present.');
    }
    /**
     * Test to import a standard order
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
            'acknowledgeOrder',
            'recalculateOrderPrices',
            'postProcess'
        );

        $handler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'apiOrder' => $apiOrder,
            ]
        );


        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);
    }
}
