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

use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\OrderImport\Rules\ColissimoRule;
use ShoppingfeedClasslib\Registry;

class OrderRulesColissimoTest extends AbstractOrdeTestCase
{
    public function testColissimo(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-manomano-colissimo.json');
        $rule = new ColissimoRule();
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
        $address = new \Address($conveyor['cart']->id_address_delivery);
        $pickupPoint = \ColissimoPickupPoint::getPickupPointByIdColissimo('p31175');
        $this->assertEquals($address->company, $pickupPoint->company_name);
        $this->assertEquals($address->address1, $pickupPoint->address1);
        $this->assertEquals($address->address2, $pickupPoint->address2);
        $this->assertEquals($address->city, $pickupPoint->city);
        $this->assertEquals($address->postcode, $pickupPoint->zipcode);
    }

    public function testShowroompriveColissimo()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-showroomprive-colissimo.json');
        $rule = new ColissimoRule();
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
        $address = new \Address($conveyor['cart']->id_address_delivery);
        $pickupPoint = \ColissimoPickupPoint::getPickupPointByIdColissimo('488411');
        $this->assertEquals($address->company, $pickupPoint->company_name);
        $this->assertEquals($address->address1, $pickupPoint->address1);
        $this->assertEquals($address->address2, $pickupPoint->address2);
        $this->assertEquals($address->city, $pickupPoint->city);
        $this->assertEquals($address->postcode, $pickupPoint->zipcode);
    }

    public function testVeepeegroupColissimo()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-veepeegroup-colissimo.json');
        $rule = new ColissimoRule();
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
        $address = new \Address($conveyor['cart']->id_address_delivery);
        $pickupPoint = \ColissimoPickupPoint::getPickupPointByIdColissimo('488411');
        $this->assertEquals($address->company, $pickupPoint->company_name);
        $this->assertEquals($address->address1, $pickupPoint->address1);
        $this->assertEquals($address->address2, $pickupPoint->address2);
        $this->assertEquals($address->city, $pickupPoint->city);
        $this->assertEquals($address->postcode, $pickupPoint->zipcode);
    }

    public function testBhvColissimo()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-bhv-colissimo.json');
        $rule = new ColissimoRule();
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
        $address = new \Address($conveyor['cart']->id_address_delivery);
        $pickupPoint = \ColissimoPickupPoint::getPickupPointByIdColissimo('488411');
        $this->assertEquals($address->company, $pickupPoint->company_name);
        $this->assertEquals($address->address1, $pickupPoint->address1);
        $this->assertEquals($address->address2, $pickupPoint->address2);
        $this->assertEquals($address->city, $pickupPoint->city);
        $this->assertEquals($address->postcode, $pickupPoint->zipcode);
    }
}
