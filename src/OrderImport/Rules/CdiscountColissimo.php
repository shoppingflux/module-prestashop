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
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;

class CdiscountColissimo extends AbstractColissimo implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // Check marketplace, that the additional fields with the pickup point data are there and not empty, and that the "colissimo" module is installed and active
        $apiOrderShipment = $apiOrder->getShipment();

        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && (
                $apiOrderShipment['carrier'] === 'SO1'
                || $apiOrderShipment['carrier'] === 'REL'
                || $apiOrderShipment['carrier'] === 'RCO'
            )
            && $this->isModuleColissimoEnabled()
        ;
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

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'CdiscountColissimo'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';

        $this->updateAddress($orderData->shippingAddress);

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered. Shipping address updated to set relay ID.', 'CdiscountColissimo'),
            'Order'
        );
    }

    /**
     * See old module _getAddress
     * The relay ID is in "lastname", and the actual lastname is appended to "firstname".
     * The relay ID should (apparently) be in "company".
     * The old function didn't alter the API address, so we won't do it either.
     *
     * @param array $address
     */
    public function updateAddress(&$address)
    {
        // Workaround for CDiscount usage of last name as pickup-point name
        $relayId = $address['lastName'];

        // Check if the company is already filled
        if (!empty($address['company'])) {
            // When the company is known, we are appending it to the second line of the adresse
            $address['street2'] .= ' ' . $address['company'];
        }

        $address['company'] = $relayId;

        // And now we decompose the fullname (in the FirstName field) by last name + first name
        // We consider that what's after the last space is the first name
        $fullname = trim($address['firstName']);
        $explodedFullname = explode(' ', $fullname);
        if (isset($explodedFullname[0])) {
            $address['firstName'] = array_pop($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order is from CDiscount and the carrier is \'SO1\', \'REL\' or \'RCO\'.', 'CdiscountColissimo');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Retrieves  the relay ID from the \'lastname\' field and puts it in \'company\'. If a company is already set, appends it to \'address (2)\'. Fills the \'lastname\' field with everything after the first space from \'firstname\'.', 'CdiscountColissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        return 'A2P';
    }

    protected function getPointId(OrderResource $apiOrder)
    {
        $shippingAddress = $apiOrder->getShippingAddress();

        if (empty($shippingAddress['other'])) {
            return '';
        }

        return $shippingAddress['other'];
    }

    public function onCarrierRetrieval($params)
    {
        //todo: should set colissimo carrier as for other marketplace?
        return;
    }
}
