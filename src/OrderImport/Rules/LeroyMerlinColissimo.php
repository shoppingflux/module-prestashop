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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class LeroyMerlinColissimo extends AbstractColissimo
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'LeroyMerlinColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (strcasecmp('leroymerlin', $apiOrder->getChannel()->getName()) === 0) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered.', 'LeroyMerlinColissimo'),
                'Order'
            );

            return true;
        }

        return false;
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
        $relayId = empty($apiOrderData['additionalFields']['shipping_pudo_id']) ? '' : $apiOrderData['additionalFields']['shipping_pudo_id'];

        return $relayId;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from LeroyMerlin', 'LeroyMerlinColissimo');
    }
}
