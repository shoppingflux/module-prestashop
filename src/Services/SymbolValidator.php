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

class SymbolValidator
{
    /**
     * replace invalid symbol
     *
     * @param string $input
     * @param callable $callback validation rule. Must return bool
     * @param string $replaceSymbol for replace invalid symbol
     */
    public function validate(&$input, $callback, $replaceSymbol = '-')
    {
        if (false == is_callable($callback)) {
            return;
        }

        if (call_user_func($callback, $input)) {
            return;
        }

        $tempInput = [];

        foreach (str_split($input) as $symbol) {
            if (call_user_func($callback, $symbol)) {
                $tempInput[] = $symbol;
            } else {
                $tempInput[] = $replaceSymbol;
            }
        }

        $input = implode('', $tempInput);
    }
}
