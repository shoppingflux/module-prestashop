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
use ShoppingfeedAddon\OrderImport\Rules\AmazonEbay;
use ShoppingfeedAddon\OrderImport\Rules\AmazonManomanoTva;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules AmazonEbay Test
 */
class OrderRulesAmazonManomanoTvaTest extends AbstractOrdeTestCase
{
    public function testAmazon(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-with-tva-num.json');
        $rule = new AmazonManomanoTva();
        $this->assertTrue($rule->isApplicable($apiOrder));

        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart'
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
        $handler->process('shoppingfeedOrderImport');
        $conveyor = $handler->getConveyor();
        $billingAddress = new \Address($conveyor['id_billing_address']);
        $shippingAddress = new \Address($conveyor['id_shipping_address']);

        $this->assertNotEmpty($billingAddress->vat_number);
        $this->assertNotEmpty($shippingAddress->vat_number);
    }

    public function testManomano(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-manomano-with-tva-num.json');
        $rule = new AmazonManomanoTva();
        $this->assertTrue($rule->isApplicable($apiOrder));

        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart'
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
        $handler->process('shoppingfeedOrderImport');
        $conveyor = $handler->getConveyor();
        $billingAddress = new \Address($conveyor['id_billing_address']);
        $shippingAddress = new \Address($conveyor['id_shipping_address']);

        $this->assertNotEmpty($billingAddress->vat_number);
        $this->assertNotEmpty($shippingAddress->vat_number);
    }
}
