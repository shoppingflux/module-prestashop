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

use Context;
use Manufacturer;

class BrandFilter implements Filter
{
    protected $manufacturer;

    protected $translator;

    public function __construct($id)
    {
        $this->manufacturer = new Manufacturer($id, Context::getContext()->language->id);
        $this->translator = Context::getContext()->getTranslator();
    }

    public function getSqlChunk()
    {
        return '(ps.id_product IN (select id_product from ' . _DB_PREFIX_ . 'product where id_manufacturer = ' . (int) $this->manufacturer->id . '))';
    }

    public function getFilter()
    {
        return json_encode(['brand' => $this->manufacturer->id]);
    }

    public function getType()
    {
        return $this->translator->trans('Brand', [], 'BrandFilter');
    }

    public function getValue()
    {
        return $this->manufacturer->name;
    }
}
