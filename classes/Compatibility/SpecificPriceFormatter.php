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
if (!defined('_PS_VERSION_')) {
    exit;
}

class SpecificPriceFormatter
{
    /**
     * Calculation method to be used (tax included or not?)
     *
     * @var bool
     */
    private $isTaxIncluded;

    /**
     * Specific price data array
     *
     * @var array
     */
    private $specificPrice;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var bool
     */
    private $displayDiscountPrice;

    /**
     * SpecificPriceFormatter constructor.
     *
     * @param array $specificPrice
     * @param bool $isTaxIncluded
     * @param Context $context
     */
    public function __construct($specificPrice, $isTaxIncluded, Currency $currency, $displayDiscountPrice)
    {
        $this->specificPrice = $specificPrice;
        $this->isTaxIncluded = $isTaxIncluded;
        $this->currency = $currency;
        $this->displayDiscountPrice = $displayDiscountPrice;
    }

    /**
     * This is legacy code extracted from ProductController and it should be refactored
     *
     * @param float $initialPrice
     * @param float $tax_rate
     * @param float $ecotax_amount
     *
     * @return array
     */
    public function formatSpecificPrice($initialPrice, $tax_rate, $ecotax_amount)
    {
        $priceFormatter = new PriceFormatter();

        $this->specificPrice['quantity'] = &$this->specificPrice['from_quantity'];
        if ($this->specificPrice['price'] >= 0) {
            // The price may be directly set

            /** @var float $currentPriceDefaultCurrency current price with taxes in default currency */
            $currentPriceDefaultCurrency = $this->calculateSpecificPrice(
                $this->specificPrice['price'],
                (bool) $this->specificPrice['reduction_tax'],
                $tax_rate,
                $ecotax_amount
            );

            // Since this price is set in default currency,
            // we need to convert it into current currency
            $this->specificPrice['id_currency'];
            $currentPriceCurrentCurrency = Tools::convertPrice($currentPriceDefaultCurrency, $this->currency, true);

            if ($this->specificPrice['reduction_type'] == 'amount') {
                $currentPriceCurrentCurrency -= ($this->specificPrice['reduction_tax'] ? $this->specificPrice['reduction'] : $this->specificPrice['reduction'] / (1 + $tax_rate / 100));
                $this->specificPrice['reduction_with_tax'] = $this->specificPrice['reduction_tax'] ? $this->specificPrice['reduction'] : $this->specificPrice['reduction'] / (1 + $tax_rate / 100);
            } else {
                $currentPriceCurrentCurrency *= 1 - $this->specificPrice['reduction'];
            }
            $this->specificPrice['real_value'] = $initialPrice > 0 ? $initialPrice - $currentPriceCurrentCurrency : $currentPriceCurrentCurrency;
            $discountPrice = $initialPrice - $this->specificPrice['real_value'];

            if ($this->displayDiscountPrice) {
                if ($this->specificPrice['reduction_tax'] == 0 && !$this->specificPrice['price']) {
                    $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - ($initialPrice * $this->specificPrice['reduction_with_tax']));
                } else {
                    $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - $this->specificPrice['real_value']);
                }
            } else {
                $this->specificPrice['discount'] = $priceFormatter->format($this->specificPrice['real_value']);
            }
        } else {
            if ($this->specificPrice['reduction_type'] == 'amount') {
                if ($this->isTaxIncluded) {
                    $this->specificPrice['real_value'] = $this->specificPrice['reduction_tax'] == 1 ? $this->specificPrice['reduction'] : $this->specificPrice['reduction'] * (1 + $tax_rate / 100);
                } else {
                    $this->specificPrice['real_value'] = $this->specificPrice['reduction_tax'] == 0 ? $this->specificPrice['reduction'] : $this->specificPrice['reduction'] / (1 + $tax_rate / 100);
                }
                $this->specificPrice['reduction_with_tax'] = $this->specificPrice['reduction_tax'] ? $this->specificPrice['reduction'] : $this->specificPrice['reduction'] + ($this->specificPrice['reduction'] * $tax_rate) / 100;
                $discountPrice = $initialPrice - $this->specificPrice['real_value'];
                if ($this->displayDiscountPrice) {
                    if ($this->specificPrice['reduction_tax'] == 0 && !$this->specificPrice['price']) {
                        $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - ($initialPrice * $this->specificPrice['reduction_with_tax']));
                    } else {
                        $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - $this->specificPrice['real_value']);
                    }
                } else {
                    $this->specificPrice['discount'] = $priceFormatter->format($this->specificPrice['real_value']);
                }
            } else {
                $this->specificPrice['real_value'] = $this->specificPrice['reduction'] * 100;
                $discountPrice = $initialPrice - $initialPrice * $this->specificPrice['reduction'];
                if ($this->displayDiscountPrice) {
                    if ($this->specificPrice['reduction_tax'] == 0) {
                        $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - ($initialPrice * $this->specificPrice['reduction_with_tax']));
                    } else {
                        $this->specificPrice['discount'] = $priceFormatter->format($initialPrice - ($initialPrice * $this->specificPrice['reduction']));
                    }
                } else {
                    $this->specificPrice['discount'] = $this->specificPrice['real_value'] . '%';
                }
            }
        }

        $this->specificPrice['save'] = $priceFormatter->format(($initialPrice * $this->specificPrice['quantity']) - ($discountPrice * $this->specificPrice['quantity']));

        return $this->specificPrice;
    }

    /**
     * @param float $specificPriceValue
     * @param bool $specificPriceReductionTax
     * @param float $taxRate
     * @param float $ecoTaxAmount
     *
     * @return mixed
     */
    private function calculateSpecificPrice(
        $specificPriceValue,
        $specificPriceReductionTax,
        $taxRate,
        $ecoTaxAmount
    ) {
        $specificPrice = $specificPriceValue;

        // only apply tax rate when tax calculation method is set to PS_TAX_INC
        if ($this->isTaxIncluded && $specificPriceReductionTax) {
            $specificPrice = $specificPrice * (1 + $taxRate / 100) + (float) $ecoTaxAmount;
        }

        return $specificPrice;
    }
}
