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

namespace ShoppingfeedAddon\Hook;

use Configuration;
use ShoppingfeedClasslib\Hook\AbstractHook;

class Diagnostics extends AbstractHook
{
    const AVAILABLE_HOOKS = [
        'actionShoppingfeedGetConflicts',
    ];

    public function actionShoppingfeedGetConflicts($params)
    {
        $recalculateShipping = (bool) Configuration::get('PS_ORDER_RECALCULATE_SHIPPING');
        $conflics = [];
        if ($recalculateShipping === true) {
            $conflics[] = $this->module->l(
                'Setting "Recalculate shipping costs after editing the order" is set to YES, this setting will change the amount of an order in case of update.',
                'Diagnostics'
            );
        }

        $isHookOverrided = (bool) file_exists(_PS_OVERRIDE_DIR_ . 'Hook.php');
        if ($isHookOverrided === true) {
            $conflics[] = $this->module->l(
                'Class hooks is probably overrided by a GDPR module (GDPR Pro, Cookie Concent, ...). Be careful to authorize or whitelist Shoppingfeed module on this GDPR module.',
                'Diagnostics'
            );
        }

        $isShoppingfluxexport = (bool) file_exists(_PS_MODULE_DIR_ . 'shoppingfluxexport');
        if ($isShoppingfluxexport === true) {
            $conflics[] = $this->module->l(
                'Older module Shoppingflux Export is available on your server. Please uninstall and remove the directory of the ',
                'Diagnostics'
            );
        }

        return $conflics;
    }
}
