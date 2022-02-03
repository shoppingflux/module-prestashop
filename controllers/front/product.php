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

use ShoppingFeed\Feed\Product\Product;
use ShoppingfeedAddon\Services\SfProductGenerator;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ShoppingfeedProductModuleFrontController extends \ModuleFrontController
{
    protected $sfToken = null;

    public function initContent()
    {
        $token = Tools::getValue('token');
        if (empty($token)) {
            $token = (new ShoppingfeedToken())->getDefaultToken();
        } else {
            $token = (new ShoppingfeedToken())->findByToken($token);
        }

        if ($token === false) {
            exit();
        }

        $this->preparePreloading($token);

        $this->sfToken = $token;
        $fileXml = sprintf('file-%d.xml', $token['id_shoppingfeed_token']);
        ProcessLoggerHandler::logSuccess(sprintf('Generate file %s for token %s:.', $fileXml, $token['content']), null, null, 'ShoppingfeedProductModuleFrontController');
        ProcessLoggerHandler::closeLogger();
        $productGenerator = new SfProductGenerator($fileXml, 'xml');
        $productGenerator->setPlatform('Prestashop', _PS_VERSION_)
            ->addMapper([$this, 'mapper']);

        $limit = 100;
        $nb_iteration = ceil((new ShoppingfeedPreloading())->getPreloadingCount($token['id_shoppingfeed_token']) / $limit);
        $productGenerator->open();

        for ($i = 0; $i < $nb_iteration; ++$i) {
            $products = (new ShoppingfeedPreloading())->findAllByTokenId($token['id_shoppingfeed_token'], $i * $limit, $limit);
            $productGenerator->appendProduct($products);
        }

        $productGenerator->close();

        Tools::redirect(
            $this->getBaseLink($token['id_shop']) . $fileXml,
            __PS_BASE_URI__,
            null,
            ['HTTP/1.1 302 Moved Temporarily']
        );
        exit;
    }

    private function getBaseLink($id_shop)
    {
        $ssl_enable = (bool) Configuration::get('PS_SSL_ENABLED');
        $shop = new Shop($id_shop);
        $base = (($ssl_enable) ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);

        return $base . $shop->getBaseURI();
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
        if (isset($item['ecotax']) === true) {
            $product->setEcotax($item['ecotax']);
        }
        if (isset($item['vat']) === true) {
            $product->setVat($item['vat']);
        }
        if (isset($item['weight']) === true) {
            $product->setWeight($item['weight']);
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

        if (false === empty($item['specificPrices'])) {
            $discount = $this->calculDiscount($item['specificPrices']);

            if ($discount > 0) {
                $product->addDiscount($discount);
            }
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
            if (empty($variation['shipping']) !== true) {
                $variationProduct->addShipping($variation['shipping']['amount'], $variation['shipping']['label']);
            }
            if (empty($variation['attributes']) !== true) {
                $variationProduct->setAttributes($variation['attributes']);
            }
            if (empty($variation['images']) !== true) {
                $variationProduct->setAdditionalImages($variation['images']);
            }

            if (isset($variation['specificPrices']) && false === empty($variation['specificPrices'])) {
                $discount = $this->calculDiscount($variation['specificPrices']);

                if ($discount > 0) {
                    $variationProduct->addDiscount($discount);
                }
            }
        }
    }

    protected function preparePreloading($token)
    {
        $sfp = new ShoppingfeedPreloading();
        $products = $sfp->findAllPoorByTokenId($token['id_shoppingfeed_token']);
        $ids = [];

        if (empty($products)) {
            return true;
        }

        try {
            foreach ($products as $product) {
                $ids[] = $product['id_product'];
                $sfp->saveProduct($product['id_product'], $product['id_token'], $token['id_lang'], $token['id_shop'], $token['id_currency']);
            }
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf('Error while update a preloading products: %s. File: %s. Line: %d', $e->getMessage(), $e->getFile(), $e->getLine()),
                null,
                null,
                'ShoppingfeedProductModuleFrontController'
            );

            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf($this->module->l('products updated: %s', 'product'), implode(',', $ids)),
            null,
            null,
            'ShoppingfeedProductModuleFrontController'
        );

        return true;
    }

    /**
     * @param array $specificPrices
     *
     * @return float
     */
    protected function calculDiscount($specificPrices)
    {
        if (false === is_array($specificPrices)) {
            return 0;
        }

        if (is_null($this->sfToken)) {
            return 0;
        }

        foreach ($specificPrices as $specificPrice) {
            if (false === isset($specificPrice['from'])) {
                continue;
            }

            if (false === isset($specificPrice['to'])) {
                continue;
            }

            if (false === isset($specificPrice['discount'])) {
                continue;
            }

            if (false === isset($specificPrice['id_shop']) || ((int) $specificPrice['id_shop'] !== 0 && $specificPrice['id_shop'] != $this->sfToken['id_shop'])) {
                continue;
            }

            if (false === isset($specificPrice['id_currency']) || ((int) $specificPrice['id_currency'] !== 0 && $specificPrice['id_currency'] != $this->sfToken['id_currency'])) {
                continue;
            }

            if (false === isset($specificPrice['id_group']) || (int) $specificPrice['id_group'] !== 0) {
                continue;
            }

            if (false === isset($specificPrice['id_customer']) || (int) $specificPrice['id_customer'] !== 0) {
                continue;
            }

            if (false === isset($specificPrice['id_country']) || (int) $specificPrice['id_country'] !== 0) {
                continue;
            }

            $now = new DateTime();
            if ($specificPrice['to'] !== '0000-00-00 00:00:00') {
                $to = DateTime::createFromFormat('Y-m-d H:i:s', $specificPrice['to']);
                if ($to->diff($now)->invert === 0) {
                    continue;
                }
            }
            if ($specificPrice['from'] !== '0000-00-00 00:00:00') {
                $from = DateTime::createFromFormat('Y-m-d H:i:s', $specificPrice['from']);
                if ($from->diff($now)->invert === 1) {
                    continue;
                }
            }

            return (float) $specificPrice['discount'];
        }

        return 0;
    }
}
