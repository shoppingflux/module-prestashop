<?php

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
