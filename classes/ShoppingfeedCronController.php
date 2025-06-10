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

use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Front\CronController;

if (!defined('_PS_VERSION_')) {
    exit;
}


class ShoppingfeedCronController extends CronController
{
    /**
     * Added for a compatibility with PS 1.6
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @throws PrestaShopException
     */
    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        $this->ajaxRender($value, $controller, $method);
        exit;
    }

    /**
     * Added for a compatibility with PS 1.6
     *
     * @param null $value
     * @param null $controller
     * @param null $method
     *
     * @throws PrestaShopException
     */
    protected function ajaxRender($value = null, $controller = null, $method = null)
    {
        if ($controller === null) {
            $controller = get_class($this);
        }

        if ($method === null) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $method = $bt[1]['function'];
        }

        /* @deprecated deprecated since 1.6.1.1 */
        Hook::exec('actionAjaxDieBefore', ['controller' => $controller, 'method' => $method, 'value' => $value]);

        /*
         * @deprecated deprecated since 1.6.1.1
         * use 'actionAjaxDie'.$controller.$method.'Before' instead
         */
        Hook::exec('actionBeforeAjaxDie' . $controller . $method, ['value' => $value]);
        Hook::exec('actionAjaxDie' . $controller . $method . 'Before', ['value' => $value]);
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

        echo $value;
    }
}
