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

namespace ShoppingfeedAddon\PrestaShopCloudSync;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CloudSyncView
{
    /** @var \ModuleCore|\Shoppingfeed */
    protected $module;
    /** @var \Context */
    protected $context;
    /** @var CloudSyncWrapper */
    protected $cloudSyncWrapper;

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('shoppingfeed');
        $this->context = \Context::getContext();
        $this->cloudSyncWrapper = new CloudSyncWrapper();
    }

    public function render()
    {
        if (false === $this->cloudSyncWrapper->areDependenciesMet()) {
            $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/prestashop_cloud_sync/cloud-sync-dependency.tpl');
            $tpl->assign('dependencies', $this->cloudSyncWrapper->getDependencies());

            return $tpl->fetch();
        }

        $eventbusPresenterService = $this->cloudSyncWrapper->getEventbusPresenterService();
        $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/prestashop_cloud_sync/cloud-sync.tpl');
        $tpl->assign('module_dir', _PS_MODULE_DIR_ . $this->module->name);
        $tpl->assign('urlAccountsCdn', $this->cloudSyncWrapper->getPsAccountsService()->getAccountsCdn());
        $tpl->assign('urlCloudsync', 'https://assets.prestashop3.com/ext/cloudsync-merchant-sync-consent/latest/cloudsync-cdc.js');
        $tpl->assign('contextPsAccounts', $this->cloudSyncWrapper->getPsAccountsPresenter()->present($this->module->name));
        $tpl->assign('contextPsEventbus', $eventbusPresenterService->expose($this->module, ['info', 'modules', 'themes']));

        return $tpl->fetch();
    }
}
