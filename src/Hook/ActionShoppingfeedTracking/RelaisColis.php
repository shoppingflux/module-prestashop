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
namespace ShoppingfeedAddon\Hook\ActionShoppingfeedTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}


use Carrier;
use ShoppingfeedAddon\Services\CarrierFinder;
use ShoppingfeedClasslib\Hook\AbstractHook;

class RelaisColis extends AbstractHook
{
    const AVAILABLE_HOOKS = [
        'actionShoppingfeedTracking',
    ];

    public function actionShoppingfeedTracking($params)
    {
        if ($this->isRelaisColisEnabled() == false) {
            return;
        }

        if (empty($params[0]['order']) || $params[0]['order'] instanceof \Order == false) {
            return;
        }

        if (empty($params[0]['taskOrderPayload']['tracking_number'])) {
            return;
        }

        /**
         * @var \Carrier $carrier
         * @var \Order $order
         */
        $order = $params[0]['order'];
        $carrier = $this->initCarrierFinder()->findByOrder($order);
        // Getting a tracking number for relaiscolis carrier
        if ($carrier->external_module_name == 'relaiscolis' && class_exists(\RelaisColisOrder::class)) {
            $relaisColisOrder = $this->getRelaisColisOrderFromPsOrder($order);

            if (\Validate::isLoadedObject($relaisColisOrder)) {
                $params[0]['taskOrderPayload']['tracking_number'] = \Tools::substr($relaisColisOrder->pdf_number, 2, 10);
            }
        }
    }

    protected function getRelaisColisOrderFromPsOrder(\Order $order)
    {
        try {
            return new \RelaisColisOrder($this->getIdRelaisColisOrderFromPsOrder($order));
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getIdRelaisColisOrderFromPsOrder(\Order $order)
    {
        try {
            return (int) \RelaisColisOrder::getRelaisColisOrderId($order->id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function isRelaisColisEnabled()
    {
        $module = \Module::getInstanceByName('relaiscolis');

        if (false == \Validate::isLoadedObject($module)) {
            return false;
        }

        return $module->active;
    }

    protected function initCarrierFinder()
    {
        return new CarrierFinder();
    }
}
