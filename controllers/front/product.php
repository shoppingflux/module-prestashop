<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Feed\ProductGenerator;
use ShoppingFeed\Feed\Product\Product;

class ShoppingfeedProductModuleFrontController  extends \ModuleFrontController
{
    public function initContent()
    {
        $token = Tools::getValue('token');
        if (empty($token)) {
            $token = (new ShoppingfeedToken())->getDefaultToken();
        } else {
            $token = (new ShoppingfeedToken())->findByToken($token);
        }

        if ($token === false) {
            die();
        }
        $fileXml = sprintf('file-%d.xml', $token['id_shoppingfeed_token']);
        $productGenerator = new ProductGenerator($fileXml, 'xml');
        $productGenerator->setPlatform('Prestashop', _PS_VERSION_)
                ->addMapper([$this, 'mapper']);

        $limit = 100;
        $iterations = range(0, floor((new ShoppingfeedPreloading)->getPreloadingCount() / 100));
        foreach($iterations as $iteration) {
            $products = (new ShoppingfeedPreloading)->findAllByToken($token['content'], $iteration * $limit, $limit);
            $productGenerator->write($products);
        }

        header('HTTP/1.1 302 Moved Temporarily');
        header('Location: '. __PS_BASE_URI__ . $fileXml);
        exit;
    }

    public function mapper(array $item, Product $product)
    {
        $product
            ->setName($item['name'])
            ->setDescription($item['description']['full'], $item['description']['short'])
            ->setReference($item['reference'])
            ->setGtin($item['gtin'])
            ->setQuantity($item['quantity'])
            ->setLink($item['link'])
            ->setPrice($item['price'])
            ->addShipping($item['shipping']['amount'], $item['shipping']['label'])
            ->setAttributes($item['attributes'])
        ;
        if (empty($item['brand']) !== true) {
            $product->setBrand($item['brand']['name'], $item['brand']['link']);
        }
        if (empty($item['category']) !== true) {
            $product->setCategory($item['category']['name'], $item['category']['link']);
        }
        foreach ($item['discounts'] as $discount) {
            $product->addDiscount($discount);
        }
        if (empty($item['images']) !== true && empty($item['images']['main']) !== true) {
            $product->setMainImage($item['images']['main']);
            $product->setAdditionalImages($item['images']['additional']);
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
