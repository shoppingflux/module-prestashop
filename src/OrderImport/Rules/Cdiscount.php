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

use Configuration;
use Shoppingfeed;
use ShoppingFeed\Sdk\Api\Order\OrderItem;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\OrderItemData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedAddon\Services\CdiscountFeeProduct;
use Tools;

class Cdiscount extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && $this->getFees($apiOrder) > 0;
    }

    public function onPreProcess($params)
    {
        if (empty($params['orderData']) || empty($params['apiOrder'])) {
            return;
        }
        /** @var OrderData $orderData */
        $orderData = $params['orderData'];
        $item = new OrderItem(
            $this->getReference(),
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

    protected function getReference()
    {
        $product = $this->initCdiscountFeeProduct()->getProduct();
        $referenceFormat = Configuration::get(Shoppingfeed::PRODUCT_FEED_REFERENCE_FORMAT);

        if (empty($referenceFormat)) {
            return $product->id;
        }

        if ($referenceFormat == 'supplier_reference') {
            return $product->mpn;
        }

        return $product->{$referenceFormat};
    }

    protected function initCdiscountFeeProduct()
    {
        return new CdiscountFeeProduct();
    }
}
