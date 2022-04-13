<?php
/**
 *
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
 *
 */

namespace Tests\OrderImport;

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingFeed\Sdk\Hal\HalResource;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;
use Currency;
use Tools;

/**
 * Order Rules CDiscount Test
 */
class OrderImportVerifyCurrencyTest extends AbstractOrdeTestCase
{
    /**
     * @dataProvider providerApiOrder
     */
    public function testCurrencyVerification($apiOrder, $vefificationResult): void
    {
        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder'
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
        $this->assertEquals($vefificationResult, $processResult);
    }

    public function providerApiOrder()
    {
        $dataset = $this->getDataSet();
        $client = $this->createMock(\ShoppingFeed\Sdk\Hal\HalClient::class);

        $dataset['payment']['currency'] = $this->getSupportedCurrency();
        $halResourceValid = $this
            ->getMockBuilder(HalResource::class)
            ->setConstructorArgs([$client, $dataset, $dataset['_links'], $dataset['_embedded']])
            ->setMethods(['getFirstResources'])
            ->getMock();

        $dataset['payment']['currency'] = $this->getUnsupportedCurrency();
        $halResourceInvalid = $this
            ->getMockBuilder(HalResource::class)
            ->setConstructorArgs([$client, $dataset, $dataset['_links'], $dataset['_embedded']])
            ->setMethods(['getFirstResources'])
            ->getMock();

        $apiOrderValid = new OrderResource($halResourceValid);
        $apiOrderInvalid = new OrderResource($halResourceInvalid);

        return [
            'supportedCurrency' => [
                $apiOrderValid,
                true
            ],
            'unsupportedCurrency' => [
                $apiOrderInvalid,
                false
            ]
        ];
    }

    protected function getDataSet()
    {
        return json_decode(
            file_get_contents(__DIR__ . '/dataset/order-cdiscount-colissimorelais.json'),
            true
        );
    }

    protected function getSupportedCurrency()
    {
        return Tools::strtoupper(Currency::getDefaultCurrency()->iso_code);
    }

    protected function getUnsupportedCurrency()
    {
        return 'INVALID_ISO_CURRENCY';
    }
}
