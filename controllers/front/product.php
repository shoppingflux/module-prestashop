<?php

use ShoppingFeed\Feed\ProductGenerator;
use ShoppingFeed\Feed\Product\Product;

class ShoppingfeedProductModuleFrontController  extends \ModuleFrontController
{
    public function initContent()
    {
        $products = ShoppingfeedPreloading::findAll();

        (new ProductGenerator('file.xml', 'xml'))
                ->setPlatform('Prestashop', _PS_VERSION_)
                ->addMapper([$this, 'mapper'])
                ->write($products);
        die();
    }

    public function mapper(array $item, Product $product)
    {
        $product
            ->setName($item['name'])
            ->setDescription($item['description']['full'], $item['description']['short'])
            ->setBrand($item['brand']['name'], $item['brand']['link'])
            ->setCategory($item['category']['name'], $item['category']['link'])
            ->setReference($item['reference'])
            ->setGtin($item['gtin'])
            ->setQuantity($item['quantity'])
            ->setLink($item['link'])
            ->setPrice($item['price'])
            ->addShipping($item['shipping']['amount'], $item['shipping']['label'])
            ->setAttributes($item['attributes'])
        ;
        foreach ($item['discounts'] as $discount) {
            $product->addDiscount($discount);
        }
        if ($item['images'] !== [] && empty($item['images'] ['main'])) {
            $product->setMainImage($item['images']['main']);
            foreach ($item['images']['additional'] as $additionalImage) {
                $product->setAdditionalImages($additionalImage);
            }
        }

        foreach ($item['variations'] as $variation) {
            $variationProduct = $product->createVariation();
            $variationProduct
                ->setReference($variation['reference'])
                ->setGtin($variation['gtin'])
                ->setQuantity($variation['quantity'])
                ->setLink($variation['link'])
                ->setPrice($variation['price'])
                ->addShipping($variation['shipping']['amount'], $variation['shipping']['label'])
                ->setAttributes($variation['attributes'])
                ->setAdditionalImages($variation['images'])
            ;
        }
    }
}
