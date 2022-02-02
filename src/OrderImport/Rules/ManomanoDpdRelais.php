<?php

namespace ShoppingfeedAddon\OrderImport\Rules;

use Cart;
use Module;
use ShoppingfeedAddon\OrderImport\DpdAssociation;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
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

    /**
     * {@inheritdoc}
     */
    public function isApplicable(\ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder)
    {
        $this->dpdfrance = Module::getInstanceByName('dpdfrance');

        if (false == Validate::isLoadedObject($this->dpdfrance) || false == $this->dpdfrance->active) {
            return false;
        }

        if ('monechelle' !== Tools::strtolower($apiOrder->getChannel()->getName())) {
            return false;
        }

        if (empty($this->getRelayIdFromOrder($apiOrder))) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Retrieves the relay ID and fill in the table ”dpdfrance_shipping” as expected by ”DPD France” Addons.', 'Shoppingfeed.Rule');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from ManoMano with ”DPD Relay”', 'Shoppingfeed.Rule');
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

        $apiOrder = $params['apiOrder'];
        $relayID = $this->getRelayIdFromOrder($apiOrder);

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Shoppingfeed.Rule'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (empty($relayID)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered. No relay ID found in shipping address \'RelayId\' field', 'Shoppingfeed.Rule')
            );

            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                $this->l('Rule triggered. Id Relay : %s', 'Shoppingfeed.Rule'),
                $relayID
            )
        );

        if (false == $this->associateWithDpd($cart, $relayID)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Failed to associate an order with dpdfrance module', 'Shoppingfeed.Rule')
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

    protected function getRelayIdFromOrder(\ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder)
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
}
