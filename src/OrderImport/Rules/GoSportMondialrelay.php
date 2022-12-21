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

use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

class GoSportMondialrelay extends AbstractMondialrelay
{
    /** @var Module */
    protected $module_mondialRelay;

    public function isApplicable(OrderResource $apiOrder)
    {
        if (parent::isApplicable($apiOrder) === false) {
            return false;
        }
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        if (empty($apiOrderAdditionalFields['shipping_pudo_id'])
            || $apiOrderAdditionalFields['shipping_type_code']) {
            return false;
        }

        return true;
    }

    public function getRelayId($orderData)
    {
        if ($relayId = parent::getRelayId($orderData)) {
            return $relayId;
        }

        if (false === empty($orderData->additionalFields['shipping_pudo_id'])) {
            return $orderData->additionalFields['shipping_pudo_id'];
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Mondial Relay and has non-empty "shipping_pudo_id" and "shipping_type_code" additional fields.', 'Mondial Relay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
