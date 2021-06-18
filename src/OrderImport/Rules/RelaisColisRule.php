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
use ShoppingfeedAddon\Services\IsoConvertor;
use Symfony\Component\VarDumper\VarDumper;
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
use Exception;
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
        $idRelais = $params['orderData']->shippingAddress['other'];

        if (false == $this->isRelayColisOrder($order)) {
            return false;
        }

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

        if (empty($idRelais)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Rule triggered. No relay ID found in shipping address \'Other\' field', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return false;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    $this->l('Rule triggered. Id Relay : %s / id_address_delivery : %s', 'RelaisColisRule'),
                $idRelais,
                $order->id_address_delivery
            ),
            'Order',
            $order->id
        );

        $relaisColisInfo = $this->createRelaisColisInfo($order, $idRelais);

        if (false == Validate::isLoadedObject($relaisColisInfo)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Failed to create a relais colis info object', 'RelaisColisRule'),
                'Order',
                $order->id
            );
            return false;
        }

        return $this->updateRelaisColisOrder($order, $relaisColisInfo);
    }

    /**
     * Gets relay info from the Mondial Relay API
     *
     * @param string $idRelais
     */

    public function getRelaisData($idRelais)
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

    /**
     * @return \RelaisColisInfo
     */
    protected function createRelaisColisInfo(Order $order, $idRelais)
    {
        $relaiData = $this->getRelaisData($idRelais);
        $address = new Address($order->id_address_delivery);
        $isoCountry = $this->getIsoConvertor()->toISO3(Country::getIsoById($address->id_country));
        $relaisColisInfo = new \RelaisColisInfo();
        $relaisColisInfo->id_cart = $order->id_cart;
        $relaisColisInfo->id_customer = $order->id_customer;
        $relaisColisInfo->rel = $idRelais;
        $relaisColisInfo->selected_date = date('Y-m-d');
        $relaisColisInfo->rel_adr = $address->address1;
        $relaisColisInfo->rel_cp = $address->postcode;
        $relaisColisInfo->rel_vil = $address->city;
        $relaisColisInfo->fcod_pays = $isoCountry;

        if (false == empty($relayData)) {
            // todo: set other properties
        }

        try {
            $relaisColisInfo->save();
        } catch (Exception $e) {
            throw $e;
        }

        return $relaisColisInfo;
    }

    /**
     * @param Order $order
     * @param \RelaisColisInfo $relaisColisInfo
     * @return bool
     */
    protected function updateRelaisColisOrder(Order $order, \RelaisColisInfo $relaisColisInfo)
    {
        $relaisColisOrder = $this->getRelaisColisOrder($order);
        $relaisColisOrder->id_relais_colis_info = $relaisColisInfo->id;

        try {
            $relaisColisOrder->save();
        } catch (Exception $e) {
            return false;
        }
    }

    protected function getRelaisColisOrder(Order $order)
    {
        try {
            return new \RelaisColisOrder(\RelaisColisOrder::getRelaisColisOrderId($order->id));
        } catch (Exception $e) {
            return new \RelaisColisOrder();
        }
    }

    /**
     * @return bool
     */
    protected function isRelayColisOrder(Order $order)
    {
        $query = (new DbQuery())
            ->select('id_relais_colis_order')
            ->from('relaiscolis_order')
            ->where('id_order = ' . (int)$order->id);

        try {
            return (bool)Db::getInstance()->getValue($query);
        } catch (Exception $e) {
            return false;
        }
    }

    protected function getIsoConvertor()
    {
        return new IsoConvertor();
    }
}
