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

use Configuration;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class MondialrelayRule extends RuleAbstract implements RuleInterface
{
    protected $module_mondialRelay;

    protected $logPrefix = '';

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->module_mondialRelay = \Module::getInstanceByName('mondialrelay');
        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'Mondialrelay'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        if (\Validate::isLoadedObject($this->module_mondialRelay) && $this->module_mondialRelay->active) {
            if (false === empty($this->getRelayId($apiOrder))) {
                return true;
            }
        }

        return false;
    }

    public function afterCartCreation($params)
    {
        if (empty($params['apiOrder'])) {
            return;
        }
        if (empty($params['cart'])) {
            return;
        }

        $cart = $params['cart'];
        $apiOrder = $params['apiOrder'];
        $carrier = new \Carrier((int) $cart->id_carrier);
        $address = new \Address((int) $cart->id_address_delivery);
        $countryIso = \Country::getIsoById($address->id_country);
        $relayId = $this->getRelayId($apiOrder);

        if ($carrier->external_module_name != $this->module_mondialRelay->name) {
            return;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $this->logPrefix .
                $this->l('Rule triggered. Id Relay : %s / id_address_delivery : %s / isoCountry : %s', 'Mondialrelay'),
                $relayId,
                $address->id,
                $countryIso
            ),
            'Cart',
            $cart->id
        );

        // Get relay data
        $relayData = $this->getRelayData($apiOrder, $relayId, $countryIso);
        if (!$relayData) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                $this->l('Failed to get relay data', 'Mondialrelay'),
                'Cart',
                $cart->id
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            sprintf(
                $this->logPrefix .
                $this->l('Id Carrier : %s', 'Mondialrelay'),
                $carrier->id
            ),
            'Cart',
            $cart->id
        );
        // Insertion in the module is dependent on module version.
        if (version_compare($this->module_mondialRelay->version, '3', '<')) {
            $this->addOrderBeforeV3($relayId, $countryIso, $relayData, $carrier, $cart);

            return;
        }

        $this->addOrderV3($relayId, $countryIso, $relayData, $carrier, $cart);
    }

    public function onPostProcess($params)
    {
        if (empty($params['sfOrder'])) {
            return;
        }

        $order = new \Order($params['sfOrder']->id_order);

        if (version_compare($this->module_mondialRelay->version, '3', '<')) {
            $table = 'mr_selected';
        } else {
            $table = 'mondialrelay_selected_relay';
        }

        \Db::getInstance()->update(
            $table,
            [
                'id_order' => (int) $order->id,
            ],
            'id_cart = ' . (int) $order->id_cart
        );

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Updated order info in the table ' . $table, 'Mondialrelay'),
            'Order',
            $order->id
        );
    }

    protected function addOrderBeforeV3($relayId, $countryIso, $relayData, $carrier, $cart)
    {
        // Get corresponding method
        $queryGetMondialRelayMethod = new \DbQuery();
        $queryGetMondialRelayMethod->select('id_mr_method')
            ->from('mr_method')
            ->where('id_carrier = ' . (int) $carrier->id)
            ->orderBy('id_mr_method DESC');
        $mondialRelayMethod = \Db::getInstance()->getValue($queryGetMondialRelayMethod);

        if (!$mondialRelayMethod) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->logPrefix .
                        $this->l('Could not find mondial relay method for carrier ID %s', 'Mondialrelay'),
                    $carrier->id
                ),
                'Cart',
                $cart->id
            );

            return false;
        }

        // Depending of the marketplace, the length of the relay ID is not the same. (5 digits, 6 digits).
        // We force a 6 digits string required by Mondial Relay
        $formattedRelayId = str_pad($relayId, 6, '0', STR_PAD_LEFT);

        $insertResult = \Db::getInstance()->insert(
            'mr_selected',
            [
                'id_customer' => (int) $cart->id_customer,
                'id_method' => (int) $mondialRelayMethod,
                'id_cart' => (int) $cart->id,
                'MR_Selected_Num' => pSQL($formattedRelayId),
                'MR_Selected_LgAdr1' => pSQL($relayData->LgAdr1),
                'MR_Selected_LgAdr2' => pSQL($relayData->LgAdr2),
                'MR_Selected_LgAdr3' => pSQL($relayData->LgAdr3),
                'MR_Selected_LgAdr4' => pSQL($relayData->LgAdr4),
                'MR_Selected_CP' => pSQL($relayData->CP),
                'MR_Selected_Ville' => pSQL($relayData->Ville),
                'MR_Selected_Pays' => pSQL($countryIso),
            ]
        );

        if ($insertResult) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Successfully added relay information', 'Mondialrelay'),
                'Cart',
                $cart->id
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Could not add relay information', 'Mondialrelay'),
            'Cart',
            $cart->id
        );
    }

    protected function addOrderV3($relayId, $countryIso, $relayData, $carrier, $cart)
    {
        // Get corresponding method
        $queryGetMondialRelayMethod = new \DbQuery();
        $queryGetMondialRelayMethod->select('id_mondialrelay_carrier_method')
            ->from('mondialrelay_carrier_method')
            ->where('id_carrier = ' . (int) $carrier->id)
            ->orderBy('id_mondialrelay_carrier_method DESC');
        $mondialRelayCarrierMethodId = \Db::getInstance()->getValue($queryGetMondialRelayMethod);

        if (!$mondialRelayCarrierMethodId) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->logPrefix .
                        $this->l('Could not find mondial relay method for carrier ID %s', 'Mondialrelay'),
                    $carrier->id
                ),
                'Cart',
                $cart->id
            );

            return false;
        }

        // Depending of the marketplace, the length of the relay ID is not the same. (5 digits, 6 digits).
        // We force a 6 digits string required by Mondial Relay
        $formattedRelayId = str_pad($relayId, 6, '0', STR_PAD_LEFT);
        $insertResult = \Db::getInstance()->insert(
            'mondialrelay_selected_relay',
            [
                'id_customer' => (int) $cart->id_customer,
                'id_mondialrelay_carrier_method' => (int) $mondialRelayCarrierMethodId,
                'id_cart' => (int) $cart->id,
                'selected_relay_num' => pSQL($formattedRelayId),
                'selected_relay_adr1' => pSQL($relayData->LgAdr1),
                'selected_relay_adr2' => pSQL($relayData->LgAdr2),
                'selected_relay_adr3' => pSQL($relayData->LgAdr3),
                'selected_relay_adr4' => pSQL($relayData->LgAdr4),
                'selected_relay_postcode' => pSQL($relayData->CP),
                'selected_relay_city' => pSQL($relayData->Ville),
                'selected_relay_country_iso' => pSQL($countryIso),
                'package_weight' => $cart->getTotalWeight() * (float) \Configuration::get('MONDIALRELAY_WEIGHT_COEFF'),
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]
        );

        if ($insertResult) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Successfully added relay information', 'Mondialrelay'),
                'Cart',
                $cart->id
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Could not add relay information', 'Mondialrelay'),
            'Cart',
            $cart->id
        );
    }

    /**
     * Gets relay info from the Mondial Relay API
     *
     * @param OrderResource $apiOrder
     * @param string $relayId
     * @param string $countryIso
     */
    public function getRelayData(OrderResource $apiOrder, $relayId, $countryIso)
    {
        $mondialRelayConfig = $this->getMondialRelayConfig();
        // Mondial relay module not configured
        if (!$mondialRelayConfig) {
            return false;
        }

        $client = $this->initSoapClient();
        if (!is_object($client)) {
            // Error connecting to webservice
            ProcessLoggerHandler::logInfo(
                $this->logPrefix . $this->l('Could not create SOAP client for URL', 'Mondialrelay'),
                'Order'
            );

            return false;
        }
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;

        $reqParams = [
            'Enseigne' => $mondialRelayConfig['enseigne'],
            'Num' => $relayId,
            'Pays' => $countryIso,
            'Security' => \Tools::strtoupper(md5(
                $mondialRelayConfig['enseigne'] . $relayId . $countryIso . $mondialRelayConfig['apiKey']
            )),
        ];

        $result = $client->WSI2_AdressePointRelais($reqParams);

        if (!isset($result->WSI2_AdressePointRelaisResult->STAT)
            || $result->WSI2_AdressePointRelaisResult->STAT != 0) {
            // Web service did not return expected data
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $this->logPrefix . $this->l('Error getting relay %s data : code %s', 'Mondialrelay'),
                    (string) $relayId,
                    (string) $result->WSI2_AdressePointRelaisResult->STAT
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
            $mondialRelayConfig = \Configuration::get('MR_ACCOUNT_DETAIL');
            // Mondial relay module not configured
            if (!$mondialRelayConfig) {
                return false;
            }
            $mondialRelayConfig = unserialize($mondialRelayConfig);

            return [
                'enseigne' => $mondialRelayConfig['MR_ENSEIGNE_WEBSERVICE'],
                'apiKey' => $mondialRelayConfig['MR_KEY_WEBSERVICE'],
            ];
        }

        $enseigne = \Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE');
        $apiKey = \Configuration::get('MONDIALRELAY_WEBSERVICE_KEY');

        // Mondial relay module not configured
        if (empty($enseigne) || empty($apiKey)) {
            return false;
        }

        return [
            'enseigne' => $enseigne,
            'apiKey' => $apiKey,
        ];
    }

    public function getRelayId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (false === empty($apiOrderData['shippingAddress']['relayId'])) {
            return $apiOrderData['shippingAddress']['relayId'];
        }
        if (false === empty($apiOrderData['shippingAddress']['other'])) {
            return $apiOrderData['shippingAddress']['other'];
        }
        if (false === empty($apiOrderData['additionalFields']['shipping_pudo_id'])) {
            return $apiOrderData['additionalFields']['shipping_pudo_id'];
        }
        if (false === empty($apiOrderData['additionalFields']['shippingRelayId'])) {
            return $apiOrderData['additionalFields']['shippingRelayId'];
        }

        return '';
    }

    protected function initSoapClient()
    {
        $client = null;
        $configs = [
            'https://api.mondialrelay.com/Web_Services.asmx?WSDL',
            'https://www.mondialrelay.fr/webservice/Web_Services.asmx?WSDL',
        ];

        foreach ($configs as $config) {
            try {
                $client = new \SoapClient($config);
            } catch (\SoapFault $e) {
                continue;
            }

            return $client;
        }

        return $client;
    }

    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }

    public function getConditions()
    {
        return $this->l('If the order is delivered by mondialrelay carrier', 'Mondialrelay');
    }
}
