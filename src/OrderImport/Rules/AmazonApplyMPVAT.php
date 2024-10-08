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
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class AmazonApplyMPVAT extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        $this->logPrefix = sprintf(
            $this->l('[Order: %s]', 'AmazonApplyMPVAT'),
            $apiOrder->getId()
        );
        $this->logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if ($this->configuration['enabled']) {
            if (isset($apiOrderAdditionalFields['is_business_order']) && $apiOrderAdditionalFields['is_business_order'] == 1) {
                if (preg_match('#^amazon#i', $apiOrder->getChannel()->getName())) {
                    ProcessLoggerHandler::logInfo(
                        $this->logPrefix .
                        $this->l('AmazonApplyMPVAT - Rule triggered.', 'AmazonApplyMPVAT'),
                        'Order'
                    );

                    return true;
                }
            }
        }

        return false;
    }

    public function beforeRecalculateOrderPrices($params)
    {
        $params['isUseSfTax'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Apply the Amazon VAT amount to the PrestaShop order in the case of B2B orders', 'AmazonApplyMPVAT'),
                'desc' => $this->l('By activating this option, in the case of B2B orders, the value of the tax sent by Amazon will be recorded in the PrestaShop order. The order will therefore not be affected by VAT according to the PrestaShop configuration', 'AmazonApplyMPVAT'),
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

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfiguration()
    {
        return ['enabled' => false];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Amazon and is a B2B order.', 'AmazonApplyMPVAT');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Apply VAT from Amazon order to PrestaShop order.', 'AmazonApplyMPVAT');
    }
}
