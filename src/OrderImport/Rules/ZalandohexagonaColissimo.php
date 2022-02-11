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
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

/**
 * See ticket #30781
 * This is for the "colissimo" module, not to be confused with "soflexibilite" or "soliberte". When an order from Zalando uses Pickup Point shipping with Colissimo,
 * the data for the pickup point are in the additional fields. The data must be set up in the "colissimo" module's table so that colissimo can treat it as a
 * standard order. The goal is to allow the merchant to generate labels with the "colissimo" module so that the standard shipping process can take place.
 */
class ZalandohexagonaColissimo extends AbstractColissimo implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', $this->getFileName()),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        if ('zalandohexagona' === Tools::strtolower($apiOrder->getChannel()->getName())
            && !empty($apiOrderData['additionalFields']['service_point_id'])
            && !empty($apiOrderData['additionalFields']['service_point_name'])
            && $this->isModuleColissimoEnabled()
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Rule triggered.', $this->getFileName()),
                'Order'
            );

            return true;
        }

        return false;
    }

    /**
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
            $this->l('[Order: %s]', $this->getFileName()),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Saving Colissimo pickup point.', $this->getFileName()),
            'Order'
        );

        // Retrieve necessary order data
        $productCode = $this->getProductCode($apiOrder);
        $colissimoPickupPointId = $this->getPointId($apiOrder);

        $shippingAddress = $apiOrder->getShippingAddress();

        // Save/update Colissimo pickup point
        $pickupPointData = [
            'colissimo_id' => $colissimoPickupPointId,
            'company_name' => $apiOrderData['additionalFields']['service_point_name'],
            'address1' => $shippingAddress['street'],
            'address2' => $shippingAddress['street2'],
            'address3' => '',
            'city' => $shippingAddress['city'],
            'zipcode' => $shippingAddress['postalCode'],
            'country' => Tools::strtoupper(Country::getNameById($params['cart']->id_lang, Country::getByIso($shippingAddress['country']))),
            'iso_country' => $shippingAddress['country'],
            'product_code' => $productCode,
            'network' => '',
        ];
        $pickupPoint = ColissimoPickupPoint::getPickupPointByIdColissimo($colissimoPickupPointId);
        $pickupPoint->hydrate(array_map('pSQL', $pickupPointData));
        $pickupPoint->save();

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                sprintf(
                    $this->l('Linking Colissimo pickup point %s to cart %s.', $this->getFileName()),
                    $colissimoPickupPointId,
                    $params['cart']->id
                ),
            'Order'
        );

        // Save pickup point/cart association
        ColissimoCartPickupPoint::updateCartPickupPoint(
            (int) $params['cart']->id,
            (int) $pickupPoint->id,
            '' //TODO $mobilePhone; use billing address phone ? Not required, but might as well, the merchant will have to fill it either way.
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Zalando and has non-empty "service_point_id" and "service_point_name" additional fields.', $this->getFileName());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', $this->getFileName());
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        list($productCode, $colissimoPickupPointId) = explode(':', $apiOrderData['additionalFields']['service_point_id']);

        return $productCode;
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        list($productCode, $colissimoPickupPointId) = explode(':', $apiOrderData['additionalFields']['service_point_id']);

        return $colissimoPickupPointId;
    }
}
