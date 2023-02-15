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

use Module;
use Validate;

class Adapter implements AdapterInterface
{
    /** @var \NkmGls*/
    protected $glsModule;

    /** @var \Nukium\GLS\Legacy\GlsController*/
    protected $gls;

    public function __construct()
    {
        $glsModule = Module::getInstanceByName('nkmgls');

        if (false == Validate::isLoadedObject($glsModule)) {
            return;
        }

        $this->glsModule = $glsModule;
        $this->gls = \Nukium\GLS\Legacy\GlsController::createInstance($this->glsModule->getConfigFormValues());
    }

    public function getRelayDetail($relayId)
    {
        if (!$this->gls) {
            return [];
        }

        return $this->gls->getRelayDetail($relayId);
    }

    public function getGlsProductCode($idCarrier, $countryCode = 'FR')
    {
        if (!$this->glsModule) {
            return false;
        }

        return $this->glsModule->getGlsProductCode($idCarrier, $countryCode);
    }
}