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

use Carrier;
use Configuration;
use Module;
use Order;
use OrderHistory;
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
        if ($orderRawData['isTest']) {
            return true;
        }

        return false;
    }

    public function onPostProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $idOrderState = (int) Configuration::get('PS_OS_CANCELED');

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
        $psOrder = new Order($params['sfOrder']->id_order);
        // Set order to CANCELED
        $history = new OrderHistory();
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
        return $this->l('Set order to CANCELED after the process.', 'TestingOrder');
    }
}
