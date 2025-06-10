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

class ShoppingfeedAdminController extends ModuleAdminController
{
    /**
     * Method is added for compatibility with PS 1.6
     *
     * @return array
     */
    public function getTemplateFormVars()
    {
        return $this->tpl_form_vars;
    }

    public function init()
    {
        parent::init();

        if (Shoppingfeed::isCatalogModeEnabled()) {
            $this->errors[] = $this->module->l('No order can be imported as long as Prestashop\'s catalog mode is activated', 'ShoppingfeedAdminController');
        }
    }
}
