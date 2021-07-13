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

class LaredoutePreimportRule extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        return 'laredoute' == Tools::strtolower(trim($apiOrder->getChannel()->getName()));
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


        if (empty($orderData->shippingAddress['company'])) {
            $orderData->shippingAddress['company'] = $orderData->shippingAddress['lastName'];
        }

        $orderData->shippingAddress['firstName'] = $orderData->billingAddress['firstName'];
        $orderData->shippingAddress['lastName'] = $orderData->billingAddress['lastName'];

        ProcessLoggerHandler::logSuccess(
            $logPrefix .
                $this->l('Rule triggered. Delivery address updated.', 'Shoppingfeed.Rule'),
            'Order'
        );
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order is Laredoute.', 'Shoppingfeed.Rule');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Replace firstname and lastname of a delivery address by firstname and lastname of a billing address', 'Shoppingfeed.Rule');
    }
}
