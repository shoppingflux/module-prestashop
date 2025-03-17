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

class TaxForBusiness extends RuleAbstract implements RuleInterface
{
    /** @var \Context */
    protected $context;

    protected $logPrefix = '';

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->context = \Context::getContext();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        if (!$this->configuration['enabled']) {
            return false;
        }

        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];
        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'TaxForBusiness'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        if (empty($apiOrderAdditionalFields['is_business_order'])) {
            return false;
        }

        return true;
    }

    public function beforeRecalculateOrderPrices($params)
    {
        /** @var OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];

        if (strtolower($apiOrder->getChannel()->getName()) !== 'amazon') {
            $params['skipTax'] = true;
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                $this->l('Rule triggered. Skip tax', 'TaxForBusiness'),
                'Order',
                $params['id_order']
            );
        } else {
            $params['isUseSfTax'] = true;
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                $this->l('Rule triggered. Using Shoppingfeed tax', 'TaxForBusiness'),
                'Order',
                $params['id_order']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the option \'Tax management for B2B orders\' is enabled', 'TaxForBusiness');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Implement tax management', 'TaxForBusiness');
    }

    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Tax management for B2B orders', 'TaxForBusiness'),
                'desc' => $this->getFormDescription(),
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
                \Shoppingfeed::ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION,
                null,
                null,
                $this->id_shop
            ),
            true
        );

        if (empty($rulesConfiguration['ShoppingfeedAddon\OrderImport\Rules\SkipTax']['enabled'])) {
            return ['enabled' => false];
        }

        return ['enabled' => (int) $rulesConfiguration['ShoppingfeedAddon\OrderImport\Rules\SkipTax']['enabled']];
    }

    protected function getFormDescription()
    {
        return $this->context->smarty->fetch('module:shoppingfeed/views/templates/admin/shoppingfeed_order_import_rules/tax-for-business-desc.tpl');
    }
}
