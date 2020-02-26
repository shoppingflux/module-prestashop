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
use Translate;
use Address;
use Country;
use Carrier;
use SoapClient;

use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class Mondialrelay extends \ShoppingfeedAddon\OrderImport\RuleAbstract
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();
        $module_mondialRelay = Module::getInstanceByName('mondialrelay');
        
        // Check only for name presence in the string; the relay ID could be appended
        // to the field (see ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay)
        if ($module_mondialRelay && preg_match('#mondial relay#', Tools::strtolower($apiOrderShipment['carrier']))) {
            return true;
        }
        return false;
    }
    
    public function onPostProcess($params)
    {
        // TODO : This should work for older version of MR, but we need to support the new one too
        $apiOrder = $params['apiOrder'];
        $order = new Order($params['sfOrder']->id_order);
        $relayId = $params['orderData']->shippingAddress['other'];
        
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'Mondialrelay'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        
        
        ProcessLoggerHandler::logInfo(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Rule triggered.', 'Mondialrelay'),
            'Order',
            $order->id
        );
        
        if (empty($relayId)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'No relay ID found in shipping address \'Other\' field', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return false;
        }
        
        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'Id Relay : %s', 'Mondialrelay'),
                $relayId,
                $order->id
            ),
            'Order',
            $order->id
        );
        
        $carrier = new Carrier((int)$order->id_carrier);
        
        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'id_address_delivery : %s', 'Mondialrelay'),
                $order->id_address_delivery
            ),
            'Order',
            $order->id
        );
        
        $address = new Address($order->id_address_delivery);
        $countryIso = Country::getIsoById($address->id_country);
        
        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'isoCountry : %s', 'Mondialrelay'),
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
                    Translate::getModuleTranslation('shoppingfeed', 'Failed to get relay data', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return false;
        }
        
        ProcessLoggerHandler::logInfo(
            sprintf(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'Id Carrier : %s', 'Mondialrelay'),
                $carrier->id
            ),
            'Order',
            $order->id
        );
        
        // Get corresponding method
        $queryGetMondialRelayMethod = new DbQuery();
        $queryGetMondialRelayMethod->select('id_mr_method')
            ->from('mr_method')
            ->where('id_carrier = ' . (int)$carrier->id)
            ->orderBy('id_mr_method DESC');
        $mondialRelayMethod = Db::getInstance()->getValue($queryGetMondialRelayMethod);
        
        if (!$mondialRelayMethod) {
            ProcessLoggerHandler::logError(
                sprintf(
                    $logPrefix .
                        Translate::getModuleTranslation('shoppingfeed', 'Could not find mondial relay method for carrier ID %s', 'Mondialrelay'),
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
            ProcessLoggerHandler::logSuccess(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'Successfully added relay information', 'Mondialrelay'),
                'Order',
                $order->id
            );
            return;
        }
        
        ProcessLoggerHandler::logError(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Could not add relay information', 'Mondialrelay'),
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
    public function getRelayData(ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder, $relayId, $countryIso)
    {
        $urlWebService = 'http://www.mondialrelay.fr/webservice/Web_Services.asmx?WSDL';
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'Mondialrelay'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ;
        
        $mondialRelayConfig = $this->getMondialRelayConfig();
        // Mondial relay module not configured
        if (!$mondialRelayConfig) {
            return false;
        }
        
        $client = new SoapClient($urlWebService);
        if (!is_object($client)) {
            // Error connecting to webservice
            ProcessLoggerHandler::logError(
                sprintf(
                    $logPrefix .
                        Translate::getModuleTranslation('shoppingfeed', 'Could not create SOAP client for URL %s', 'Mondialrelay'),
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
            ProcessLoggerHandler::logError(
                sprintf(
                    $logPrefix .
                        Translate::getModuleTranslation('shoppingfeed', 'Error getting relay %s data : code %s', 'Mondialrelay'),
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
    
    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'If the order has \'Mondial Relay\' in its carrier name.', 'Mondialrelay');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
