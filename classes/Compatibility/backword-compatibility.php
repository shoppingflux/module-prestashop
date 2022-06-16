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
include_once dirname(__FILE__) . '/../../../../config/config.inc.php';

if (version_compare(phpversion(), '7', '<')) {
    if (false === class_exists('Throwable')) {
        class Throwable extends Exception
        {
        }
    }
}

if (version_compare(_PS_VERSION_, '1.7.7', '<')) {
    require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/Compatibility/PriceFormatter.php';
    require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/Compatibility/SpecificPriceFormatter.php';
}
