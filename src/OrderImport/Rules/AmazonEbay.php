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

class AmazonEbay extends RuleAbstract implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        if (empty($this->configuration['enabled'])) {
            return false;
        }

        $shippingAddress = $apiOrder->getShippingAddress();
        $billingAddress = $apiOrder->getBillingAddress();
        $shippingAddressFirstName = $shippingAddress['firstName'] === '.' ? '' : $shippingAddress['firstName'];
        $shippingAddressLastName = $shippingAddress['lastName'] === '.' ? '' : $shippingAddress['lastName'];
        $billingAddressFirstName = $billingAddress['firstName'] === '.' ? '' : $billingAddress['firstName'];
        $billingAddressLastName = $billingAddress['lastName'] === '.' ? '' : $billingAddress['lastName'];

        return preg_match('#^(amazon|ebay|laredoute|laredoutemirakl|alltricks|miravia)$#', \Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                empty($shippingAddressFirstName)
                || empty($shippingAddressLastName)
                || empty($billingAddressFirstName)
                || empty($billingAddressLastName)
            );
    }

    /**
     * We have to do this on preprocess, as the fields may be used in various
     * steps
     *
     * @param array $params
     */
    public function onPreProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'AmazonEbay'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        $this->_updateAddress(
            $orderData->shippingAddress,
            $apiOrder->getChannel()->getName()
        );
        $this->_updateAddress(
            $orderData->billingAddress,
            $apiOrder->getChannel()->getName()
        );

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Addresses updated.', 'AmazonEbay'),
            'Order'
        );
    }

    /**
     * Update address by splitting firstname and lastname
     *
     * @param array $address
     * @param string $channel
     *
     * @return void
     */
    private function _updateAddress(array &$address, $channel)
    {
        $address['firstName'] = trim($address['firstName']);
        $address['lastName'] = trim($address['lastName']);

        if (!empty($address['lastName']) && !empty($address['firstName'])) {
            return;
        }

        if (empty($address['firstName'])) {
            $fullname = $address['lastName'];
        } else {
            $fullname = $address['firstName'];
        }

        $explodedFullname = explode(' ', $fullname);
        if (empty($explodedFullname[0]) === false && preg_match('#^(ebay)$#', \Tools::strtolower($channel))) {
            $address['firstName'] = array_shift($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        } else {
            $address['lastName'] = array_shift($explodedFullname);
            $address['firstName'] = implode(' ', $explodedFullname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from Amazon or Ebay or Laredoute or Alltricks and has an empty "firstname" or "lastname" field in its addresses.', 'AmazonEbay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Removes everything after the first space in the filled field and moves it to the empty field.', 'AmazonEbay');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Parse firstname/lastname for Amazon, Ebay, Laredoute and Alltricks orders.', 'AmazonEbay'),
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
        return ['enabled' => true];
    }
}
