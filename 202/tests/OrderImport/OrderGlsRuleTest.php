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

use ShoppingfeedAddon\OrderImport\GLS\Adapter;
use ShoppingfeedAddon\OrderImport\Rules\GlsRule;

class OrderGlsRuleTest extends AbstractOrdeTestCase
{
    public function testRule(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-gls.json');

        $cart = new \Cart();
        $cart->id_address_delivery = $this->getDefaultAddress()->id;
        $cart->id_customer = $this->getDefaultAddress()->id_customer;
        $cart->id_shop = $this->getDefaultShop()->id;
        $cart->id_shop_group = $this->getDefaultShop()->id_shop_group;
        $cart->id_carrier = $this->getDefaultIdCarrier();
        $cart->id_currency = $this->getDefaultIdCurrency()->id;
        $cart->save();

        $glsAdapter = $this->getGlsAdapter();
        $rule = new GlsRule();
        $rule->setGlsAdapter($glsAdapter);

        $this->assertTrue($rule->isApplicable($apiOrder));

        $rule->afterCartCreation([
            'apiOrder' => $apiOrder,
            'cart' => $cart,
        ]);

        $result = \Db::getInstance()
            ->getRow(
                (new \DbQuery())
                    ->from('gls_cart_carrier')
                    ->where('id_cart = ' . (int) $cart->id)
            );

        $this->assertNotEmpty($result);
        $this->assertEquals('2500976161', $result['parcel_shop_id']);
    }

    protected function getDefaultAddress()
    {
        return new \Address(1);
    }

    protected function getDefaultShop()
    {
        return new \Shop(1);
    }

    protected function getGlsAdapter()
    {
        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->onlyMethods(['getRelayDetail', 'getGlsProductCode'])
            ->getMock();
        $adapter->method('getRelayDetail')->willReturn([
            'parcelShopById' => '2500976161',
            'Name1' => 'TABAC BRENET',
            'Street1' => '15 FAUBOURG SAINT ETIENNE',
            'ZipCode' => '25300',
            'City' => 'PONTARLIER',
            'Country' => 'FR',
            'Phone' => '',
            'Mobile' => '',
            'email' => '',
            'url' => '',
            'latitude' => '46.90188',
            'longitude' => '6.35976',
            'GLSWorkingDay' => json_decode('[{"Day":0,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}},{"Day":1,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}},{"Day":2,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}},{"Day":3,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}},{"Day":4,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}},{"Day":5,"OpeningHours":{"Hours":{"From":"080000","To":"200000"}},"Breaks":{"Hours":{"From":"","To":""}}}]'),
            'Name2' => '',
            'Name3' => '',
            'ContactName' => '',
            'BlockNo1' => '',
            'Street2' => '',
            'BlockNo2' => '',
            'Province' => '',
        ]);
        $adapter->method('getGlsProductCode')->willReturn('02');

        return $adapter;
    }

    protected function getDefaultIdCarrier()
    {
        return \Db::getInstance()
            ->getValue(
                (new \DbQuery())
                    ->select('id_carrier')
                    ->from('carrier')
                    ->where('external_module_name = "nkmgls"')
                    ->where('deleted = 0')
            );
    }

    protected function getDefaultIdCurrency()
    {
        return new \Currency(1);
    }
}
