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

namespace ShoppingfeedAddon\Services;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SfTools
{
    public function hash($string)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            return call_user_func([\Tools::class, 'encrypt'], $string);
        } else {
            return \Tools::hash($string);
        }
    }

    public function str2url($str)
    {
        if (version_compare(_PS_VERSION_, '8.1', '<')) {
            return call_user_func([\Tools::class, 'link_rewrite'], $str);
        } else {
            return call_user_func([\Tools::class, 'str2url'], $str);
        }
    }

    public function isInt($value)
    {
        return \Validate::isInt($value);
    }

    public function displayPrice($price, $currency = null, $no_utf8 = false, ?\Context $context = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            return call_user_func([\Tools::class, 'displayPrice'], $price, $currency, $no_utf8, $context);
        }

        if (!is_numeric($price)) {
            return $price;
        }

        $context = $context ?: \Context::getContext();
        $currency = $currency ?: $context->currency;

        if (is_int($currency)) {
            $currency = \Currency::getCurrencyInstance($currency);
        }
        /* @phpstan-ignore-next-line */
        $locale = \Tools::getContextLocale($context);
        $currencyCode = is_array($currency) ? $currency['iso_code'] : $currency->iso_code;

        return $locale->formatPrice($price, $currencyCode);
    }
}
