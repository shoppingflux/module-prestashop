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

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ChronopostRule extends RuleAbstract implements RuleInterface
{
    protected $chronopost = null;
    protected $logprefix = '';

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->chronopost = \Module::getInstanceByName('chronopost');
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'Chronopost'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        return \Validate::isLoadedObject($this->chronopost)
            && $this->chronopost->active
            && method_exists($this->chronopost, 'isRelais')
            && !empty($this->getRelayId($apiOrder));
    }

    public function afterCartCreation($params)
    {
        $cart = $params['cart'];

        if (false === $cart instanceof \Cart) {
            return;
        }
        if (false === call_user_func([$this->chronopost, 'isRelais'], $cart->id_carrier)) {
            return;
        }
        /** @var OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];
        $relayId = $this->getRelayId($apiOrder);

        \Db::getInstance()->insert(
            'chrono_cart_relais',
            [
                'id_cart' => $cart->id,
                'id_pr' => $relayId,
            ]
        );

        ProcessLoggerHandler::logInfo(
            $this->logPrefix . $this->l('Saving Chronopost pickup point: ', 'Chronopost') . $relayId,
            'Cart',
            $cart->id
        );
    }

    public function getDescription()
    {
        return $this->l('Set the carrier to Chronopost and add necessary data in the chronopost module accordingly.', 'Chronopost');
    }

    public function getConditions()
    {
        return $this->l('If the order a point relay id is sent and a carrier belongs the module "chronopost"', 'Chronopost');
    }

    protected function getRelayId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (false === empty($apiOrderData['shippingAddress']['relayId'])) {
            return $apiOrderData['shippingAddress']['relayId'];
        }
        if (false === empty($apiOrderData['shippingAddress']['other'])) {
            return $apiOrderData['shippingAddress']['other'];
        }
        if (false === empty($apiOrderData['shippingAddress']['relayID'])) {
            return $apiOrderData['shippingAddress']['relayID'];
        }
        if (false === empty($apiOrderData['additionalFields']['pickup_point_id'])) {
            return $apiOrderData['additionalFields']['pickup_point_id'];
        }
        if (false === empty($apiOrderData['additionalFields']['shippingRelayId'])) {
            return $apiOrderData['additionalFields']['shippingRelayId'];
        }
        if (false === empty($apiOrderData['additionalFields']['relais-id'])) {
            return $apiOrderData['additionalFields']['relais-id'];
        }
        if (false === empty($apiOrderData['additionalFields']['shipping_pudo_id'])) {
            return $apiOrderData['additionalFields']['shipping_pudo_id'];
        }
        if (false === empty($apiOrderData['additionalFields']['service_point_id'])) {
            return $apiOrderData['additionalFields']['service_point_id'];
        }

        return '';
    }
}
