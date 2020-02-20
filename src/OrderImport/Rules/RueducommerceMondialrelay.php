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

use ShoppingfeedClasslib\Registry;

use ShoppingFeed\Sdk\Api\Order\OrderResource;

/**
 * RDC is using RelayID from the delivery adresse and Other for the order number
 * instead of the relay ID.
 * The relay ID is located in the ShippingMethod
 * Therefore we need to extract the relay ID from the ShippingMethod and then
 * rebuild the ShippingMethod without the relay ID
 */
class RueducommerceMondialrelay implements \ShoppingfeedAddon\OrderImport\RuleInterface {
   
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();
        
        if (preg_match('#^rdc|rueducommerce$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && preg_match('#mondial relay .+#', Tools::strtolower($apiOrderShipment['carrier']))
        ) {
            // If the rule is applicable, we'll make sure this is empty, just in case...
            Registry::set(self::class . '_mondialRelayId', null);
            return true;
        }
        return false;
    }
    
    /**
     * Updates the carrier name before it's used to get a carrier
     * 
     * @param type $params
     */
    public function onCarrierRetrieval($params)
    {
        // Split the carrier name
        $explodedCarrier = explode(' ', $params['apiOrderShipment']['carrier']);
        // Remove the relay ID
        $mondialRelayID = array_pop($explodedCarrier);
        // Rebuild the carrier name; it should be found properly
        $params['apiOrderShipment']['carrier'] = implode($explodedCarrier, " ");
        
        // Save the relay ID in the "Other" field so it can be used by the main
        // Mondial Relay rule
        // TODO : where is the "$order->Other" field in the new API ?
    }
}
