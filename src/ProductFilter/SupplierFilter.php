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

namespace ShoppingfeedAddon\ProductFilter;

use ShoppingfeedClasslib\Utils\Translate\TranslateTrait;

class SupplierFilter implements Filter
{
    use TranslateTrait;

    protected $supplier;

    public function __construct($id)
    {
        $this->supplier = new \Supplier($id, \Context::getContext()->language->id);
    }

    public function getSqlChunk()
    {
        return '(ps.id_product IN (
            SELECT id_product 
            FROM ' . _DB_PREFIX_ . 'product_supplier 
            WHERE id_supplier = ' . (int) $this->supplier->id . ')
        )';
    }

    public function getFilter()
    {
        return json_encode(['supplier' => $this->supplier->id]);
    }

    public function getType()
    {
        return $this->l('Supplier', 'SupplierFilter');
    }

    public function getValue()
    {
        return $this->supplier->name;
    }
}
