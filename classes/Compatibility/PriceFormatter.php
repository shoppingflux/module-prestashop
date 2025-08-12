<?php

/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

use ShoppingfeedAddon\Services\SfTools;

if (!defined('_PS_VERSION_')) {
    exit;
}
/**
 * Format a price depending on locale and currency.
 */
class PriceFormatter
{
    /**
     * @param float $price
     * @param array|int|null $currency
     *
     * @return float
     */
    public function convertAmount($price, $currency = null)
    {
        return (float) Tools::convertPrice($price, $currency);
    }

    /**
     * @param float $price
     * @param array|int|null $currency
     *
     * @return string
     */
    public function format($price, $currency = null)
    {
        return (new SfTools())->displayPrice($price, $currency);
    }

    /**
     * @param float $price
     *
     * @return string
     */
    public function convertAndFormat($price)
    {
        return $this->format($this->convertAmount($price));
    }
}
