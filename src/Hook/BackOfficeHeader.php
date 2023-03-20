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

use Context;
use ShoppingfeedAddon\Services\CarrierFinder;
use ShoppingfeedClasslib\Hook\AbstractHook;
use Validate;

class BackOfficeHeader extends AbstractHook
{
    const AVAILABLE_HOOKS = [
        'displayBackOfficeHeader',
    ];

    protected $carrierFinder;

    public function __construct($module)
    {
        parent::__construct($module);

        $this->carrierFinder = new CarrierFinder();
    }

    public function displayBackOfficeHeader($params)
    {
        $carrier = $this->carrierFinder->findProductFeedCarrier();

        if (false == Validate::isLoadedObject($carrier)) {
            Context::getContext()->controller->errors[] = $this->module->l('Be careful, the choice of carrier for the import of shipping costs in the source feed is not filled in (see the "Products feed" tab)', 'BackOfficeHeader');
        }
    }
}
