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
use Validate;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Registry;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ChangeStateOrder extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        if (empty($this->configuration['end_order_state'])
            || $this->configuration['end_order_state'] === Configuration::get('PS_OS_PAYMENT')) {
            return false;
        }

        return true;
    }

    public function onPostProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $idOrder = $params['id_order'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ChangeStateOrder'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        $psOrder = new Order($idOrder);

        if (false === Validate::isLoadedObject($psOrder)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                sprintf(
                    $this->l('Can not retrieve a prestashop order. Id Order: %d', 'ChangeStateOrder'),
                    $idOrder
                ),
                'Order',
                $idOrder
            );
            return;
        }

        if (false == $this->isOrderStateValid($this->configuration['end_order_state'])) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                sprintf(
                    $this->l('Invalid order state. ID: %d', 'ChangeStateOrder'),
                    (int)$this->configuration['end_order_state']
                ),
                'Order',
                $idOrder
            );
            return;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Set order %s to specified status.', 'ChangeStateOrder'),
                $idOrder
            ),
            'Order',
            $idOrder
        );

        // Set order to CANCELED
        $history = new OrderHistory();
        $history->id_order = $idOrder;
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
