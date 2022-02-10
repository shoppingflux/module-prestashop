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

use PHPUnit\Framework\TestCase;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingFeed\Sdk\Hal\HalResource;

/**
 * Abstract Order Import Case
 */
abstract class AbstractOrdeTestCase extends TestCase
{
    /**
     * Test to import a standard order
     *
     * @return void
     */
    protected function getOrderRessourceFromDataset(string $dataset): OrderResource
    {
        $dataset = file_get_contents(__DIR__ . '/dataset/' . $dataset);
        $this->props = json_decode($dataset, true);
        $client = $this->createMock(\ShoppingFeed\Sdk\Hal\HalClient::class);
        $halResource = $this
            ->getMockBuilder(HalResource::class)
            ->setConstructorArgs([$client, $this->props, $this->props['_links'], $this->props['_embedded']])
            ->setMethods(['getFirstResources'])
            ->getMock();

        return new OrderResource($halResource);
    }
}
