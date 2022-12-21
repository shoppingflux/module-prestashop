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

/**
 * See ticket #30781
 * This is for the "colissimo" module, not to be confused with "soflexibilite" or "soliberte". When an order from Zalando uses Pickup Point shipping with Colissimo,
 * the data for the pickup point are in the additional fields. The data must be set up in the "colissimo" module's table so that colissimo can treat it as a
 * standard order. The goal is to allow the merchant to generate labels with the "colissimo" module so that the standard shipping process can take place.
 */
class ColizeyColissimo extends AbstractColissimo implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ColizeyColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        $module_colissimo = Module::getInstanceByName('colissimo');
        if (strcasecmp('colizey', $apiOrder->getChannel()->getName() === 0)
            && !empty($apiOrderData['additionalFields']['shippingRelayId'])
            && $module_colissimo && $module_colissimo->active
        ) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                    $this->l('Rule triggered.', 'ColizeyColissimo'),
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
        $address['other'] = $this->getRelayId($apiOrder);
        $orderData->shippingAddress = $address;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Colizey and has non-empty \'service_point_id\' and \'service_point_name\' additional fields.', 'ColizeyColissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        return 'A2P';
    }

    protected function getRelayId(OrderResource $apiOrder)
    {
        if ($relayId = parent::getRelayId($apiOrder)) {
            return $relayId;
        }

        $apiOrderData = $apiOrder->toArray();

        return $apiOrderData['additionalFields']['shippingRelayId'];
    }
}
