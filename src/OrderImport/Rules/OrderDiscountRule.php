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

use Cart;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\DiscountProvider;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class OrderDiscountRule extends RuleAbstract implements RuleInterface
{
    /** @var \Context */
    protected $context;

    protected $logPrefix = '';
    /** @var DiscountProvider */
    protected $discountProvider;

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->context = \Context::getContext();
        $this->discountProvider = new DiscountProvider();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];
        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'OrderDiscountRule'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        if (!empty($apiOrderAdditionalFields['seller_voucher'])) {
            return true;
        }
        if (!empty($apiOrderAdditionalFields['channel_voucher']) && $this->configuration['enabled']) {
            return true;
        }

        return false;
    }

    public function afterCartCreation($params)
    {
        /** @var \Cart $cart */
        $cart = $params['cart'];
        /** @var OrderData $orderData */
        $orderData = $params['orderData'];
        $cartRule = $this->getCartRule($orderData, $cart);

        if ($cartRule instanceof \CartRule) {
            if ($cart->addCartRule($cartRule->id)) {
                ProcessLoggerHandler::logInfo(
                    $this->logPrefix .
                    sprintf(
                        $this->l('Adding cart rule %d to the cart %d is successful', 'OrderDiscountRule'),
                        $cartRule->id,
                        $cart->id
                    ),
                    'Cart',
                    $cart->id,
                );
            } else {
                ProcessLoggerHandler::logInfo(
                    $this->logPrefix .
                    sprintf(
                        $this->l('Adding cart rule %d to the cart %d is failed', 'OrderDiscountRule'),
                        $cartRule->id,
                        $cart->id
                    ),
                    'Cart',
                    $cart->id,
                );
            }
        }
    }

    public function beforeRecalculateOrderPrices($params)
    {
        /** @var \Cart $cart */
        $cart = $params['cart'];
        /** @var OrderData $orderData */
        $orderData = $params['orderData'];
        $cartRule = $this->getCartRule($orderData, $cart);

        if ($cartRule instanceof \CartRule) {
            $params['discount'] = $cartRule;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the \'seller_voucher\' is sent or \'channel_voucher\' is sent and the \'The import order discount rule\' is enabled', 'OrderDiscountRule');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('The import order discount rule', 'OrderDiscountRule');
    }

    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Import the marketplace discounts', 'OrderDiscountRule'),
                'name' => 'enabled',
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
            ],
        ];
    }

    public function getDefaultConfiguration()
    {
        $rulesConfiguration = json_decode(
            \Configuration::get(
                \Shoppingfeed::IMPORT_MARKETPLACE_DISCOUNT,
                null,
                null,
                $this->id_shop
            ),
            true
        );

        if (empty($rulesConfiguration['ShoppingfeedAddon\OrderImport\Rules\OrderDiscountRule']['enabled'])) {
            return ['enabled' => false];
        }

        return ['enabled' => (int) $rulesConfiguration['ShoppingfeedAddon\OrderImport\Rules\OrderDiscountRule']['enabled']];
    }

    protected function getCartRule(OrderData $orderData, Cart $cart)
    {
        $additionalFields = $orderData->additionalFields;
        $amount = 0;
        $name = '';

        if (!empty($additionalFields['seller_voucher'])) {
            $amount = (float) $additionalFields['seller_voucher'];
            $name = $this->l('Discount (merchant)', 'OrderDiscountRule');
        }
        if ($this->configuration['enabled'] && !empty($additionalFields['channel_voucher'])) {
            $amount = (float) $additionalFields['channel_voucher'];
            $name = $this->l('Discount (marketplace)', 'OrderDiscountRule');
        }

        if (!$amount) {
            return null;
        }

        try {
            $cartRule = $this->discountProvider->getForOrder(
                $orderData->reference,
                $amount,
                $cart->id_currency,
                $cart->id_customer,
                $name
            );
        } catch (\Throwable $e) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                sprintf(
                    $this->l('Creating discount is failed. Message: %s. File: %s. Line: %d', 'OrderDiscountRule'),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                'Cart',
                $cart->id,
            );
            return null;
        }

        return $cartRule;
    }
}
