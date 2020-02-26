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

class CdiscountRelay extends \ShoppingfeedAddon\OrderImport\RuleAbstract
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();
        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                $apiOrderShipment['carrier'] === "SO1"
                || $apiOrderShipment['carrier'] === "REL"
                || $apiOrderShipment['carrier'] === "RCO"
            )
        ;
    }
    
    /**
     * We have to do this on preprocess, as the fields may be used in various
     * steps
     *
     * @param array $params
     */
    public function onPreProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'CdiscountRelay'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';
        
        ProcessLoggerHandler::logInfo(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Rule triggered.', 'CdiscountRelay'),
            'Order'
        );
        
        $this->updateAddress($orderData->shippingAddress);
        
        ProcessLoggerHandler::logSuccess(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Shipping address updated to set relay ID.', 'CdiscountRelay'),
            'Order'
        );
    }
    
    /**
     * See old module _getAddress
     * The relay ID is in "lastname", and the actual lastname is appended to "firstname".
     * The relay ID should (apparently) be in "company".
     * The old function didn't alter the API address, so we won't do it either.
     *
     * @param array $address
     */
    public function updateAddress(&$address)
    {
        // Workaround for CDiscount usage of last name as pickup-point name
        $relayId = $address['lastName'];
        
        // Check if the company is already filled
        if (!empty($address['company'])) {
            // When the company is known, we are appending it to the second line of the adresse
            $address['street2'] .= ' ' . $address['company'];
        }
        
        $address['company'] = $relayId;

        // And now we decompose the fullname (in the FirstName field) by first name + last name
        // We consider that what's after the space is the last name
        $fullname = trim($address['firstName']);
        $explodedFullname = explode(" ", $fullname);
        if (isset($explodedFullname[0])) {
            $address['firstName'] = array_shift($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'If the order is from CDiscount and the carrier is \'SO1\', \'REL\' or \'RCO\'.', 'CdiscountRelay');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'Retrieves  the relay ID from the \'lastname\' field and puts it in \'company\'. If a company is already set, appends it to \'address (2)\'. Fills the \'lastname\' field with everything after the first space from \'firstname\'.', 'CdiscountRelay');
    }
}
