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
use ShoppingfeedClasslib\Registry;

/**
 * RDC is using RelayID from the delivery adresse and Other for the order number
 * instead of the relay ID.
 * The relay ID is located in the ShippingMethod
 * Therefore we need to extract the relay ID from the ShippingMethod and then
 * rebuild the ShippingMethod without the relay ID
 */
class RueducommerceMondialrelay extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();

        if (preg_match('#^rdc|rueducommerce$#', \Tools::strtolower($apiOrder->getChannel()->getName()))
            && preg_match('#livraison en point de proximité avec .+#', \Tools::strtolower($apiOrderShipment['carrier']))
        ) {
            // If the rule is applicable, we'll make sure this is empty, just in case...
            Registry::set(self::class . '_mondialRelayId', '');

            return true;
        }

        return false;
    }

    /**
     * Updates the carrier name before it's used
     *
     * @param array $params
     */
    public function onPreProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'RueducommerceMondialrelay'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';

        $len = \Tools::strlen('livraison en point de proximité avec ');
        $orderData->shipment['carrier'] = \Tools::substr($orderData->shipment['carrier'], $len);

        // Split the carrier name
        $explodedCarrier = explode(' ', $orderData->shipment['carrier']);
        // Remove the relay ID
        $mondialRelayID = array_pop($explodedCarrier);

        // Rebuild the carrier name; it should be found properly
        $orderData->shipment['carrier'] = implode(' ', $explodedCarrier);

        // Save the relay ID in the shipping address "Other" field so it can be
        // used by the main Mondial Relay rule
        // See ShoppingfeedAddon\OrderImport\Rules\Mondialrelay
        $orderData->shippingAddress['other'] = $mondialRelayID;

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Shipping address updated to set MR relay ID.', 'RueducommerceMondialrelay'),
            'Order'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from Rue du Commerce and has \'Mondial Relay\' in its carrier name.', 'RueducommerceMondialrelay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Removes the relay ID from the carrier name. Sets it in the proper field to be used by the main Mondial Relay rule.', 'RueducommerceMondialrelay');
    }
}
