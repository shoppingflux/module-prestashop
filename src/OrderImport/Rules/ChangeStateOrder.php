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
use Db;
use Address;
use Country;
use Customer;
use Configuration;
use Carrier;
use Order;
use OrderHistory;
use OrderState;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Registry;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ChangeStateOrder extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        if (empty($this->configuration['end_order_state'])) {
            return false;
        }

        return true;
    }

    public function onPostProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ChangeStateOrder'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Set order %s to specified status.', 'ChangeStateOrder'),
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
        $history->changeIdOrderState((int) $this->configuration['end_order_state'], $psOrder, $use_existings_payment);
        // Save all changes
        $history->addWithemail();
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('No specific conditions.', 'ChangeStateOrder');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Set order to specific status after the process.', 'ChangeStateOrder');
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationSubform()
    {
        $context = \Context::getContext();
        $statuses = OrderState::getOrderStates((int) $context->language->id);
        array_unshift($statuses, [
          'id_order_state' => '',
          'name' => '',
        ]);
        return array(
            array(
                'type' => 'select',
                'label' =>
                    $this->l('After a sucessfull order import, turn this order status into ', 'ChangeStateOrder'),
                'name' => 'end_order_state',
                'options' => array(
                    'query' => $statuses,
                    'id' => 'id_order_state',
                    'name' => 'name'
                ),
                'required' => false,
            )
        );
    }
}
