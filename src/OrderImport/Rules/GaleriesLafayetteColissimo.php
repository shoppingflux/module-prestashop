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


use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

class GaleriesLafayetteColissimo extends AbstractColissimo
{

    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'GaleriesLafayetteColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        if (1273 == Tools::strtolower($apiOrder->getChannel()->getId())
            && $this->isModuleColissimoEnabled()
            && !empty($this->getPointId($apiOrder))
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered.', 'GaleriesLafayetteColissimo'),
                'Order'
            );

            return true;
        }

        return false;
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $code = empty($apiOrderData['additionalFields']['type-de-point']) ? 'A2P' : $apiOrderData['additionalFields']['type-de-point'];

        return $code;
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $relayId = empty($apiOrderData['additionalFields']['relais-id']) ? '' : $apiOrderData['additionalFields']['relais-id'];

        return $relayId;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from GaleriesLafayette and has non-empty "other" or "relayID" field.', 'GaleriesLafayetteColissimo');
    }
}
