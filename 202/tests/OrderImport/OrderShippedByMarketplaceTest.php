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

use Order;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\OrderImport\Rules\ShippedByMarketplace;
use ShoppingfeedClasslib\Registry;
use StockAvailable;

class OrderShippedByMarketplaceTest extends AbstractOrdeTestCase
{
    public function testOrderSkipping()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-shipped-afn.json');
        $rule = new ShippedByMarketplace([
            \Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE => false,
        ]);

        $this->assertTrue($rule->isApplicable($apiOrder));

        $params = [
            'isSkipImport' => false,
            'apiOrder' => $apiOrder,
        ];
        $rule->onPreProcess($params);
        $this->assertTrue($params['isSkipImport']);
    }

    public function testImportAfn()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-shipped-afn.json');
        $stockBeforeImport = StockAvailable::getQuantityAvailableByProduct(8);

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
        $stockAfterImport = StockAvailable::getQuantityAvailableByProduct(8);

        $conveyor = $handler->getConveyor();

        $psOrder = new Order((int) $conveyor['id_order']);

        $this->assertEquals(5, $psOrder->current_state);
        $this->assertEquals($stockBeforeImport, $stockAfterImport);
    }

    public function testImportClogistique()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-shipped-clogistique.json');
        $stockBeforeImport = StockAvailable::getQuantityAvailableByProduct(8);

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
        $stockAfterImport = StockAvailable::getQuantityAvailableByProduct(8);

        $conveyor = $handler->getConveyor();

        $psOrder = new Order((int) $conveyor['id_order']);

        $this->assertEquals(5, $psOrder->current_state);
        $this->assertEquals($stockBeforeImport, $stockAfterImport);
    }

    public function testImportEpmm()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-shipped-epmm.json');
        $stockBeforeImport = StockAvailable::getQuantityAvailableByProduct(8);

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
        $stockAfterImport = StockAvailable::getQuantityAvailableByProduct(8);

        $conveyor = $handler->getConveyor();

        $psOrder = new Order((int) $conveyor['id_order']);

        $this->assertEquals(5, $psOrder->current_state);
        $this->assertEquals($stockBeforeImport, $stockAfterImport);
    }

    public function testImportFulfilledChannel()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-shipped-fulfilledByChannel.json');
        $stockBeforeImport = StockAvailable::getQuantityAvailableByProduct(8);

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
        $stockAfterImport = StockAvailable::getQuantityAvailableByProduct(8);

        $conveyor = $handler->getConveyor();

        $psOrder = new Order((int) $conveyor['id_order']);

        $this->assertEquals(5, $psOrder->current_state);
        $this->assertEquals($stockBeforeImport, $stockAfterImport);
    }
}
