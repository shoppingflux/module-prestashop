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

use Order;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedAddon\OrderImport\Rules\TaxForBusiness;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules AmazonEbay Test
 */
class OrderRuleTaxForBusinessTest extends AbstractOrdeTestCase
{
    public function testApplicability()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon.json');
        $apiOrderBusiness = $this->getOrderRessourceFromDataset('order-amazon-business.json');
        $manmanoOrderBusiness = $this->getOrderRessourceFromDataset('order-manomano-tax-business.json');
        $rule = new TaxForBusiness(['enabled' => true]);
        $ruleDisabled = new TaxForBusiness(['enabled' => false]);

        $this->assertFalse($rule->isApplicable($apiOrder));
        $this->assertFalse($ruleDisabled->isApplicable($apiOrderBusiness));
        $this->assertTrue($rule->isApplicable($apiOrderBusiness));
        $this->assertTrue($rule->isApplicable($manmanoOrderBusiness));
    }

    /**
     * "undefined" means the marketplace did not send a tax mode: Amazon keeps using the Shoppingfeed tax
     */
    public function testAmazonUndefinedTaxModeUsesShoppingfeedTax()
    {
        $flags = $this->recalculationFlags('order-amazon-business.json');

        $this->assertTrue($flags['isUseSfTax']);
        $this->assertFalse($flags['skipTax']);
    }

    public function testTaxIncludedModeUsesPrestashopTax()
    {
        $flags = $this->recalculationFlags('order-amazon-business.json', ['payment' => ['taxMode' => 'tax_included']]);

        $this->assertFalse($flags['isUseSfTax']);
        $this->assertFalse($flags['skipTax']);
        $this->assertTrue($flags['isAmountTaxIncl']);
    }

    public function testTaxExcludedModeUsesShoppingfeedTax()
    {
        $flags = $this->recalculationFlags('order-amazon-business.json', ['payment' => ['taxMode' => 'tax_excluded']]);

        $this->assertTrue($flags['isUseSfTax']);
        $this->assertFalse($flags['skipTax']);
    }

    /**
     * Flags set by TaxForBusiness::beforeRecalculateOrderPrices(), from the defaults used by recalculateOrderPrices()
     */
    private function recalculationFlags($dataset, array $overrides = [])
    {
        $apiOrder = $this->getOrderRessourceFromDataset($dataset, $overrides);
        $rule = new TaxForBusiness(['enabled' => true]);
        $this->assertTrue($rule->isApplicable($apiOrder));

        $flags = ['isAmountTaxIncl' => true, 'skipTax' => false, 'isUseSfTax' => false];
        $rule->beforeRecalculateOrderPrices([
            'apiOrder' => $apiOrder,
            'id_order' => 0,
            'isAmountTaxIncl' => &$flags['isAmountTaxIncl'],
            'skipTax' => &$flags['skipTax'],
            'isUseSfTax' => &$flags['isUseSfTax'],
        ]);

        return $flags;
    }

    public function testImportAmazonBusiness()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-amazon-business.json');

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
        $order = new \Order($handler->getConveyor()['id_order']);
        $tax = round($order->total_paid_tax_incl - $order->total_paid_tax_excl, 2);

        $this->assertTrue($processResult);
        $this->assertEquals(10.74, $tax);
    }

    public function testImportRetifBusiness()
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-business.json');

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
        $order = new \Order($handler->getConveyor()['id_order']);
        $tax = round($order->total_paid_tax_incl - $order->total_paid_tax_excl, 2);

        $this->assertTrue($processResult);
        $this->assertEquals(0, $tax);
    }
}
