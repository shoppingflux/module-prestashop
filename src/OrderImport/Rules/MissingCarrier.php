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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class MissingCarrier extends RuleAbstract
{
    protected $className = 'MissingCarrier';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', $this->className),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (empty($apiOrderData['shipment']['carrier'])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered.', $this->className),
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
    public function onCarrierRetrieval($params)
    {
        $apiOrder = $params['apiOrder'];
        $params['apiOrderShipment']['carrier'] = 'standard';

        $logPrefix = sprintf(
            $this->l('[Order: %s]', $this->className),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Carrier set to standard', $this->className),
            'Order'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the carrier is missing', $this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the standard carrier.', $this->className);
    }
}
