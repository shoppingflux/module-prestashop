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

class SkipTax extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    public function isApplicable(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        $this->logPrefix = sprintf(
            $this->l('[Order: %s]', 'SkipTax'),
            $apiOrder->getId()
        );
        $this->logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (
            ((empty($apiOrderAdditionalFields['is_business_order']) === false ||
                 preg_match('#^retif#i', $apiOrder->getChannel()->getName()) === true)
            && $this->configuration['enabled']) === false
        ) {
            return false;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('SkipTax - Rule triggered.', 'SkipTax'),
            'Order'
        );

        return true;
    }

    public function beforeRecalculateOrderPrices($params)
    {
        $params['skipTax'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Set VAT to O for business order', 'SkipTax'),
                'desc' => $this->l('By activating this option, VAT of imported order is set to 0%.', 'SkipTax'),
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
        return $this->l('If the order is a business order.', 'TestingOrder');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set VAT to O if skip tax is enabled and is_business_order.', 'TestingOrder');
    }
}
