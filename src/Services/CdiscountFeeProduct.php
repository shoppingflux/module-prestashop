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

namespace ShoppingfeedAddon\Services;

use Configuration;
use Db;
use Language;
use Product;
use Shoppingfeed;
use Validate;

class CdiscountFeeProduct
{
    protected $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function getProduct()
    {
        $product = new Product(Configuration::get(Shoppingfeed::CDISCOUNT_FEE_PRODUCT));

        if (Validate::isLoadedObject($product)) {
            return $product;
        }

        return $this->createProduct();
    }

    protected function createProduct()
    {
        $product = new Product();
        $product->active = true;
        $product->name = [];

        foreach (Language::getLanguages(false) as $lang) {
            if ($lang['iso_code'] == 'fr') {
                $product->name[$lang['id_lang']] = 'Frais CDiscount';
            } else {
                $product->name[$lang['id_lang']] = 'CDiscount Fees';
            }
        }

        $product->visibility = 'none';
        $product->depends_on_stock = 1; //do not depend on stock
        $product->available_for_order = true;
        $product->reference = $this->getReference();
        $product->save();

        Configuration::updateValue(Shoppingfeed::CDISCOUNT_FEE_PRODUCT, $product->id);

        return $product;
    }

    public function removeProduct()
    {
        $product = new Product(Configuration::get(Shoppingfeed::CDISCOUNT_FEE_PRODUCT));

        if (Validate::isLoadedObject($product)) {
            $product->delete();
        }

        return Configuration::deleteByName(Shoppingfeed::CDISCOUNT_FEE_PRODUCT);
    }

    protected function getReference()
    {
        return 'FDG-ShoppingFlux';
    }
}
