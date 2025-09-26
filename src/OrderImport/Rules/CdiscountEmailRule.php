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
use ShoppingfeedAddon\OrderImport\OrderCustomerData;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class CdiscountEmailRule extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'CdiscountEmailRule'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        if (preg_match('#^cdiscount$#', strtolower($apiOrder->getChannel()->getName()))) {
            return true;
        }

        return false;
    }

    public function onPreProcess($params)
    {
        /** @var OrderData $orderData */
        $orderData = $params['orderData'];
        /** @var OrderCustomerData $customer */
        $customer = $orderData->getCustomer();

        if ($customer->getEmail() === 'noreply@clemarche.com') {
            $customer->setEmail($orderData->reference . '-' . $customer->getEmail());
            ProcessLoggerHandler::logInfo($this->logPrefix . $this->l('Rule triggered', 'CdiscountEmailRule'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('Rule is applied to all orders coming from CDiscount', 'CdiscountEmailRule');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Rule adds the prefix to email noreply@clemarche.com', 'CdiscountEmailRule');
    }
}
