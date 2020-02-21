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

use Tools;
use Translate;

use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class CdiscountRelay implements \ShoppingfeedAddon\OrderImport\RuleInterface {
   
    public function isApplicable(OrderResource $apiOrder) {
        $apiOrderShipment = $apiOrder->getShipment();
        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                $apiOrderShipment['carrier'] === "SO1" 
                || $apiOrderShipment['carrier'] === "REL"
                || $apiOrderShipment['carrier'] === "RCO"
            )
        ;
    }
    
    public function beforeBillingAddressSave($params)
    {
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'Mondialrelay'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';
        
        ProcessLoggerHandler::logInfo(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'Updating CDiscount billing address to set relay ID...', 'CdiscountRelay'),
            'Order'
        );
        
        $this->updateAddress($params['apiBillingAddress'], $params['billingAddress']);
    }
    
    public function beforeShippingAddressSave($params)
    {
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'Mondialrelay'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';
        
        ProcessLoggerHandler::logInfo(
                $logPrefix .
                    Translate::getModuleTranslation('shoppingfeed', 'Updating CDiscount shipping address to set relay ID...', 'CdiscountRelay'),
            'Order'
        );
        
        $this->updateAddress($params['apiShippingAddress'], $params['shippingAddress']);
    }
    
    /**
     * See old module _getAddress
     * The relay ID is in "lastname", and the actual lastname is appended to "firstname".
     * The relay ID should (apparently) be in "company".
     * The old function didn't alter the API address, so we won't do it either.
     * 
     * @param array $apiAddress
     * @param \Address $address
     */
    public function updateAddress(&$apiAddress, $address)
    {
        // Workaround for CDiscount usage of last name as pickup-point name
        $relayId = $apiAddress['lastName'];
        
        // Check if the company is already filled
        if (!empty($apiAddress['company'])) {
            // When the company is known, we are appending it to the second line of the adresse
            $address->address2 = $address->address2 . ' ' . $apiAddress['company'];
        }
        
        $address->company = $relayId;

        // And now we decompose the fullname (in the FirstName field) by first name + last name
        // We consider that what's after the space is the last name
        $fullname = trim($apiAddress['firstName']);
        $explodedFullname = explode(" ", $fullname);
        if (isset($explodedFullname[0])) {
            $address->firstname = array_shift($explodedFullname);
            $address->lastname = implode(' ', $explodedFullname);
        }
    }
}