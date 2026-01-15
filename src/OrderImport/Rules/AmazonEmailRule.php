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

class AmazonEmailRule extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        if (!$this->configuration['enabled']) {
            return false;
        }

        $this->logPrefix = sprintf(
            $this->l('[Order: %s][%s] %s | ', 'AmazonEmailRule'),
            $apiOrder->getId(),
            $apiOrder->getReference(),
            self::class
        );

        if (preg_match('#^amazon$#', strtolower($apiOrder->getChannel()->getName()))) {
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
            ProcessLoggerHandler::logInfo($this->logPrefix . $this->l('Rule triggered', 'AmazonEmailRule'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('Rule is applied to all orders coming from Amazon', 'AmazonEmailRule');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Rule adds the prefix to email noreply@clemarche.com', 'AmazonEmailRule');
    }

    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Changing the email address for orders from the \'Invoice by Amazon\'', 'AmazonEmailRule'),
                'desc' => $this->l('one email per order', 'AmazonEmailRule'),
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
}
