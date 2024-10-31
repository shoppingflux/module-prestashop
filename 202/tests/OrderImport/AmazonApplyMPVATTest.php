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
use ShoppingfeedAddon\OrderImport\Rules\AmazonApplyMPVAT;
use ShoppingfeedClasslib\Registry;

class AmazonApplyMPVATTest extends AbstractOrdeTestCase
{
    public function testApplyingTax(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-business-tax.json');
        $rule = new AmazonApplyMPVAT(['enabled' => true]);

        $this->assertTrue($rule->isApplicable($apiOrder));

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
        $conveyor = $handler->getConveyor();

        $this->assertTrue($processResult);

        $psOrder = new Order((int) $conveyor['id_order']);
        $taxAmount = round($psOrder->getTotalProductsWithTaxes() - $psOrder->getTotalProductsWithoutTaxes(), 2);
        $this->assertEquals(10.74, $taxAmount);
    }

    public function testMissingTax(): void
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-business-missing-tax.json');
        $rule = new AmazonApplyMPVAT(['enabled' => true]);

        $this->assertTrue($rule->isApplicable($apiOrder));

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
        $conveyor = $handler->getConveyor();

        $this->assertTrue($processResult);

        $psOrder = new Order((int) $conveyor['id_order']);
        $taxAmount = round($psOrder->getTotalProductsWithTaxes() - $psOrder->getTotalProductsWithoutTaxes(), 2);
        $this->assertEquals(0, $taxAmount);
    }
}
