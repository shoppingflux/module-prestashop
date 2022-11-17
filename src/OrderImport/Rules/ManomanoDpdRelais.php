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

use Carrier;
use Cart;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\DpdAssociation;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedAddon\Services\CarrierFinder;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ManomanoDpdRelais extends RuleAbstract implements RuleInterface
{
    /** @var Module */
    protected $dpdfrance;

    const MODULE_NAME = 'dpdfrance';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $this->dpdfrance = Module::getInstanceByName(self::MODULE_NAME);

        if (false == Validate::isLoadedObject($this->dpdfrance) || false == $this->dpdfrance->active) {
            return false;
        }

        if ('monechelle' !== Tools::strtolower($apiOrder->getChannel()->getName())
            && 'manomanopro' !== Tools::strtolower($apiOrder->getChannel()->getName())) {
            return false;
        }

        if (empty($this->getRelayIdFromOrder($apiOrder)) === true) {
            return false;
        }

        if (false == $this->isDpdCarrier($apiOrder)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Retrieves the relay ID and fill in the table ”dpdfrance_shipping” as expected by ”DPD France” Addons.', 'ManomanoDpdRelais');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from ManoMano with ”DPD Relay”', 'ManomanoDpdRelais');
    }

    /**
     * We have to do this on pre process, as the fields may be used in various
     * steps
     *
     * @param array $params
     */
    public function onPreProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];

        $orderData->shippingAddress['firstName'] = trim($orderData->shippingAddress['firstName']);
        $orderData->shippingAddress['lastName'] = trim($orderData->shippingAddress['lastName']);

        $fullname = $orderData->shippingAddress['firstName'];
        $relayID = $this->getRelayIdFromOrder($apiOrder);
        $orderData->shippingAddress['other'] = $relayID;
        $orderData->shippingAddress['company'] = $orderData->shippingAddress['lastName'] . ' (' . $relayID . ')';

        $explodedFullname = explode(' ', $fullname);
        $orderData->shippingAddress['firstName'] = array_shift($explodedFullname);
        $orderData->shippingAddress['lastName'] = implode(' ', $explodedFullname);
    }

    public function afterCartCreation($params)
    {
        if (false == isset($params['cart'])) {
            return false;
        }

        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false == $cart instanceof Cart) {
            return false;
        }

        $carrier = new Carrier($cart->id_carrier);

        if ($carrier->external_module_name != self::MODULE_NAME) {
            return false;
        }

        $apiOrder = $params['apiOrder'];
        $relayID = $this->getRelayIdFromOrder($apiOrder);

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ManomanoDpdRelais'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (empty($relayID)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered. No relay ID found in shipping address \'RelayId\' field', 'ManomanoDpdRelais')
            );

            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                $this->l('Rule triggered. Id Relay : %s', 'ManomanoDpdRelais'),
                $relayID
            )
        );

        if (false == $this->associateWithDpd($cart, $relayID)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Failed to associate an order with dpdfrance module', 'ManomanoDpdRelais')
            );

            return false;
        }

        return true;
    }

    protected function associateWithDpd(Cart $cart, $relayID)
    {
        return $this->getDpdAssociation()->create($cart, $relayID);
    }

    protected function getDpdAssociation()
    {
        return new DpdAssociation();
    }

    protected function getRelayIdFromOrder(OrderResource $apiOrder)
    {
        $address = $apiOrder->getShippingAddress();

        if (false == empty($address['other'])) {
            return $address['other'];
        }

        if (false == empty($address['relayID'])) {
            return $address['relayID'];
        }

        return '';
    }

    protected function isDpdCarrier(OrderResource $apiOrder)
    {
        $channelName = $apiOrder->getChannel()->getName() ? $apiOrder->getChannel()->getName() : '';
        $apiCarrierName = empty($apiOrder->getShipment()['carrier']) ? '' : $apiOrder->getShipment()['carrier'];
        $carrier = $this->initCarrierFinder()->getCarrierForOrderImport($channelName, $apiCarrierName);

        if (false == Validate::isLoadedObject($carrier)) {
            return false;
        }

        return $carrier->external_module_name = self::MODULE_NAME;
    }

    protected function initCarrierFinder()
    {
        return new CarrierFinder();
    }
}
