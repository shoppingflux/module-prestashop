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

use Tools;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class AmazonEbay extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        if (empty($this->configuration['enabled'])) {
            return false;
        }

        $shippingAddress = $apiOrder->getShippingAddress();
        $billingAddress = $apiOrder->getBillingAddress();

        return preg_match('#^(amazon|ebay|laredoute)$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                empty($shippingAddress['firstName'])
                || empty($shippingAddress['lastName'])
                || empty($billingAddress['firstName'])
                || empty($billingAddress['lastName'])
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

        $this->updateAddress($orderData->shippingAddress);
        $this->updateAddress($orderData->billingAddress);

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Addresses updated.', 'AmazonEbay'),
            'Order'
        );
    }

    public function updateAddress(&$address)
    {
        $address['firstName'] = trim($address['firstName']);
        $address['lastName'] = trim($address['lastName']);

        if (empty($address['firstName'])) {
            $fullname = $address['lastName'];
        } else {
            $fullname = $address['firstName'];
        }

        $explodedFullname = explode(" ", $fullname);
        if (isset($explodedFullname[0])) {
            $address['firstName'] = array_shift($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order is from Amazon or Ebay or Laredoute and has an empty "firstname" or "lastname" field in its addresses.', 'AmazonEbay');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Removes everything after the first space in the filled field and moves it to the empty field.', 'AmazonEbay');
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationSubform()
    {
        return array(
            array(
                'type' => 'switch',
                'label' =>
                $this->l('Parse firstname/lastname for Amazon and Ebay orders.', 'AmazonEbay'),
                'name' => 'enabled',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    )
                ),
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConfiguration()
    {
        return array('enabled' => true);
    }
}
