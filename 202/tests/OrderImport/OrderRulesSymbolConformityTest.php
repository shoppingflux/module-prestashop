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

use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\Rules\SymbolConformity;

/**
 * Order Rules AmazonEbay Test
 */
class OrderRulesSymbolConformityTest extends AbstractOrdeTestCase
{
    /**
     * Test to split name
     *
     * @return void
     */
    public function testSymbolConfomity(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-prohibited-symbols.json');

        $rules = new SymbolConformity();
        $params['orderData'] = new OrderData($apiOrder);
        $params['apiOrder'] = $apiOrder;
        $rules->onPreProcess($params);
        $customerData = $params['orderData']->getCustomer();
        $apiBillingAddress = $params['orderData']->billingAddress;

        $customer = new \Customer();
        $customer->firstname = $customerData->getFirstName();
        $customer->lastname = $customerData->getLastName();
        $customer->email = $customerData->getEmail();
        $customer->passwd = md5(pSQL(_COOKIE_KEY_ . rand()));

        $this->assertTrue($customer->validateFields(false));

        $address = new \Address();
        $address->id_country = \Country::getByIso($apiBillingAddress['country']);
        $address->alias = 'testSymbolConformityAlias';
        $address->lastname = $apiBillingAddress['lastName'];
        $address->firstname = $apiBillingAddress['firstName'];
        $address->address1 = $apiBillingAddress['street'];
        $address->city = $apiBillingAddress['city'];
        $address->phone = $apiBillingAddress['mobilePhone'];

        $this->assertTrue($address->validateFields(false));
    }
}
