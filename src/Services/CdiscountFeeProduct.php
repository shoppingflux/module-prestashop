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
use Validate;

class CdiscountFeeProduct
{
    const PRODUCT = 'SHOPPINGFEED_CDISCOUNT_FEE_PRODUCT';

    protected $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function getProduct()
    {
        $product = new Product(Configuration::get(self::PRODUCT));

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
        $product->mpn = $this->getReference();
        $product->reference = $this->getReference();
        $product->ean13 = $this->getEan13();
        $product->isbn = $this->getIsbn();
        $product->upc = $this->getUpc();
        $product->save();

        $this->db->insert(
            'product_supplier',
            [
                'id_product' => $product->id,
                'id_supplier' => 1,
                'product_supplier_reference' => $product->reference,
            ],
            false,
            true,
            Db::REPLACE
        );
        Configuration::updateValue(self::PRODUCT, $product->id);

        return $product;
    }

    public function removeProduct()
    {
        $product = new Product(Configuration::get(self::PRODUCT));

        if (Validate::isLoadedObject($product)) {
            $product->delete();
        }

        return Configuration::deleteByName(self::PRODUCT);
    }

    protected function getReference()
    {
        return 'sf-cdiscount-fee-product-ref';
    }

    protected function getEan13()
    {
        return '1234567890123';
    }

    protected function getIsbn()
    {
        return '12345678901234567890123456789012';
    }

    protected function getUpc()
    {
        return '123456789012';
    }
}
