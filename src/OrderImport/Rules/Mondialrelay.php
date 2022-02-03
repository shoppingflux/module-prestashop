<?php
/**
 *
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
 *
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
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Validate;

class Mondialrelay extends RuleAbstract implements RuleInterface
{
    /** @var Module*/
    protected $module_mondialRelay;

    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();
        $this->module_mondialRelay = Module::getInstanceByName('mondialrelay');

        if (Validate::isLoadedObject($this->module_mondialRelay) == false) {
            return false;
        }

        // Check only for name presence in the string; the relay ID could be appended
        // to the field (see ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay)
        if (preg_match('#mondial relay#', Tools::strtolower($apiOrderShipment['carrier']))) {
            return true;
        }

        if (preg_match('#Livraison Magasin produit jusqu\'à 30 kg#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#Livraison Point MR 24R#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#^rel$#i', trim($apiOrderShipment['carrier']))) {
            return true;
        }

        return false;
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
            ProcessLoggerHandler::logInfo(
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
            ProcessLoggerHandler::logInfo(
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

        // Insertion in the module is dependent on module version.
        if (version_compare($this->module_mondialRelay->version, '3', '<')) {
            $this->addOrderBeforeV3($relayId, $countryIso, $relayData, $carrier, $order, $logPrefix);
            return;
        }

        $this->addOrderV3($relayId, $countryIso, $relayData, $carrier, $order, $logPrefix);
    }

    protected function addOrderBeforeV3($relayId, $countryIso, $relayData, $carrier, $order, $logPrefix)
    {
        // Get corresponding method
        $queryGetMondialRelayMethod = new DbQuery();
        $queryGetMondialRelayMethod->select('id_mr_method')
            ->from('mr_method')
            ->where('id_carrier = ' . (int)$carrier->id)
            ->orderBy('id_mr_method DESC');
        $mondialRelayMethod = Db::getInstance()->getValue($queryGetMondialRelayMethod);

        if (!$mondialRelayMethod) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix .
                        $this->l('Could not find mondial relay method for carrier ID %s', 'Mondialrelay'),
                    $carrier->id
                ),
                'Order',
                $order->id
            );
            return false;
        }

        // Depending of the marketplace, the length of the relay ID is not the same. (5 digits, 6 digits).
        // We force a 6 digits string required by Mondial Relay
        $formattedRelayId = str_pad($relayId, 6, '0', STR_PAD_LEFT);

        $insertResult = Db::getInstance()->insert(
            'mr_selected',
            array(
                'id_customer' => (int)$order->id_customer,
                'id_method' => (int)$mondialRelayMethod,
                'id_cart' => (int)$order->id_cart,
                'id_order' => (int)$order->id,
                'MR_Selected_Num' => pSQL($formattedRelayId),
                'MR_Selected_LgAdr1' => pSQL($relayData->LgAdr1),
                'MR_Selected_LgAdr2' => pSQL($relayData->LgAdr2),
                'MR_Selected_LgAdr3' => pSQL($relayData->LgAdr3),
                'MR_Selected_LgAdr4' => pSQL($relayData->LgAdr4),
                'MR_Selected_CP' => pSQL($relayData->CP),
                'MR_Selected_Ville' => pSQL($relayData->Ville),
                'MR_Selected_Pays' => pSQL($countryIso),
            )
        );

        if ($insertResult) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Successfully added relay information', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Could not add relay information', 'Mondialrelay'),
            'Order',
            $order->id
        );
    }

    protected function addOrderV3($relayId, $countryIso, $relayData, $carrier, $order, $logPrefix)
    {
        // Get corresponding method
        $queryGetMondialRelayMethod = new DbQuery();
        $queryGetMondialRelayMethod->select('id_mondialrelay_carrier_method')
            ->from('mondialrelay_carrier_method')
            ->where('id_carrier = ' . (int)$carrier->id)
            ->orderBy('id_mondialrelay_carrier_method DESC');
        $mondialRelayCarrierMethodId = Db::getInstance()->getValue($queryGetMondialRelayMethod);

        if (!$mondialRelayCarrierMethodId) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix .
                        $this->l('Could not find mondial relay method for carrier ID %s', 'Mondialrelay'),
                    $carrier->id
                ),
                'Order',
                $order->id
            );
            return false;
        }

        // Depending of the marketplace, the length of the relay ID is not the same. (5 digits, 6 digits).
        // We force a 6 digits string required by Mondial Relay
        $formattedRelayId = str_pad($relayId, 6, '0', STR_PAD_LEFT);
        $cart = new Cart((int)$order->id_cart);
        $insertResult = Db::getInstance()->insert(
            'mondialrelay_selected_relay',
            array(
                'id_customer' => (int)$order->id_customer,
                'id_mondialrelay_carrier_method' => (int)$mondialRelayCarrierMethodId,
                'id_cart' => (int)$order->id_cart,
                'id_order' => (int)$order->id,
                'selected_relay_num' => pSQL($formattedRelayId),
                'selected_relay_adr1' => pSQL($relayData->LgAdr1),
                'selected_relay_adr2' => pSQL($relayData->LgAdr2),
                'selected_relay_adr3' => pSQL($relayData->LgAdr3),
                'selected_relay_adr4' => pSQL($relayData->LgAdr4),
                'selected_relay_postcode' => pSQL($relayData->CP),
                'selected_relay_city' => pSQL($relayData->Ville),
                'selected_relay_country_iso' => pSQL($countryIso),
                'package_weight' => $cart->getTotalWeight() * (float)Configuration::get('MONDIALRELAY_WEIGHT_COEFF'),
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            )
        );

        if ($insertResult) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Successfully added relay information', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Could not add relay information', 'Mondialrelay'),
            'Order',
            $order->id
        );
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
        $urlWebService = 'http://www.mondialrelay.fr/webservice/Web_Services.asmx?WSDL';
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Mondialrelay'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        $mondialRelayConfig = $this->getMondialRelayConfig();
        // Mondial relay module not configured
        if (!$mondialRelayConfig) {
            return false;
        }

        $client = new SoapClient($urlWebService);
        if (!is_object($client)) {
            // Error connecting to webservice
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix .
                        $this->l('Could not create SOAP client for URL %s', 'Mondialrelay'),
                    $urlWebService
                ),
                'Order'
            );
            return false;
        }
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;

        $reqParams = array(
            'Enseigne' => $mondialRelayConfig['enseigne'],
            'Num' => $relayId,
            'Pays' => $countryIso,
            'Security' => Tools::strtoupper(md5(
                $mondialRelayConfig['enseigne'].$relayId.$countryIso.$mondialRelayConfig['apiKey']
            ))
        );

        $result = $client->WSI2_AdressePointRelais($reqParams);

        if (!isset($result->WSI2_AdressePointRelaisResult->STAT)
            || $result->WSI2_AdressePointRelaisResult->STAT != 0) {
            // Web service did not return expected data
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix .
                        $this->l('Error getting relay %s data : code %s', 'Mondialrelay'),
                    $result->WSI2_AdressePointRelaisResult->STAT
                ),
                'Order'
            );
            return false;
        } else {
            return $result->WSI2_AdressePointRelaisResult;
        }
    }

    protected function getMondialRelayConfig()
    {
        // Module configuration is dependent on version.
        if (version_compare($this->module_mondialRelay->version, '3', '<')) {
            $mondialRelayConfig = Configuration::get('MR_ACCOUNT_DETAIL');
            // Mondial relay module not configured
            if (!$mondialRelayConfig) {
                return false;
            }
            $mondialRelayConfig = unserialize($mondialRelayConfig);
            return array(
                'enseigne' => $mondialRelayConfig['MR_ENSEIGNE_WEBSERVICE'],
                'apiKey' => $mondialRelayConfig['MR_KEY_WEBSERVICE'],
            );
        }

        $enseigne = Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE');
        $apiKey = Configuration::get('MONDIALRELAY_WEBSERVICE_KEY');

        // Mondial relay module not configured
        if (empty($enseigne) || empty($apiKey)) {
            return false;
        }

        return array(
            'enseigne' => $enseigne,
            'apiKey' => $apiKey,
        );
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order has \'Mondial Relay\' in its carrier name.', 'Mondialrelay');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
