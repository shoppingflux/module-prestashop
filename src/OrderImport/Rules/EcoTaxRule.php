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

class EcoTaxRule extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        return (bool) $this->configuration['enabled'];
    }

    public function beforeRecalculateOrderPrices($params)
    {
        $params['isUseEcotax'] = (bool) $this->configuration['enabled'];
    }

    public function getConditions()
    {
        return $this->l('If the option \'Eco-tax display\' is enabled', 'EcoTaxRule');
    }

    public function getDescription()
    {
        return $this->l('Display product eco-tax on imported orders and invoices', 'EcoTaxRule');
    }

    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Eco-tax display', 'EcoTaxRule'),
                'desc' => $this->l(
                    'By enabling this option, the eco-tax specified on product records will be displayed on your orders and invoices',
                    'EcoTaxRule'
                ),
                'name' => 'enabled',
                'is_bool' => true,
                'values' => [
                    ['id' => 'ok', 'value' => 1],
                    ['id' => 'ko', 'value' => 0],
                ],
            ],
        ];
    }

    public function getDefaultConfiguration()
    {
        return ['enabled' => false];
    }
}
