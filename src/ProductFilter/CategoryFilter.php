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

class CategoryFilter implements Filter
{
    use TranslateTrait;

    protected $category;

    public function __construct($id)
    {
        $this->category = new \Category($id, \Context::getContext()->language->id);
    }

    public function getSqlChunk()
    {
        return '(ps.id_category_default = ' . (int) $this->category->id . ')';
    }

    public function getFilter()
    {
        return json_encode(['category' => $this->category->id]);
    }

    public function getType()
    {
        return $this->l('Category', 'CategoryFilter');
    }

    public function getValue()
    {
        return sprintf(
            '(%d) %s',
            (int) $this->category->id,
            $this->category->name
        );
    }
}
