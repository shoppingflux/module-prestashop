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
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

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
        ProcessLoggerHandler::logInfo(sprintf('Generate file %s for token %s:.', $fileXml, $token['content']), null, null, 'ShoppingfeedProductModuleFrontController');
        ProcessLoggerHandler::closeLogger();
        $productGenerator = new ProductGenerator($fileXml, 'xml');
        $productGenerator->setPlatform('Prestashop', _PS_VERSION_)
                ->addMapper([$this, 'mapper']);

        $limit = 100;
        $products = [];
        $iterations = range(0, floor((new ShoppingfeedPreloading)->getPreloadingCount() / 100));
        foreach($iterations as $iteration) {
            $products = array_merge($products, (new ShoppingfeedPreloading)->findAllByTokenId($token['id_shoppingfeed_token'], $iteration * $limit, $limit));
        }

        $productGenerator->write($products);


        header('HTTP/1.1 302 Moved Temporarily');
        header('Location: '. __PS_BASE_URI__ . $fileXml);
        exit;
    }

    public function mapper(array $item, Product $product)
    {
        $product
            ->setName($item['name'])
            ->setReference($item['reference'])
            ->setPrice($item['price'])
        ;
        if (isset($item['quantity']) === true) {
            $product->setQuantity($item['quantity']);
        }
        if (empty($item['description']) !== true) {
            $product->setDescription($item['description']['full'], $item['description']['short']);
        }
        if (empty($item['gtin']) !== true) {
            $product->setGtin($item['gtin']);
        }
        if (empty($item['link']) !== true) {
            $product->setLink($item['link']);
        }
        if (empty($item['shipping']) !== true) {
            $product->addShipping($item['shipping']['amount'], $item['shipping']['label']);
        }
        if (empty($item['attributes']) !== true) {
            $product->setAttributes($item['attributes']);
        }
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
                ->setPrice($variation['price'])
            ;
            if (isset($variation['quantity']) === true) {
                $variationProduct->setQuantity($variation['quantity']);
            }
            if (empty($variation['gtin']) !== true) {
                $variationProduct->setGtin($variation['gtin']);
            }
            if (empty($variation['link']) !== true) {
                $variationProduct->setLink($variation['link']);
            }
            if (empty($variation['shipping']) !== true) {
                $variationProduct->addShipping($variation['shipping']['amount'], $variation['shipping']['label']);
            }
            if (empty($variation['attributes']) !== true) {
                $variationProduct->setAttributes($variation['attributes']);
            }
            if (empty($variation['images']) !== true) {
                $variationProduct->setAdditionalImages($variation['images']);
            }
        }
    }
}
