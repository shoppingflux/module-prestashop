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

use Module;
use ShoppingFeed\Sdk\Api\Order\OrderItem;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\OrderItemData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedAddon\Services\CdiscountFeeProduct;
use ShoppingfeedProduct;
use Tools;
use Validate;

class Cdiscount extends RuleAbstract implements RuleInterface
{
    /**
     * @var \Shoppingfeed
     */
    protected $module;

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->module = Module::getInstanceByName('shoppingfeed');
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        if ($this->configuration['enabled']) {
            if (preg_match('#^cdiscount$#', strtolower($apiOrder->getChannel()->getName()))) {
                if ($this->getFees($apiOrder) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function onVerifyOrder($params)
    {
        if (empty($params['orderData']) || empty($params['apiOrder']) || empty($params['prestashopProducts'])) {
            return;
        }
        $cdiscountFeeProduct = $this->initCdiscountFeeProduct()->getProduct();
        if (Validate::isLoadedObject($cdiscountFeeProduct) === false) {
            return;
        }
        $cdiscountFeeProduct->id_product_attribute = null;
        $sfp = new ShoppingfeedProduct();
        $sfp->id_product = $cdiscountFeeProduct->id;
        $reference = $this->module->mapReference($sfp);
        $params['prestashopProducts'][$reference] = $cdiscountFeeProduct;
        $orderData = $params['orderData'];
        $item = new OrderItem(
            $reference,
            1,
            $this->getFees($params['apiOrder']),
            0
        );
        $orderData->items[] = new OrderItemData($item);
    }

    protected function getFees(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (empty($apiOrderData['additionalFields']['INTERETBCA'])) {
            return 0;
        }

        return (float) $apiOrderData['additionalFields']['INTERETBCA'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from CDiscount and has the \'INTERETBCA\' field set.', 'Cdiscount');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds an \'Operation Fee\' product to the order, so the amount will show in the invoice.', 'Cdiscount');
    }

    protected function initCdiscountFeeProduct()
    {
        return new CdiscountFeeProduct();
    }

    public function getConfigurationSubform()
    {
        return [
            [
                'type' => 'switch',
                'label' => $this->l('Add a \'CDiscount fees\' product corresponding to management fees, the amount of which will be visible on the order.', 'Cdiscount'),
                'desc' => $this->l('Caution: deactivating this option could distort your accounting and invoicing.', 'Cdiscount'),
                'name' => 'enabled',
                'is_bool' => true,
                'disabled' => Tools::isSubmit('with_factory') === false,
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

    protected function getDefaultConfiguration()
    {
        return [
            'enabled' => true,
        ];
    }
}
