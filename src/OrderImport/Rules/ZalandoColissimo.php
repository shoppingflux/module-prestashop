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
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * See ticket #30781
 * This is for the "colissimo" module, not to be confused with "soflexibilite" or "soliberte". When an order from Zalando uses Pickup Point shipping with Colissimo,
 * the data for the pickup point are in the additional fields. The data must be set up in the "colissimo" module's table so that colissimo can treat it as a
 * standard order. The goal is to allow the merchant to generate labels with the "colissimo" module so that the standard shipping process can take place.
 */
class ZalandoColissimo extends AbstractColissimo implements RuleInterface
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
        if ($this->isModuleColissimoEnabled()
            && !empty($apiOrderData['additionalFields']['service_point_id'])
            && !empty($apiOrderData['additionalFields']['service_point_name'])
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
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Zalando and has non-empty "service_point_id" and "service_point_name" additional fields.', 'ZalandoColissimo');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'ZalandoColissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $service_point_id = explode(':', $apiOrderData['additionalFields']['service_point_id']);
        if (count($service_point_id) > 1) {
            return $service_point_id[1];
        }

        return 'A2P';
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        list($colissimoPickupPointId) = explode(':', $apiOrderData['additionalFields']['service_point_id']);

        return $colissimoPickupPointId;
    }
}
