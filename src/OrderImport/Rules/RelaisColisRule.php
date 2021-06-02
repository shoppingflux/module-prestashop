<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommence
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommence is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommence
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Module;
use Tools;
use Configuration;
use DbQuery;
use Db;
use Address;
use Country;
use Carrier;
use SoapClient;
use Order;
use Cart;
use Validate;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class RelaisColisRule extends RuleAbstract implements RuleInterface
{
    /** @var \Relaiscolis*/
    protected $relaisColis;

    /**
     * @param OrderResource $apiOrder
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

    public function onPostProcess($params)
    {
        $apiOrder = $params['apiOrder'];
        $order = new Order($params['sfOrder']->id_order);
        $relayId = $params['orderData']->shippingAddress['other'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Mondialrelay'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('[Order: %s]', 'Mondialrelay'),
            'Order',
            $order->id
        );

        if (empty($relayId)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Rule triggered. No relay ID found in shipping address \'Other\' field', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return false;
        }

        $carrier = new Carrier((int)$order->id_carrier);
        $address = new Address($order->id_address_delivery);
        $countryIso = Country::getIsoById($address->id_country);

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    $this->l('Rule triggered. Id Relay : %s / id_address_delivery : %s / isoCountry : %s', 'Mondialrelay'),
                $relayId,
                $order->id_address_delivery,
                $countryIso
            ),
            'Order',
            $order->id
        );

        // Get relay data
        $relayData = $this->getRelayData($apiOrder, $relayId, $countryIso);
        if (!$relayData) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Failed to get relay data', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    $this->l('Id Carrier : %s', 'Mondialrelay'),
                $carrier->id
            ),
            'Order',
            $order->id
        );

        // todo: implement a link with the module relaiscolis
    }

    /**
     * Gets relay info from the Mondial Relay API
     *
     * @param ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder
     * @param string $relayId
     * @param string $countryIso
     */

    public function getRelayData(OrderResource $apiOrder, $relayId, $countryIso)
    {
        // todo: to implement
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Relais Colis module\'s table.', 'RelaisColisRule');
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order has \'Relais Colis\' in its carrier name.', 'RelaisColisRule');
    }
}
