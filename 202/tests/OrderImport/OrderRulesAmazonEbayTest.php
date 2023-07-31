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

use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\Rules\AmazonEbay;

/**
 * Order Rules AmazonEbay Test
 */
class OrderRulesAmazonEbayTest extends AbstractOrdeTestCase
{
    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitNameOk(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));

        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
                'firstName' => 'Martin',
                'lastName' => 'Bernard',
                'company' => '202 ecommerce',
                'street' => '10 rue Vivienne',
                'street2' => '',
                'other' => '',
                'postalCode' => '75002',
                'city' => 'Paris',
                'country' => 'FR',
                'phone' => '0123456789',
                'mobilePhone' => '0623456789',
                'email' => 'unique.id@test.net',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    /**
     * Test to split none avalailble marketpalce
     *
     * @return void
     */
    public function testNotApplicable(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-naturedecouverte.json');

        $rules = new AmazonEbay();
        $this->assertFalse($rules->isApplicable($apiOrder));
    }

    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitNameLaredoute(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-laredoute.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));

        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
                'firstName' => 'CELINE',
                'lastName' => 'CARRA',
                'company' => '',
                'street' => '25 RUE DES MARONNIERS',
                'street2' => '',
                'other' => '',
                'postalCode' => '71250',
                'city' => 'CLUNY',
                'country' => 'FR',
                'phone' => '',
                'mobilePhone' => '',
                'email' => 'mp+20220414342060+000010890@web.redoute.fr',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    public function testSplitNameLaredoutemirakl(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-laredoutemirakl.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));

        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
            'firstName' => 'CELINE',
            'lastName' => 'CARRA',
            'company' => '',
            'street' => '10 rue Vivienne',
            'street2' => '',
            'other' => '',
            'postalCode' => '75000',
            'city' => 'Paris',
            'country' => 'FR',
            'phone' => '',
            'mobilePhone' => '',
            'email' => 'mp+20220414342060+000010890@web.redoute.fr',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitNameEbay(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-ebay.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));

        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
                'firstName' => 'Jean-Pierre',
                'lastName' => 'Le Rouge',
                'company' => '',
                'street' => '23 rue de la jetée',
                'street2' => '',
                'other' => 'leg1520',
                'postalCode' => '06800',
                'city' => 'Cagnes Sur Mer',
                'country' => 'FR',
                'phone' => '065544332211',
                'mobilePhone' => '',
                'email' => 'abcdefghijklmopq@members.ebay.com',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitNameAmazon(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-shipped.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));
        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
                'firstName' => 'Edgar',
                'lastName' => 'Lameloise',
                'company' => '',
                'street' => '36 Place d\'Armes',
                'street2' => '',
                'other' => '',
                'postalCode' => '71150',
                'city' => 'CHAGNY',
                'country' => 'FR',
                'phone' => '0385112233',
                'mobilePhone' => '',
                'email' => 'gfwv9k7xvwfrg11@marketplace.amazon.fr',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitNameAlltricks(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-alltricks.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));
        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
            'firstName' => 'Martin',
            'lastName' => 'Bernard',
            'company' => '202 ecommerce',
            'street' => '10 rue Vivienne',
            'street2' => '',
            'other' => '',
            'postalCode' => '75002',
            'city' => 'Paris',
            'country' => 'FR',
            'phone' => '0123456789',
            'mobilePhone' => '0623456789',
            'email' => 'unique.id.1@test.net',
        ];

        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }

    public function testSplitNameMiravia(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-miravia.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));
        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);

        $expedtedAddress = [
            'firstName' => 'Martin',
            'lastName' => 'Bernard',
            'company' => '202 ecommerce',
            'street' => '10 rue Vivienne',
            'street2' => '',
            'other' => '',
            'postalCode' => '75002',
            'city' => 'Paris',
            'country' => 'FR',
            'phone' => '0123456789',
            'mobilePhone' => '0623456789',
            'email' => 'unique.id.2@test.net',
        ];

        $this->assertSame($expedtedAddress, $params['orderData']->billingAddress);
    }
}
