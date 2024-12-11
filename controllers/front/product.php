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
if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Feed\Product\Product;
use ShoppingfeedAddon\Services\SfProductGenerator;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ShoppingfeedProductModuleFrontController extends \ModuleFrontController
{
    protected $sfToken = null;

    protected $isCompressFeed;

    protected $productWithHierarchy;

    public function init()
    {
        parent::init();

        $this->sfToken = (new ShoppingfeedToken())->findByToken(Tools::getValue('token', ''));

        if (empty($this->sfToken)) {
            $this->sfToken = (new ShoppingfeedToken())->findByFeedKey(Tools::getValue('feed_key', ''));
        }

        $this->isCompressFeed = (int) Configuration::get(Shoppingfeed::COMPRESS_PRODUCTS_FEED);
        $this->productWithHierarchy = (Configuration::get(Shoppingfeed::PRODUCT_FEED_EXPORT_HIERARCHY) === 'product_with_children');
    }

    public function checkAccess()
    {
        if (empty($this->sfToken)) {
            http_response_code(401);
            exit;
        }

        return true;
    }

    public function initContent()
    {
        ProcessLoggerHandler::openLogger();
        $etag = sprintf('"%s"', ShoppingfeedPreloading::getEtag($this->sfToken['id_shoppingfeed_token']));
        header('Etag: ' . $etag);
        if (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            ProcessLoggerHandler::logSuccess(
                sprintf('Xml for token %s not Modified', $this->sfToken['id_shoppingfeed_token']),
                'shoppingfeed_token',
                $this->sfToken['id_shoppingfeed_token'],
                'ShoppingfeedProductModuleFrontController'
            );
            ProcessLoggerHandler::closeLogger();
            exit;
        }

        header('Content-type: text/xml');
        $this->preparePreloading($this->sfToken);
        $fileXmlGz = '';

        if ($this->isCompressFeed) {
            $fileXmlGz = sprintf(
                'shoppingfeed-%s.xml.gz',
                $this->module->tools->hash($this->sfToken['id_shoppingfeed_token'])
            );
            header('Content-Encoding: gzip');
            $productGenerator = new SfProductGenerator('compress.zlib://' . $fileXmlGz, 'xml');
        } else {
            $productGenerator = new SfProductGenerator('php://output', 'xml');
        }
        $productGenerator->setPlatform('Prestashop', _PS_VERSION_)
                         ->addMapper(
                            [
                                $this, $this->productWithHierarchy ? 'mapperWitHierarchy' : 'mapperWithoutHierarchy',
                            ]
                        );

        if (is_callable([$productGenerator, 'getMetaData'])) {
            $productGenerator->getMetaData()->setPlatform(
                'Prestashop',
                sprintf('%s-module:%s-php:%s', _PS_VERSION_, $this->module->version, phpversion())
            );
        }

        $limit = 100;
        $nb_iteration = ceil((new ShoppingfeedPreloading())->getPreloadingCount($this->sfToken['id_shoppingfeed_token']) / $limit);
        ob_end_clean();
        $productGenerator->open();

        for ($i = 0; $i < $nb_iteration; ++$i) {
            $products = (new ShoppingfeedPreloading())->findAllByTokenId($this->sfToken['id_shoppingfeed_token'], $i * $limit, $limit);
            if ($this->productWithHierarchy) {
                $productGenerator->appendProduct($products);
                continue;
            }
            $productsToAppend = [];
            foreach ($products as $product) {
                $productsToAppend[] = $product;
                foreach ($product['variations'] as $variation) {
                    $productsToAppend[] = array_merge($product, $variation);
                }
            }
            $productGenerator->appendProduct($productsToAppend);
        }

        $productGenerator->close();

        ProcessLoggerHandler::logSuccess(
            sprintf('Generate xml for token %s', $this->sfToken['id_shoppingfeed_token']),
            'shoppingfeed_token',
            $this->sfToken['id_shoppingfeed_token'],
            'ShoppingfeedProductModuleFrontController'
        );
        ProcessLoggerHandler::closeLogger();

        if ($this->isCompressFeed) {
            Tools::redirect(
                $this->getBaseLink($this->sfToken['id_shop']) . $fileXmlGz,
                __PS_BASE_URI__,
                null,
                ['HTTP/1.1 302 Moved Temporarily']
            );
        }

        exit;
    }

    private function getBaseLink($id_shop)
    {
        $ssl_enable = (bool) Configuration::get('PS_SSL_ENABLED');
        $shop = new Shop($id_shop);
        $base = (($ssl_enable) ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);

        return $base . $shop->getBaseURI();
    }

    public function mapperWithoutHierarchy(array $item, Product $product)
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
    }

    public function mapperWitHierarchy(array $item, Product $product)
    {
        $this->mapperWithoutHierarchy($item, $product);
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

    protected function isEcotaxEnabled()
    {
        return (bool) Configuration::get('PS_USE_ECOTAX');
    }
}
