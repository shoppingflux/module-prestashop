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

use ColissimoCartPickupPoint;
use ColissimoPickupPoint;
use Country;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

class CdiscountRelay extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        $module_colissimo = Module::getInstanceByName('colissimo');
        $apiOrderShipment = $apiOrder->getShipment();

        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                $apiOrderShipment['carrier'] === 'SO1'
                || $apiOrderShipment['carrier'] === 'REL'
                || $apiOrderShipment['carrier'] === 'RCO'
            )
            && $module_colissimo
            && $module_colissimo->active
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
            $this->l('[Order: %s]', 'CdiscountRelay'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';

        $this->updateAddress($orderData->shippingAddress);

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Shipping address updated to set relay ID.', 'CdiscountRelay'),
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

        // And now we decompose the fullname (in the FirstName field) by last name + first name
        // We consider that what's after the last space is the first name
        $fullname = trim($address['firstName']);
        $explodedFullname = explode(' ', $fullname);
        if (isset($explodedFullname[0])) {
            $address['firstName'] = array_pop($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        }
    }

    /**
     * refs #32011
     * Add Colissimo pickup point in colissimo module DB table; add pickup point/cart association in colissimo module DB table.
     * If these data are properly entered, the colissimo module should be able to handle the rest using the order process native hooks.
     */
    public function afterCartCreation($params)
    {
        if (class_exists(ColissimoPickupPoint::class) === false || class_exists(ColissimoCartPickupPoint::class) === false) {
            return;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Saving Colissimo pickup point.', 'CdiscountRelay'),
            'Order'
        );

        $shippingAddress = $apiOrder->getShippingAddress();
        if (empty($shippingAddress['other']) === true) {
            return true;
        }
        $colissimoPickupPointId = $shippingAddress['other'];

        $shippingAddress = $apiOrder->getShippingAddress();
        $productCode = 'A2P'; // hack

        // Save/update Colissimo pickup point
        $pickupPointData = [
            'colissimo_id' => $colissimoPickupPointId,
            'company_name' => $shippingAddress['lastName'],
            'address1' => $shippingAddress['street'],
            'address2' => $shippingAddress['street2'],
            'city' => $shippingAddress['city'],
            'zipcode' => $shippingAddress['postalCode'],
            'country' => Tools::strtoupper(Country::getNameById($params['cart']->id_lang, Country::getByIso($shippingAddress['country']))),
            'iso_country' => $shippingAddress['country'],
            'product_code' => $productCode,
            'network' => '', //TODO; how do we get this field ? It's not in the data sent by SF. Ask Colissimo directly ? Then again, it's not required.
        ];
        $pickupPoint = ColissimoPickupPoint::getPickupPointByIdColissimo($colissimoPickupPointId);
        $pickupPoint->hydrate(array_map('pSQL', $pickupPointData));
        $pickupPoint->save();

        if (empty($pickupPoint) === true) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    sprintf(
                        $this->l('Linking Colissimo pickup failed point %s to cart %s. Pickup point not found in the Colissimo module.', 'CdiscountRelay'),
                        $colissimoPickupPointId,
                        $params['cart']->id
                    ),
                'Order'
            );

            return true;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                sprintf(
                    $this->l('Linking Colissimo pickup point %s to cart %s.', 'CdiscountRelay'),
                    $colissimoPickupPointId,
                    $params['cart']->id
                ),
            'Order'
        );

        // Save pickup point/cart association
        ColissimoCartPickupPoint::updateCartPickupPoint(
            (int) $params['cart']->id,
            (int) $pickupPoint->id,
            !empty($shippingAddress['mobilePhone']) ? $shippingAddress['mobilePhone'] : ''
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from CDiscount and the carrier is \'SO1\', \'REL\' or \'RCO\'.', 'CdiscountRelay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Retrieves  the relay ID from the \'lastname\' field and puts it in \'company\'. If a company is already set, appends it to \'address (2)\'. Fills the \'lastname\' field with everything after the first space from \'firstname\'.', 'CdiscountRelay');
    }
}
