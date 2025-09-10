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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonManomanoTva extends RuleAbstract implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        $isApplicable = false;

        if ('amazon' == \Tools::strtolower($apiOrder->getChannel()->getName())) {
            $isApplicable = true;
        }

        if ('monechelle' == \Tools::strtolower($apiOrder->getChannel()->getName())) {
            $isApplicable = true;
        }

        if ('manomanopro' == \Tools::strtolower($apiOrder->getChannel()->getName())) {
            $isApplicable = true;
        }

        if (empty($this->getTvaNum($apiOrder))) {
            $isApplicable = false;
        }

        return $isApplicable;
    }

    public function beforeShippingAddressSave($params)
    {
        if (empty($params['shippingAddress'])) {
            return;
        }

        if (empty($params['apiOrder'])) {
            return;
        }

        $params['shippingAddress']->vat_number = $this->getTvaNum($params['apiOrder']);
    }

    public function beforeBillingAddressSave($params)
    {
        if (empty($params['billingAddress'])) {
            return;
        }

        if (empty($params['apiOrder'])) {
            return;
        }

        $params['billingAddress']->vat_number = $this->getTvaNum($params['apiOrder']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Add tva to address', 'AmazonManomanoTva');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from ManoMano or Amazon', 'AmazonManomanoTva');
    }

    protected function getTvaNum(OrderResource $apiOrder)
    {
        $data = $apiOrder->toArray();

        if (false == empty($data['additionalFields']['tax_registration_id'])) {
            return $data['additionalFields']['tax_registration_id'];
        }

        if (false == empty($data['additionalFields']['billing_fiscal_number'])) {
            return $data['additionalFields']['billing_fiscal_number'];
        }

        if (false == empty($data['additionalFields']['intraco_vat_number'])) {
            return $data['additionalFields']['intraco_vat_number'];
        }

        return '';
    }
}
