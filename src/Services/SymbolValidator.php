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

if (!defined('_PS_VERSION_')) {
    exit;
}

class SymbolValidator
{
    /**
     * replace invalid symbol
     *
     * @param string $input
     * @param callable $callback validation rule. Must return bool
     * @param string $replaceSymbol for replace invalid symbol
     * @param bool $isWhitespaceIgnore if true, whitespace characters are always kept as-is
     *                                 instead of being run through $callback. Some validation
     *                                 callbacks (eg. Validate::isCustomerName()) assert on the
     *                                 whole string that it isn't entirely blank; applied to a
     *                                 single whitespace character in isolation, that assertion
     *                                 always fails and would wrongly flag the character as invalid.
     */
    public function validate(&$input, $callback, $replaceSymbol = '-', $isWhitespaceIgnore = false)
    {
        if (false == is_callable($callback)) { // @phpstan-ignore-line
            return;
        }

        if (call_user_func($callback, $input)) {
            return;
        }

        $tempInput = [];

        foreach (str_split($input) as $symbol) {
            if ($isWhitespaceIgnore && trim($symbol) === '') {
                $tempInput[] = $symbol;
                continue;
            }

            if (call_user_func($callback, $symbol)) {
                $tempInput[] = $symbol;
            } else {
                $tempInput[] = $replaceSymbol;
            }
        }

        $input = implode('', $tempInput);
    }
}
