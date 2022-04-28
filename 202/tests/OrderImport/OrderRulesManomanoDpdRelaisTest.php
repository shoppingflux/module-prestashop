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

use Cart;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\Rules\ManomanoDpdRelais;

/**
 * Order Rules ManomanoDpdRelais Test
 */
class OrderRulesManomanoDpdRelaisTest extends AbstractOrdeTestCase
{
    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitManoManoRelais(): void
    {
        $params['apiOrder'] = $this->getOrderRessourceFromDataset('order-manomano.json');
        $params['cart'] = new Cart();

        $rules = new ManomanoDpdRelais();
        $this->assertTrue($rules->isApplicable($params['apiOrder']));

        $params['orderData'] = new OrderData($params['apiOrder']);
        $rules->onPreProcess($params);

        $expedtedAddress = [
                'firstName' => 'patrice',
                'lastName' => 'lafont',
                'company' => 'tabac le phenix (p31175)',
                'street' => '5 place du 11 novembre 1918',
                'street2' => '',
                'other' => 'p31175',
                'postalCode' => '30150',
                'city' => 'Saint-geniès-de-comolas',
                'country' => 'FR',
                'phone' => '+3361234567',
                'mobilePhone' => '+3361234567',
                'email' => 'm_bcaddf4fgca@message.manomano.com',
        ];
        $this->assertSame($expedtedAddress, $params['orderData']->shippingAddress);
    }

    /**
     * Test to split name
     *
     * @return void
     */
    public function testSplitManoManoProRelais(): void
    {
        $params['apiOrder'] = $this->getOrderRessourceFromDataset('order-manomanopro.json');

        $rules = new ManomanoDpdRelais();
        $this->assertTrue($rules->isApplicable($params['apiOrder']));
    }
}
