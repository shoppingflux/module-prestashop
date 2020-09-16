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

use Module;
use Db;
use Address;
use Country;
use Configuration;
use Carrier;
use Order;
use OrderHistory;
use Tools;
use StockAvailable;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Registry;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
* For orders managed directly by a marketplace, the product quantity
* should not be impacted on Prestashop.
* Therefore, we'll increase the stock so that it won't be decreased
* after validating the order.
*/
class ShippedByMarketplace extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $shippedByMarketplace = [
            'amazon fba',
            'epmm',
            'clogistique'
        ];
        $orderRawData = $apiOrder->toArray();
        return in_array(Tools::strtolower($apiOrder->getChannel()->getName()), $shippedByMarketplace);
    }

    public function checkProductStock($params)
    {
        $psProduct = $params['psProduct'];
        $apiOrder = $params['apiOrder'];
        $apiProduct = $params['apiProduct'];

        // We directly add the ordered quantity; it will be deduced when
        // the order is validated
        $currentStock = StockAvailable::getQuantityAvailableByProduct(
            $psProduct->id,
            $psProduct->id_product_attribute,
            $params['id_shop']
        );
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'ShippedByMarketplace'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            sprintf(
                $this->l('Rule triggered. Order is managed by marketplace %s, increase product %s stock : original %d, add %d.', 'ShippedByMarketplace'),
                $apiOrder->getChannel()->getName(),
                $apiProduct->reference,
                (int) $currentStock,
                (int) $apiProduct->quantity
            ),
            'Order'
        );
        StockAvailable::updateQuantity(
            $psProduct->id,
            $psProduct->id_product_attribute,
            (int) $apiProduct->quantity,
            $this->conveyor['id_shop']
        );
    }

    public function onPostProcess($params)
    {
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'TestingOrder'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Rule triggered. Set order %s to DELIVERED', 'ShippedByMarketplace'),
                $params['sfOrder']->id_order
            ),
            'Order',
            $params['sfOrder']->id_order
        );

        $psOrder = new Order($params['sfOrder']->id_order);
        // Set order to DELIVERED
        $history = new OrderHistory();
        $history->id_order = $params['sfOrder']->id_order;
        $use_existings_payment = true;
        $history->changeIdOrderState((int) Configuration::get('PS_OS_DELIVERED'), $psOrder, $use_existings_payment);
        // Save all changes
        $history->addWithemail();
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If order is shipped by the marketplace.', 'ShippedByMarketplace');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Increase stocks before order. Set order to DELIVERED after the process.', 'ShippedByMarketplace');
    }
}
