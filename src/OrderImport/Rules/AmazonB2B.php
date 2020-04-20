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
use Translate;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class AmazonB2B extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // TODO : OrderResource should likely have a "getAdditionalFields" method
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        return preg_match('#^amazon#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && empty($apiOrderAdditionalFields['is_business_order']) === false;
    }

    public function onPreProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'AmazonB2B'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Rule triggered. Payment method change for "amazon b2b"', 'AmazonB2B'),
            'Order'
        );

        $orderData->payment['method'] = 'amazon b2b';

    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'If the order is from Amazon and has \'is_business_order\' set in its additional fields.', 'AmazonB2B');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'Sets the order\'s payment method as \'Amazon B2B\' in the module\'s \'Marketplaces Summary\'.', 'AmazonB2B');
    }
}
