<?php
/**
 *
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
 *
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
                        'id' => 'ok',
                        'value' => 1,
                    ),
                    array(
                        'id' => 'ko',
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
