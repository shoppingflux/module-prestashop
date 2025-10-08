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

namespace ShoppingfeedAddon\OrderImport;

use ShoppingfeedAddon\Services\SfTools;

class DiscountProvider
{
    public function getForOrder($orderRef, $amount, $id_currency, $id_customer, $name)
    {
        $code = 'SF-' . (new SfTools())->hash(sprintf(
            '%s_%f_%d_%d_%s',
            (string) $orderRef,
            (float) $amount,
            (int) $id_customer,
            (int) $id_currency,
            (string) $name
        ));
        $id_lang = (int) \Configuration::get('PS_LANG_DEFAULT');
        $result = \CartRule::getCartsRuleByCode($code, $id_lang);

        if (false === empty($result)) {
            return new \CartRule($result[0]['id_cart_rule']);
        }

        $dateFrom = \DateTime::createFromFormat('U', sprintf('%d', time() - 600));
        $dateTo = \DateTime::createFromFormat('U', sprintf('%d', time() + 60 * 60 * 24 * 30));
        $cartRule = new \CartRule();
        $cartRule->name = [$id_lang => $name];
        $cartRule->description = 'For marketplace order ' . (string) $orderRef;
        $cartRule->code = $code;
        $cartRule->reduction_amount = (float) $amount;
        $cartRule->reduction_tax = true;
        $cartRule->id_customer = (int) $id_customer;
        $cartRule->minimum_amount = 0;
        $cartRule->active = true;
        $cartRule->quantity = 1;
        $cartRule->quantity_per_user = 1;
        $cartRule->date_from = $dateFrom->format('Y-m-d H:i:s');
        $cartRule->date_to = $dateTo->format('Y-m-d H:i:s');
        $cartRule->reduction_currency = (int) $id_currency;
        $cartRule->save();

        return $cartRule;
    }
}
