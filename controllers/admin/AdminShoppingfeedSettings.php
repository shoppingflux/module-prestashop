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


/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedSettingsController extends ModuleAdminController
{
    public $bootstrap = true;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';
        parent::__construct();
        $this->fields_options = array(
            'general' => array(
                'title' => $this->trans('Configuration', array(), 'Admin.Advparameters.Feature'),
                'icon' => 'icon-home',
                'fields' => array(
                    Shoppingfeed::PRODUCT_SYNC_BY_DATE_UPD => array(
                        'title' => $this->trans('Preloading table can be update by watch date update', array(), 'Admin.Advparameters.Feature'),
                        'cast' => 'boolval',
                        'type' => 'checkbox',
                        'choices' => [1 => ''],
                    ),
                ),
                'submit' => array('title' => $this->trans('Save', array(), 'Admin.Actions')),
            ),
        );
    }
}
