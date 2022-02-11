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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use Tools;

class NatureEtDecouvertesColissimo extends AbstractColissimo implements RuleInterface
{
    const MARKET_PLACE = 'NatureEtDecouvertes';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        if (false == $this->isModuleColissimoEnabled()) {
            return false;
        }

        if (Tools::strtolower($apiOrder->getChannel()->getName()) != Tools::strtolower(self::MARKET_PLACE)) {
            return false;
        }

        if (empty($this->getPointId($apiOrder))) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'NatureEtDecouvertesColissimo');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from NatureEtDecouvertes and has non-empty "shipping_pudo_id" additional fields.', 'NatureEtDecouvertesColissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        return 'A2P'; //todo: to verify correctness
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (empty($apiOrderData['additionalFields']['shipping_pudo_id'])) {
            return '';
        }

        return $apiOrderData['additionalFields']['shipping_pudo_id'];
    }
}
