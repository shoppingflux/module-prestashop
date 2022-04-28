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
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules AmazonEbay Test
 */
class OrderImportWithConfigTest extends AbstractOrdeTestCase
{
    /**
     * Set Configurations
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT, 'reference');
    }

    /**
     * Test to import a standard order Amazon
     *
     * @return void
     */
    public function testImportReferenceAsSKU(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-veepee.json');

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
        $conveyor = $handler->getConveyor();
        $customer = $conveyor['customer'];
        $this->assertEquals($customer->firstname, 'CAROLINE');
        $this->assertEquals($customer->lastname, 'STEFANINI');
        //$this->assertEquals($customer->email, 'caroline@domain.fr');

        $sfOrder = $conveyor['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'Veepeegroup');
        $this->assertEquals($sfOrder->id_order_marketplace, '200000VEEPEE200000');

        $psOrder = $conveyor['psOrder'];
        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
    }

    /**
     * Unset Configurations
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        \Configuration::updateGlobalValue(\Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT, '');
    }
}
