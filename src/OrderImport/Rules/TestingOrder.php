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

use Carrier;
use Module;
use Order;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class TestingOrder extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // There's no check on the carrier name in the old module, so we won't
        // do it here either
        $orderRawData = $apiOrder->toArray();
        if ($orderRawData['isTest'] && ((int) $this->configuration['order_status_after_test_order'])) {
            return true;
        }

        return false;
    }

    public function onPostProcess($params)
    {
        if (empty($params['sfOrder'])) {
            return false;
        }

        if (empty($params['apiOrder'])) {
            return false;
        }

        if (empty($params['orderData'])) {
            return false;
        }

        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $idOrderState = (int) \Configuration::get('PS_OS_CANCELED');

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'TestingOrder'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (false == $this->isOrderStateValid($idOrderState)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                sprintf(
                    $this->l('Invalid order state. ID: %d', 'TestingOrder'),
                    $idOrderState
                ),
                'Order',
                $params['sfOrder']->id_order
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Set order %s to CANCELED', 'TestingOrder'),
                $params['sfOrder']->id_order
            ),
            'Order',
            $params['sfOrder']->id_order
        );
        $psOrder = new \Order($params['sfOrder']->id_order);
        // Set order to CANCELED
        $history = new \OrderHistory();
        $history->id_order = $params['sfOrder']->id_order;
        $use_existings_payment = true;
        $history->changeIdOrderState((int) $idOrderState, $psOrder, $use_existings_payment);
        // Save all changes
        $history->addWithemail();
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is a test.', 'TestingOrder');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('After a successfull test import, turn this order into \'Canceled\' status', 'TestingOrder');
    }

    public function getConfigurationSubform()
    {
        $context = \Context::getContext();

        $states[] = [
            'type' => 'switch',
            'label' => $this->l('After a successfull test import, turn this order into \'Canceled\' status', 'TestingOrder'),
            'name' => 'order_status_after_test_order',
            'required' => false,
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'ok',
                    'value' => 1,
                ],
                [
                    'id' => 'ko',
                    'value' => 0,
                ],
            ],
        ];

        return $states;
    }

    protected function getDefaultConfiguration()
    {
        return [
            'order_status_after_test_order' => 1,
        ];
    }
}
