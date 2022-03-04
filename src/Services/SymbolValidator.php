<?php

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
