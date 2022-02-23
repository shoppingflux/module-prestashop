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
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedPreloading;
use ShoppingfeedToken;
use Tools;

class TestAttributeTestCase extends TestCase
{
    public function testGetProductAvailabilityLabelWithStockAndAvailableMessage(): void
    {
        $id_product = 1;
        $token = (new ShoppingfeedToken())->getDefaultToken();
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $token['id_shoppingfeed_token']])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($token['id_shoppingfeed_token'], $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = Tools::jsonDecode($product['content'], true);
        $this->assertArrayHasKey('attributes', $productContent);
        $this->assertArrayHasKey('availability_label', $productContent['attributes']);
        $this->assertEquals($productContent['attributes']['availability_label'], 'disponible');
    }

    public function testGetProductAvailabilityLabelWithStockAndNotAvailableMessage(): void
    {
        $id_product = 2;
        $token = (new ShoppingfeedToken())->getDefaultToken();
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $token['id_shoppingfeed_token']])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($token['id_shoppingfeed_token'], $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = Tools::jsonDecode($product['content'], true);
        $this->assertArrayHasKey('attributes', $productContent);
        $this->assertArrayNotHasKey('availability_label', $productContent['attributes']);
    }

    public function testGetProductAvailabilityLabelVariationWithStockAndAvailableMessage(): void
    {
        $id_product = 3;
        $id_product_attribute = 13;
        $token = (new ShoppingfeedToken())->getDefaultToken();
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $token['id_shoppingfeed_token']])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($token['id_shoppingfeed_token'], $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = Tools::jsonDecode($product['content'], true);
        $this->assertArrayHasKey('variations', $productContent);
        $this->assertArrayHasKey($id_product_attribute, $productContent['variations']);
        $this->assertArrayHasKey('attributes', $productContent['variations'][$id_product_attribute]);
        $this->assertArrayHasKey('availability_label', $productContent['variations'][$id_product_attribute]['attributes']);
        $this->assertEquals($productContent['variations'][$id_product_attribute]['attributes']['availability_label'], 'non disponible');
    }

    public function testGetProductAvailabilityLabelVariationWithStockAndNotAvailableMessage(): void
    {
        $id_product = 4;
        $id_product_attribute = 16;
        $token = (new ShoppingfeedToken())->getDefaultToken();
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $token['id_shoppingfeed_token']])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($token['id_shoppingfeed_token'], $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = Tools::jsonDecode($product['content'], true);
        $this->assertArrayHasKey('variations', $productContent);
        $this->assertArrayHasKey($id_product_attribute, $productContent['variations']);
        $this->assertArrayHasKey('attributes', $productContent['variations'][$id_product_attribute]);
        $this->assertArrayNotHasKey('availability_label', $productContent['variations'][$id_product_attribute]['attributes']);
    }
}
