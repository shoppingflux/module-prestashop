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

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingfeedClasslib\Utils\Translate\TranslateTrait;

class AttributeFilter implements Filter
{
    use TranslateTrait;

    protected $attribute;

    protected $attributeGroup;

    public function __construct($id)
    {
        $this->attribute = new \Attribute($id);
        $this->attributeGroup = new \AttributeGroup($this->attribute->id_attribute_group, \Context::getContext()->language->id);
    }

    public function getSqlChunk()
    {
        return '(ps.id_product IN (
            SELECT id_product 
                FROM ' . _DB_PREFIX_ . 'product_attribute pa 
                JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute 
                WHERE pac.id_attribute = ' . (int) $this->attribute->id . ')
            )';
    }

    public function getFilter()
    {
        return json_encode(['attribute' => $this->attribute->id]);
    }

    public function getType()
    {
        return $this->l('Attribute', 'AttributeFilter');
    }

    public function getValue()
    {
        return sprintf(
            '%s:%s',
            $this->attributeGroup->name,
            is_array($this->attribute->name) ? $this->attribute->name[\Context::getContext()->language->id] : $this->attribute->name
        );
    }
}
