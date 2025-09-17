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

namespace ShoppingfeedAddon\OrderImport\GLS;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Adapter implements AdapterInterface
{
    /** @var \ModuleCore|null */
    protected $glsModule;

    protected $gls;

    public function __construct()
    {
        $glsModule = \Module::getInstanceByName('nkmgls');

        if (false == \Validate::isLoadedObject($glsModule)) {
            return;
        }
        if (version_compare($glsModule->version, '3.0.0') < 0) {
            return;
        }

        if (true == class_exists(\Nukium\GLS\Legacy\GlsController::class)) {
            $this->glsModule = $glsModule;
            $configs = call_user_func([$this->glsModule, 'getConfigFormValues']);
            $this->gls = call_user_func(['Nukium\GLS\Legacy\GlsController', 'createInstance'], $configs);
        } elseif (true == class_exists(\Nukium\GLS\Common\Legacy\GlsController::class)) {
            $this->glsModule = $glsModule;
            $configs = call_user_func([$this->glsModule, 'getConfigFormValues']);
            $this->gls = call_user_func(['Nukium\GLS\Common\Legacy\GlsController', 'createInstance'], $configs);
        }
    }

    public function getRelayDetail($relayId)
    {
        if (!$this->gls) {
            return [];
        }

        return call_user_func([$this->gls, 'getRelayDetail'], $relayId);
    }

    public function getGlsProductCode($idCarrier, $countryCode = 'FR')
    {
        if (!$this->glsModule) {
            return false;
        }

        return call_user_func([$this->glsModule, 'getGlsProductCode'], $idCarrier, $countryCode);
    }
}
