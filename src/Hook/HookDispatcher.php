<?php
/**
 *
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
 *
 */

namespace ShoppingfeedAddon\Hook;

use ShoppingfeedAddon\Hook\ActionShoppingfeedTracking\RelaisColis;
use ShoppingfeedClasslib\Hook\AbstractHookDispatcher;

class HookDispatcher extends AbstractHookDispatcher
{
    protected $hookClasses = [
        RelaisColis::class
    ];

    public function dispatch($hookName, $params = [])
    {
        $hookName = preg_replace('~^hook~', '', $hookName);
        $hookName = lcfirst($hookName);
        $result = false;

        if (!empty($hookName)) {
            foreach ($this->hooks as $hook) {
                if (is_callable([$hook, $hookName])) {
                    $hookResult = call_user_func([$hook, $hookName], $params);

                    if ($hookResult) {
                        $result = $hookResult;
                    }
                }
            }
        }

        return $result;
    }
}
