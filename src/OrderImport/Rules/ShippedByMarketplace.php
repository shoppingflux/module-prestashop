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

use Configuration;
use Order;
use OrderHistory;
use OrderState;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use StockAvailable;
use Tools;
use Validate;

/**
 * For orders managed directly by a marketplace, the product quantity
 * should not be impacted on Prestashop.
 * Therefore, we'll increase the stock so that it won't be decreased
 * after validating the order.
 */
class ShippedByMarketplace extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        if ($apiOrder->getStatus() == 'shipped') {
            return true;
        }

        if ($this->isShippedByMarketplace($apiOrder)) {
            return true;
        }

        return false;
    }

    public function onVerifyOrder($params)
    {
        $apiOrder = $params['apiOrder'];
        if ($this->isShippedByMarketplace($apiOrder)) {
            if (Configuration::get(\Shoppingfeed::ORDER_IMPORT_SHIPPED_MARKETPLACE) === false) {
                $this->logSkipImport($apiOrder);
                $params['isSkipImport'] = true;
            }
        } elseif ($apiOrder->getStatus() == 'shipped' && Configuration::get(\Shoppingfeed::ORDER_IMPORT_SHIPPED) === false) {
            $this->logSkipImport($apiOrder);
            $params['isSkipImport'] = true;
        }
    }

    public function checkProductStock($params)
    {
        $psProduct = $params['psProduct'];
        $apiOrder = $params['apiOrder'];
        $apiProduct = $params['apiProduct'];

        // We directly add the ordered quantity; it will be deduced when
        // the order is validated
        $currentStock = StockAvailable::getQuantityAvailableByProduct(
            $psProduct->id,
            $psProduct->id_product_attribute,
            $params['id_shop']
        );
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ShippedByMarketplace'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Order is managed by marketplace %s, increase product %s stock : original %d, add %d.', 'ShippedByMarketplace'),
                $apiOrder->getChannel()->getName(),
                $apiProduct->reference,
                (int) $currentStock,
                (int) $apiProduct->quantity
            ),
            'Order'
        );
        StockAvailable::updateQuantity(
            $psProduct->id,
            $psProduct->id_product_attribute,
            (int) $apiProduct->quantity,
            $params['id_shop']
        );
    }

    public function onPostProcess($params)
    {
        if (empty($params['sfOrder'])) {
            return false;
        }

        if (empty($params['apiOrder'])) {
            return false;
        }

        if (empty($params['id_order'])) {
            return false;
        }

        $apiOrder = $params['apiOrder'];
        $idOrder = $params['id_order'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'TestingOrder'),
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
                (int) $params['sfOrder']->id_order
            );

            return;
        }

        if (empty($this->configuration['end_order_state_shipped']) === false) {
            $changeStateId = $this->configuration['end_order_state_shipped'];
        } else {
            $changeStateId = (int) Configuration::get('PS_OS_DELIVERED');
        }

        if (false == $this->isOrderStateValid($changeStateId)) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                sprintf(
                    $this->l('Invalid order state. ID: %d', 'ShippedByMarketplace'),
                    (int) $changeStateId
                ),
                'Order',
                $idOrder
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Set order %s to DELIVERED', 'ShippedByMarketplace'),
                $idOrder
            ),
            'Order',
            $idOrder
        );

        // Set order to DELIVERED
        $history = new OrderHistory();
        $history->id_order = $idOrder;
        $use_existings_payment = true;
        $history->changeIdOrderState($changeStateId, $psOrder, $use_existings_payment);
        // Save all changes
        $history->addWithemail();
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If order is shipped by the marketplace OR import order status already as Shipped.', 'ShippedByMarketplace');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Increase stocks before order. Set order to DELIVERED after the process or status configured behind.', 'ShippedByMarketplace');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSubform()
    {
        $context = \Context::getContext();
        $statuses = OrderState::getOrderStates((int) $context->language->id);
        array_unshift($statuses, [
          'id_order_state' => '',
          'name' => '',
        ]);
        $states[] = [
            'type' => 'select',
            'label' => $this->l('After a shipped order or an order shipped by Amazon, CDiscount or Manomano import, turn this order status into', 'ShippedByMarketplace'),
            'desc' => $this->l('By default: Delivered &', 'ShippedByMarketplace'),
            'name' => 'end_order_state_shipped',
            'options' => [
                'query' => $statuses,
                'id' => 'id_order_state',
                'name' => 'name',
            ],
            'required' => false,
        ];

        return $states;
    }

    /**
     * @param OrderResource $apiOrder
     *
     * @return bool
     */
    protected function isShippedAmazon(OrderResource $apiOrder)
    {
        try {
            return strpos(strtolower($apiOrder->getPaymentInformation()['method']), 'afn') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param OrderResource $apiOrder
     *
     * @return bool
     */
    protected function isShippedCdiscount(OrderResource $apiOrder)
    {
        try {
            return strpos(strtolower($apiOrder->getPaymentInformation()['method']), 'clogistique') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param OrderResource $apiOrder
     *
     * @return bool
     */
    protected function isShippedManomano(OrderResource $apiOrder)
    {
        try {
            return strtolower($apiOrder->toArray()['additionalFields']['env']) == 'epmm';
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function isShippedByMarketplace(OrderResource $apiOrder)
    {
        if ($this->isShippedAmazon($apiOrder)) {
            return true;
        }

        if ($this->isShippedCdiscount($apiOrder)) {
            return true;
        }

        if ($this->isShippedManomano($apiOrder)) {
            return true;
        }

        if (false == empty($apiOrder->toArray()['fulfilledBy'])) {
            if (Tools::strtolower($apiOrder->toArray()['fulfilledBy']) == 'channel') {
                return true;
            }
        }

        return false;
    }

    protected function logSkipImport($apiOrder)
    {
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ShippedByMarketplace'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Rule triggered. Import should be skipped.', 'Shoppingfeed.Rule'),
            'Order'
        );
    }
}
