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

namespace ShoppingfeedAddon\CarrierRule;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Weight of an imported order, in PS_WEIGHT_UNIT
 */
class OrderWeightCalculator
{
    /**
     * Sum of (unit weight x quantity). The unit weight of a combination is the
     * product weight plus the combination weight (an impact, not an absolute
     * value). An item not mapped to a PrestaShop product weighs 0.
     *
     * @param array $items items with ->reference and ->quantity (\ShoppingfeedAddon\OrderImport\OrderItemData)
     * @param \Product[] $prestashopProducts indexed by item reference
     *
     * @return float
     */
    public function calculate(array $items, array $prestashopProducts)
    {
        $weight = 0.0;

        foreach ($items as $item) {
            if (empty($prestashopProducts[$item->reference])) {
                continue;
            }

            $weight += $this->getUnitWeight($prestashopProducts[$item->reference]) * (int) $item->quantity;
        }

        return $weight;
    }

    /**
     * @param \Product $product
     *
     * @return float
     */
    protected function getUnitWeight(\Product $product)
    {
        $weight = (float) $product->weight;

        /* @phpstan-ignore-next-line id_product_attribute is set by Shoppingfeed::mapPrestashopProduct() */
        if (!empty($product->id_product_attribute)) {
            /* @phpstan-ignore-next-line */
            $combination = new \Combination((int) $product->id_product_attribute);
            $weight += (float) $combination->weight;
        }

        return $weight;
    }
}
