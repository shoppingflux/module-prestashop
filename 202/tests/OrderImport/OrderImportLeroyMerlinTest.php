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

use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;

class OrderImportLeroyMerlinTest extends AbstractOrdeTestCase
{
    public function testAssociationWithModuleColissimo()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-leroymerlin-colissimo.json');

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

        $conveyor = $handler->getConveyor();

        $this->assertArrayHasKey('cart', $conveyor);
        $this->assertNotNull($conveyor['cart']->id);
        $idColissimoPickupPoint = \ColissimoCartPickupPoint::getByCartId($conveyor['cart']->id);
        $pickupPoint = new \ColissimoPickupPoint((int) $idColissimoPickupPoint);

        $this->assertEquals($pickupPoint->colissimo_id, '908645');
        $this->assertEquals($pickupPoint->product_code, 'A2P');
    }
}
