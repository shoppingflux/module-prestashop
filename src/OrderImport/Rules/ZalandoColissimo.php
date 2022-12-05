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
class ZalandoColissimo extends AbstractColissimo implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ZalandoColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        if (preg_match('#^zalando#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && $this->isModuleColissimoEnabled()
            && !empty($apiOrderData['additionalFields']['service_point_id'])
            && !empty($apiOrderData['additionalFields']['service_point_name'])
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Rule triggered.', 'ZalandoColissimo'),
                'Order'
            );

            return true;
        }

        return false;
    }

    /**
     * We have to do this on preprocess, as the fields may be used in various
     * steps
     *
     * @param array $params
     */
    public function onPreProcess($params)
    {
        $apiOrder = $params['apiOrder'];
        $orderData = $params['orderData'];

        $address = $apiOrder->getShippingAddress();
        $address['address2'] = $address['company'];
        $address['company'] = $orderData->additionalFields['service_point_name'];
        $address['other'] = $this->getPointId($apiOrder);

        $orderData->shippingAddress = $address;

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ZalandoColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Shipping address updated to set relay ID.', 'ZalandoColissimo'),
            'Order'
        );
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
            return $service_point_id[0];
        }

        return 'A2P';
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        if ($relayId = parent::getPointId($apiOrder)) {
            return $relayId;
        }

        $apiOrderData = $apiOrder->toArray();
        $service_point_id = explode(':', $apiOrderData['additionalFields']['service_point_id']);
        if (count($service_point_id) > 1) {
            return $service_point_id[1];
        }

        return $apiOrderData['additionalFields']['service_point_id'];
    }
}
