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

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use ShoppingfeedPrefix\Prestashop\ModuleLibMboInstaller\DependencyBuilder;
use ShoppingfeedPrefix\PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use ShoppingfeedPrefix\PrestaShop\PsAccountsInstaller\Installer\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CloudSyncWrapper
{
    /** @var ModuleManager */
    protected $moduleManager;
    /** @var Installer */
    protected $accountInstaller;
    /** @var DependencyBuilder */
    protected $mboInstaller;

    public function __construct()
    {
        $this->moduleManager = ModuleManagerBuilder::getInstance()->build();
        $this->accountInstaller = new Installer('5.0');
        $this->mboInstaller = new DependencyBuilder(\Module::getInstanceByName('shoppingfeed'));
    }

    public function getPsAccountsService()
    {
        return (new PsAccounts($this->accountInstaller))->getPsAccountsService();
    }

    public function getPsAccountsPresenter()
    {
        return (new PsAccounts($this->accountInstaller))->getPsAccountsPresenter();
    }

    public function getEventbusPresenterService()
    {
        $eventbusModule = \Module::getInstanceByName('ps_eventbus');

        return call_user_func([$eventbusModule, 'getService'], 'PrestaShop\Module\PsEventbus\Service\PresenterService');
    }

    public function areDependenciesMet()
    {
        return $this->mboInstaller->areDependenciesMet();
    }

    public function getDependencies()
    {
        return $this->mboInstaller->handleDependencies();
    }
}
