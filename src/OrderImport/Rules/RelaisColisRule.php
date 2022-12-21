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

use Address;
use Cart;
use Configuration;
use Country;
use Module;
use Order;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedAddon\Services\IsoConvertor;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;
use Validate;

class RelaisColisRule extends RuleAbstract implements RuleInterface
{
    /** @var \Relaiscolis */
    protected $relaisColis;

    /**
     * @param OrderResource $apiOrder
     *
     * @return bool
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();
        $this->relaisColis = Module::getInstanceByName('relaiscolis');

        if (Validate::isLoadedObject($this->relaisColis) == false) {
            return false;
        }

        if (Tools::strtolower($apiOrderShipment['carrier']) != 'relais') {
            return false;
        }

        return true;
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

        $relaisColisCarriers = [
            (int) Configuration::getGlobalValue('RELAISCOLIS_ID'),
            (int) Configuration::getGlobalValue('RELAISCOLIS_ID_HOME'),
            (int) Configuration::getGlobalValue('RELAISCOLIS_ID_MAX'),
            (int) Configuration::getGlobalValue('RELAISCOLIS_ID_HOME_PLUS'),
        ];

        // Return if not Relais Colis
        if (false == in_array($cart->id_carrier, $relaisColisCarriers)) {
            return false;
        }

        $apiOrder = $params['apiOrder'];
        $idRelais = $this->getRelayId($params['orderData']);

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Shoppingfeed.Rule'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (empty($idRelais)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered. No relay ID found in shipping address \'Other\' field', 'Shoppingfeed.Rule')
            );

            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                $this->l('Rule triggered. Id Relay : %s', 'Shoppingfeed.Rule'),
                $idRelais
            )
        );

        $relaisColisInfo = $this->createRelaisColisInfo($cart, $idRelais);

        if (false == Validate::isLoadedObject($relaisColisInfo)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Failed to create a relais colis info object', 'Shoppingfeed.Rule')
            );

            return false;
        }

        return true;
    }

    public function onPostProcess($params)
    {
        if (false == isset($params['apiOrder'])) {
            return false;
        }

        if (false == isset($params['sfOrder'])) {
            return false;
        }

        /**
         * @var OrderResource $apiOrder
         * @var \ShoppingfeedOrder $sfOrder
         */
        $apiOrder = $params['apiOrder'];
        $sfOrder = $params['sfOrder'];
        $psOrder = new Order($sfOrder->id_order);
        $addressShipping = new Address($psOrder->id_address_delivery);
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Shoppingfeed.Rule'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (false == Validate::isLoadedObject($addressShipping)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered. Invalid a shipping address', 'Shoppingfeed.Rule')
            );

            return false;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Updating a shipping address', 'Shoppingfeed.Rule')
        );

        $sfAddressShipping = $apiOrder->getShippingAddress();
        $sfAddressBilling = $apiOrder->getBillingAddress();
        $addressShipping->lastname = $sfAddressShipping['lastName'];
        $addressShipping->address1 = $sfAddressBilling['lastName'] . ' ' . $sfAddressBilling['firstName'];
        $addressShipping->address2 = Tools::substr($sfAddressShipping['street'], 0, 128);

        return $addressShipping->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Relais Colis module\'s table.', 'RelaisColisRule');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order has \'Laredoute\' in its marketplace name.', 'RelaisColisRule');
    }

    /**
     * @return \RelaisColisInfo
     */
    protected function createRelaisColisInfo(Cart $cart, $idRelais)
    {
        if (version_compare(_PS_VERSION_, '1.7.2.5', '>=')) {
            $deliveryOption = json_decode($cart->delivery_option, true);
        } else {
            $deliveryOption = unserialize($cart->delivery_option);
        }

        if (false == is_array($deliveryOption) || empty($deliveryOption)) {
            return new \RelaisColisInfo();
        }

        $addresses = array_keys($deliveryOption);
        $idDeliveryAddress = (int) array_pop($addresses);
        $address = new Address($idDeliveryAddress);
        $isoCountry = $this->getIsoConvertor()->toISO3(Country::getIsoById($address->id_country));

        $relaisColisInfo = new \RelaisColisInfo();
        $relaisColisInfo->id_cart = $cart->id;
        $relaisColisInfo->id_customer = $cart->id_customer;
        $relaisColisInfo->rel = $idRelais;
        $relaisColisInfo->selected_date = date('Y-m-d');
        $relaisColisInfo->rel_adr = $address->address1;
        $relaisColisInfo->rel_cp = $address->postcode;
        $relaisColisInfo->rel_vil = $address->city;
        $relaisColisInfo->fcod_pays = $isoCountry;
        $relaisColisInfo->rel_name = $address->lastname;

        try {
            $relaisColisInfo->save();
        } catch (\Throwable $e) {
            return new \RelaisColisInfo();
        }

        return $relaisColisInfo;
    }

    protected function getIsoConvertor()
    {
        return new IsoConvertor();
    }

    protected function getRelayId(OrderData $orderData)
    {
        if (false === empty($orderData->shippingAddress['relayId'])) {
            return $orderData->shippingAddress['relayId'];
        }

        if (false === empty($orderData->shippingAddress['other'])) {
            return $orderData->shippingAddress['other'];
        }

        return '';
    }
}
