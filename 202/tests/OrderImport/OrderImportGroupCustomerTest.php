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

use Order;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\OrderImport\Rules\GroupCustomer;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules AmazonEbay Test
 */
class OrderImportGroupCustomerTest extends AbstractOrdeTestCase
{
    public function testWithoutConfigImportGroupCustomer()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');
        $rules = new GroupCustomer();
        $this->assertFalse($rules->isApplicable($apiOrder));
    }

    public function testWithConfigImportGroupCustomer()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');
        $rules = new GroupCustomer(['group_customer' => 1]);
        $this->assertTrue($rules->isApplicable($apiOrder));
    }

    /**
     * @dataProvider getCustomerGroup
     */
    public function testImportCustomer(int $groupId): void
    {
        $shopId = 1;
        $defaultConfig = json_decode(
            \Configuration::get(
                \Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
                null,
                null,
                $shopId
            ),
            true
        );
        $defaultConfig['ShoppingfeedAddon\OrderImport\Rules\GroupCustomer'] = ['group_customer' => $groupId];
        \Configuration::updateValue(
            \Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
            json_encode($defaultConfig),
            false,
            null,
            $shopId
        );
        $apiOrder = $this->getOrderRessourceFromDataset('order-customer-group-' . $groupId . '.json');
        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart'
        );
        $handler->setConveyor(
            [
                'id_shop' => $shopId,
                'id_token' => 1,
                'id_lang' => 1,
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);
        $processResult = $handler->process('shoppingfeedOrderImport');
        $this->assertTrue($processResult);
        $conveyor = $handler->getConveyor();
        $cart = $conveyor['cart'];
        $customer = new \Customer($cart->id_customer);
        $this->assertEquals($customer->firstname, 'Martin');
        $this->assertEquals($customer->lastname, 'Bernard');
        $this->assertEquals($groupId, $customer->id_default_group);
    }

    public function getCustomerGroup()
    {
        return [
            'group_2' => [2],
            'group_3' => [3],
        ];
    }
}
