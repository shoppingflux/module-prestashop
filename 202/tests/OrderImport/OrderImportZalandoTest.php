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

use Hook;
use Order;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\OrderImport\Rules\Zalando;
use ShoppingfeedClasslib\Registry;
use ShoppingfeedOrder;
use Validate;

/**
 * Order Rules Zalando Test
 */
class OrderImportZalandoTest extends AbstractOrdeTestCase
{
    public function testImport()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-zalando-specificfields.json');

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
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        return $handler->getConveyor();
    }

    public function testRulesZalando()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-zalando-specificfields.json');
        $rules = new Zalando();
        $this->assertTrue($rules->isApplicable($apiOrder));
    }

    /**
     * @depends testImport
     */
    public function testIsOrderValid($conveyor)
    {
        $psOrder = new Order((int) $conveyor['id_order']);
        $this->assertTrue(Validate::isLoadedObject($psOrder));

        return $psOrder;
    }

    /**
     * @depends testImport
     */
    public function testIsSfOrderValid($conveyor)
    {
        $sfOrder = ShoppingfeedOrder::getByIdOrder((int) $conveyor['id_order']);
        $this->assertTrue(Validate::isLoadedObject($sfOrder));

        return $sfOrder;
    }

    /**
     * @depends testIsSfOrderValid
     */
    public function testDataSfOrder($psOrder)
    {
        $additionalFields = json_decode($psOrder->additionalFields, true);
        $this->assertTrue(is_array($additionalFields));
        $this->assertTrue(array_key_exists('customer_number', $additionalFields));
        $this->assertEquals($additionalFields['customer_number'], '93000070344');
    }

    /**
     * @depends testIsOrderValid
     */
    public function testDataOrder($psOrder)
    {
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
    }

    /**
     * @depends testIsOrderValid
     */
    public function testOrderDetails($psOrder)
    {
        $details = $psOrder->getOrderDetailList();

        $this->assertTrue(is_array($details));
        $this->assertTrue(count($details) === 2);
        $this->assertEquals($details[0]['product_name'], 'Haut de maillot triangle maille crochet Dolce noir : 32632708-cc99-32d1-aa43-ca59521a9c9e - JR581J004-Q11009500D');
        $this->assertEquals($details[1]['product_name'], 'Bas de maillot de bain brésilien en crochet à nouettes réglables Dolce noir : 0c6a3824-272a-3333-87bf-5a6962ad83b1 - JR581I00D-Q110042000');
    }

    /**
     * @depends testIsOrderValid
     */
    public function testHook($psOrder)
    {
        foreach ($psOrder->getInvoicesCollection()  as $invoice) {
            $display = Hook::exec('displayPDFInvoice', ['object' => $invoice]);
            $this->assertNotEquals(strpos($display, '93000070344'), 0);
        }
    }
}
