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

namespace Tests\ProductSerializer;

use PHPUnit\Framework\TestCase;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedPreloading;
use Tools;

class TestAttributeTestCase extends TestCase
{
    public function testPrelodingTableContent()
    {
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');
        $products = (new ShoppingfeedPreloading())->findAllByTokenId($id_token);
        $this->assertEquals(count($products), 18);
        foreach ($products as $product) {
            $this->assertArrayHasKey('price', $product);
            $this->assertArrayHasKey('quantity', $product);
        }
    }

    public function testGetPriceAndQuantity()
    {
        $id_token = 1;
        $id_product = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');
        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($id_token, $id_product);
        $productContent = json_decode($product['content'], true);
        $this->assertIsArray($productContent);

        $this->assertEquals($productContent['price'], 28.68);
        $this->assertEquals($productContent['category']['name'], 'Root > Home > Clothes > Men');
        $this->assertEquals($productContent['brand']['name'], 'Studio Design');
        $this->assertEquals($productContent['weight'], 0.300000);
        $this->assertEquals($productContent['shipping']['amount'], 0);
        $this->assertEquals($productContent['reference'], 1);
        $this->assertEquals($productContent['name'], 'Hummingbird printed t-shirt');
        $this->assertEquals($productContent['vat'], 20);
        $this->assertEquals($productContent['attributes']['vat'], 20);
        $this->assertEquals($productContent['attributes']['available_for_order'], 1);
        $this->assertEquals($productContent['attributes']['on_sale'], 0);
        $this->assertEquals($productContent['attributes']['hierararchy'], 'parent');
        $this->assertEquals($productContent['attributes']['mpn'], 'demo_1');
        $this->assertEquals($productContent['attributes']['supplier'], 'Fashion supplier');
        $this->assertIsString($productContent['attributes']['supplier_link']);
        $this->assertIsArray($productContent['images']);
        $this->assertEquals($productContent['attributes']['Composition'], 'Cotton');
        $this->assertEquals($productContent['attributes']['Property'], 'Short sleeves');
        $this->assertEquals($productContent['attributes']['availability_label'], 'disponible');

        $this->assertEquals(count($productContent['variations']), 8);
        $this->assertEquals($productContent['variations'][1]['reference'], '1_1');
        $this->assertEquals($productContent['variations'][1]['attributes']['hierararchy'], 'child');
        $this->assertEquals($productContent['variations'][1]['attributes']['Colorhexa'], '#ffffff');
        $this->assertEquals($productContent['variations'][1]['attributes']['Size'], 'S');
        $this->assertEquals($productContent['variations'][1]['attributes']['Color'], 'White');
    }

    public function testPrelodingTablePrice()
    {
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');
        $products = (new ShoppingfeedPreloading())->findAllByTokenId($id_token);
        $this->assertEquals(count($products), 18);
    }

    public function testGetProductAvailabilityLabelWithStockAndAvailableMessage(): void
    {
        $id_product = 1;
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($id_token, $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = json_decode($product['content'], true);
        $this->assertIsArray($productContent);
        $this->assertArrayHasKey('attributes', $productContent);
        $this->assertArrayHasKey('availability_label', $productContent['attributes']);
        $this->assertEquals($productContent['attributes']['availability_label'], 'disponible');
    }

    public function testGetProductAvailabilityLabelWithStockAndNotAvailableMessage(): void
    {
        $id_product = 2;
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($id_token, $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = json_decode($product['content'], true);
        $this->assertIsArray($productContent);
        $this->assertArrayHasKey('attributes', $productContent);
        $this->assertArrayNotHasKey('availability_label', $productContent['attributes']);
    }

    public function testGetProductAvailabilityLabelVariationWithStockAndAvailableMessage(): void
    {
        $id_product = 3;
        $id_product_attribute = 13;
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($id_token, $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = json_decode($product['content'], true);

        $this->assertIsArray($productContent);
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
        $id_token = 1;
        $handler = new ActionsHandler();
        $handler->addActions('getBatch')
                ->setConveyor(['id_token' => $id_token])
                ->process('ShoppingfeedProductSyncPreloading');

        $product = (new ShoppingfeedPreloading())->findByTokenIdAndProductId($id_token, $id_product);
        $this->assertArrayHasKey('content', $product);
        $productContent = json_decode($product['content'], true);
        $this->assertIsArray($productContent);
        $this->assertArrayHasKey('variations', $productContent);
        $this->assertArrayHasKey($id_product_attribute, $productContent['variations']);
        $this->assertArrayHasKey('attributes', $productContent['variations'][$id_product_attribute]);
        $this->assertArrayNotHasKey('availability_label', $productContent['variations'][$id_product_attribute]['attributes']);
    }
}
