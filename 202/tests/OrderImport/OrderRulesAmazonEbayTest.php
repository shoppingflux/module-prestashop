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

use ShoppingfeedAddon\OrderImport\Rules\AmazonEbay;

/**
 * Order Rules AmazonEbay Test
 */
class OrderRulesAmazonEbayTest extends AbstractOrdeTestCase
{
    /**
     * Test to import a standard order
     *
     * @return void
     */
    public function testSplitNameOk(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');

        $rules = new AmazonEbay();
        $this->assertTrue($rules->isApplicable($apiOrder));
        $address = $apiOrder->toArray()['billingAddress'];

        $rules->updateAddress($address);

        $expedtedAddress = [
                'firstName' => 'Bernard',
                'lastName' => 'Martin',
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
        $this->assertSame($address, $expedtedAddress);
    }

    /**
     * Test to import a standard order
     *
     * @return void
     */
    public function testNotApplicable(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-naturedecouverte.json');

        $rules = new AmazonEbay();
        $this->assertFalse($rules->isApplicable($apiOrder));
    }
}
