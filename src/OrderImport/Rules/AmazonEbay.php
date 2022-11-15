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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

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

        return preg_match('#^(amazon|ebay|laredoute|alltricks)$#', Tools::strtolower($apiOrder->getChannel()->getName()))
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

        if (empty($address['firstName'])) {
            $fullname = $address['lastName'];
        } else {
            $fullname = $address['firstName'];
        }

        $explodedFullname = explode(' ', $fullname);
        if (empty($explodedFullname[0]) === false && preg_match('#^(ebay)$#', Tools::strtolower($channel))) {
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
