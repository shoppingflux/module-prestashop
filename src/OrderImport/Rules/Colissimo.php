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

use Tools;
use Module;
use Carrier;
use Validate;
use Country;

use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

use ColissimoService;
use ColissimoTools;
use ColissimoPickupPoint;
use ColissimoCartPickupPoint;

/**
 * See ticket #30781
 * This is for the "colissimo" module, not to be confused with "soflexibilite" or "soliberte". When an order from Zalando uses Pickup Point shipping with Colissimo,
 * the data for the pickup point are in the additional fields. The data must be set up in the "colissimo" module's table so that colissimo can treat it as a
 * standard order. The goal is to allow the merchant to generate labels with the "colissimo" module so that the standard shipping process can take place.
 */
class Colissimo extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        $module_colissimo = Module::getInstanceByName('colissimo');
        if ("zalandohexagona" === Tools::strtolower($apiOrder->getChannel()->getName())
            && !empty($apiOrderData['additionalFields']['service_point_id'])
            && !empty($apiOrderData['additionalFields']['service_point_name'])
            && $module_colissimo && $module_colissimo->active
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Rule triggered.', 'Colissimo'),
                'Order'
            );
            return true;
        }

        return false;
    }

    /**
     * Retrieve and set the Colissimo carrier, and skip SF carrier creation.
     */
    public function onCarrierRetrieval($params)
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Setting Colissimo carrier.', 'Colissimo'),
            'Order'
        );

        // Retrieve necessary order data
        $apiOrderData = $apiOrder->toArray();
        list($productCode, $colissimoPickupPointId) = explode(':', $apiOrderData['additionalFields']['service_point_id']);

        $shippingAddress = $apiOrder->getShippingAddress();
        $destinationType = ColissimoTools::getDestinationTypeByIsoCountry($shippingAddress['country']);

        // Retrieve ColissimoService using order data
        $idColissimoService = ColissimoService::getServiceIdByProductCodeDestinationType(
            $productCode,
            $destinationType
        );
        if (!$idColissimoService) {
            throw new Exception(
                $logPrefix .
                    sprintf(
                        $this->l('Could not retrieve ColissimoService from productCode %s and destinationType %s.', 'Colissimo'),
                        $productCode,
                        $destinationType
                    )
            );
            return false;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                sprintf(
                    $this->l('Retrieved ColissimoService %s from productCode %s and destinationType %s.', 'Colissimo'),
                        $idColissimoService,
                        $productCode,
                        $destinationType
                ),
            'Order'
        );

        // Retrieve colissimo Carrier from ColissimoService
        $colissimoService = new ColissimoService($idColissimoService);
        $colissimoCarrier = Carrier::getCarrierByReference($colissimoService->id_carrier);
        if (!Validate::isLoadedObject($colissimoCarrier)) {
            throw new Exception(
                $logPrefix .
                    sprintf(
                        $this->l('Could not retrieve Carrier with id_reference %s from ColissimoService %s with productCode %s and destinationType %s.', 'Colissimo'),
                        $colissimoService->id_carrier,
                        $colissimoService->id,
                        $productCode,
                        $destinationType
                    )
            );
            return false;
        }
        if (!$colissimoCarrier->active || $colissimoCarrier->deleted) {
            throw new Exception(
                $logPrefix .
                    sprintf(
                        $this->l('Retrieved Carrier with id_reference %s from ColissimoService %s with productCode %s and destinationType %s is inactive or deleted.', 'Colissimo'),
                        $colissimoService->id_carrier,
                        $colissimoService->id,
                        $productCode,
                        $destinationType
                    )
            );
            return false;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                sprintf(
                    $this->l('Retrieved Colissimo carrier %s from ColissimoService %s.', 'Colissimo'),
                    $colissimoService->id_carrier,
                    $colissimoService->id
                ),
            'Order'
        );

        // Use retrieved carrier and skip SF carrier creation; Colissimo should decide by itself which carrier should be used
        $params['carrier'] = $colissimoCarrier;
        $params['skipSfCarrierCreation'] = true;
        return true;
    }

    /**
     * Add Colissimo pickup point in colissimo module DB table; add pickup point/cart association in colissimo module DB table.
     * If these data are properly entered, the colissimo module should be able to handle the rest using the order process native hooks.
     */
    public function afterCartCreation($params)
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Saving Colissimo pickup point.', 'Colissimo'),
            'Order'
        );

        // Retrieve necessary order data
        $apiOrderData = $apiOrder->toArray();
        list($productCode, $colissimoPickupPointId) = explode(':', $apiOrderData['additionalFields']['service_point_id']);

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
                    $this->l('Linking Colissimo pickup point %s to cart %s.', 'Colissimo'),
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
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Zalando and has non-empty "service_point_id" and "service_point_name" additional fields.', 'Colissimo');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'Colissimo');
    }
}
