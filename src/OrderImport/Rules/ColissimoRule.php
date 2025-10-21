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

use Cart;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ColissimoRule extends RuleAbstract implements RuleInterface
{
    public const COLISSIMO_MODULE_NAME = 'colissimo';

    protected $colissimo;

    protected $fileName;

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->colissimo = \Module::getInstanceByName(self::COLISSIMO_MODULE_NAME);
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        return $this->isModuleColissimoEnabled() && false === empty($this->getRelayId($apiOrder));
    }

    protected function isModuleColissimoEnabled()
    {
        try {
            return \Validate::isLoadedObject($this->colissimo) && $this->colissimo->active;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function onPreProcess($params)
    {
        $this->setOther($params);
        $apiOrder = $params['apiOrder'];

        if ($this->isMonechelle($apiOrder)) {
            $this->parseCompoundFirstname($params);
        }
        if ($this->isZalando($apiOrder)) {
            $this->setAddressCompany($params);
        }
        if ($this->isCdiscount($apiOrder)) {
            $this->updateCdiscountAddress($params);
        }
    }

    protected function isMonechelle(OrderResource $apiOrder)
    {
        return 'monechelle' === \Tools::strtolower($apiOrder->getChannel()->getName());
    }

    protected function isCdiscount(OrderResource $apiOrder)
    {
        $apiOrderShipment = $apiOrder->getShipment();

        if (preg_match('#^cdiscount$#', \Tools::strtolower($apiOrder->getChannel()->getName()))) {
            if (in_array($apiOrderShipment['carrier'], ['SO1', 'REL', 'RCO'])) {
                return true;
            }
        }

        return false;
    }

    protected function isEbay(OrderResource $apiOrder)
    {
        return (bool) preg_match('#^ebay#i', $apiOrder->getChannel()->getName());
    }

    protected function isManomano(OrderResource $apiOrder)
    {
        return (bool) preg_match('#^mamomano#i', $apiOrder->getChannel()->getName());
    }

    protected function isZalando(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (preg_match('#^zalando#', \Tools::strtolower($apiOrder->getChannel()->getName()))) {
            if (false === empty($apiOrderData['additionalFields']['service_point_id'])) {
                if (false === empty($apiOrderData['additionalFields']['service_point_name'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function updateCdiscountAddress($params)
    {
        $apiOrder = $params['apiOrder'];
        $address = $params['orderData']->shippingAddress;
        // Workaround for CDiscount usage of last name as pickup-point name
        $relayPointName = $address['lastName'];
        // Check if the company is already filled
        if (false === empty($address['company'])) {
            // When the company is known, we are appending it to the second line of the adresse
            $address['street2'] .= ' ' . $address['company'];
        }

        $address['company'] = $relayPointName;
        // And now we decompose the fullname (in the FirstName field) by last name + first name
        // We consider that what's after the last space is the first name
        $fullname = trim($address['firstName']);
        $explodedFullname = explode(' ', $fullname);
        if (isset($explodedFullname[0])) {
            $address['firstName'] = array_pop($explodedFullname);
            $address['lastName'] = implode(' ', $explodedFullname);
        }

        $params['orderData']->shippingAddress = $address;

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Cdiscount shipping address is updated.', 'Colissimo'),
            'Order'
        );
    }

    protected function setAddressCompany($params)
    {
        $apiOrder = $params['apiOrder'];
        $orderData = $params['orderData'];

        $address = $apiOrder->getShippingAddress();
        $address['address2'] = $address['company'];

        if (false === empty($orderData->additionalFields['service_point_name'])) {
            $address['company'] = $orderData->additionalFields['service_point_name'];
        }

        $orderData->shippingAddress = $address;

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Shipping address is updated to set company and address2.', 'Colissimo'),
            'Order'
        );
    }

    protected function parseCompoundFirstname($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $fullNameArray = explode(' ', trim($orderData->shippingAddress['firstName']));
        $lastName = $orderData->shippingAddress['lastName'];

        if (false === empty($orderData->shippingAddress['company'])) {
            return;
        }
        if (count($fullNameArray) < 2) {
            return;
        }

        $orderData->shippingAddress['firstName'] = $fullNameArray[0];
        $orderData->shippingAddress['lastName'] = implode(' ', array_slice($fullNameArray, 1));
        $orderData->shippingAddress['company'] = sprintf(
            '%s (%s)',
            (string) $lastName,
            (string) $this->getRelayId($params['apiOrder'])
        );

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Shipping address is updated to set firstname, lastname and company.', 'Colissimo'),
            'Order'
        );
    }

    protected function setOther($params)
    {
        $apiOrder = $params['apiOrder'];
        $orderData = $params['orderData'];
        $address = $apiOrder->getShippingAddress();
        $address['other'] = $this->getRelayId($apiOrder);
        $orderData->shippingAddress = $address;
    }

    public function beforeShippingAddressSave($params)
    {
        $this->setDefaultPhoneMobile($params['shippingAddress']->phone_mobile);
    }

    public function afterCartCreation($params)
    {
        $cart = $params['cart'];

        if (false === $cart instanceof \Cart) {
            return;
        }
        $carrier = new \Carrier($cart->id_carrier);
        if ($carrier->external_module_name != $this->colissimo->name) {
            return;
        }
        if (class_exists(\ColissimoPickupPoint::class) === false || class_exists(\ColissimoCartPickupPoint::class) === false) {
            return;
        }
        /** @var OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];
        $shippingAddressObj = new \Address($cart->id_address_delivery);

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Colissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Saving Colissimo pickup point.', 'Colissimo'),
            'Order'
        );
        $colissimoPickupPointId = $this->getRelayId($apiOrder);

        if (empty($colissimoPickupPointId)) {
            return true;
        }

        $shippingAddress = $apiOrder->getShippingAddress();
        $productCode = $this->getProductCode($apiOrder); // hack

        // Save/update Colissimo pickup point
        $pickupPointData = [
            'colissimo_id' => $colissimoPickupPointId,
            'company_name' => $shippingAddressObj->company ? $shippingAddressObj->company : 'undefined',
            'address1' => $shippingAddressObj->address1,
            'address2' => $shippingAddressObj->address2,
            'city' => $shippingAddressObj->city,
            'zipcode' => $shippingAddressObj->postcode,
            'country' => \Tools::strtoupper(\Country::getNameById($params['cart']->id_lang, \Country::getByIso($shippingAddress['country']))),
            'iso_country' => $shippingAddress['country'],
            'product_code' => $productCode,
            'network' => '', // TODO; how do we get this field ? It's not in the data sent by SF. Ask Colissimo directly ? Then again, it's not required.
        ];
        $pickupPoint = \ColissimoPickupPoint::getPickupPointByIdColissimo($colissimoPickupPointId);
        $pickupPoint->hydrate(array_map('pSQL', $pickupPointData));
        $pickupPoint->save();

        if (empty($pickupPoint) === true) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    sprintf(
                        $this->l('Linking Colissimo pickup failed point %s to cart %s. Pickup point not found in the Colissimo module.', 'Colissimo'),
                        $colissimoPickupPointId,
                        $params['cart']->id
                    ),
                'Order'
            );

            return true;
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                sprintf(
                    $this->l('Linking Colissimo pickup point %s to cart %s.', 'Colissimo'),
                    $colissimoPickupPointId,
                    $params['cart']->id
                ),
            'Order'
        );

        // Save pickup point/cart association
        \ColissimoCartPickupPoint::updateCartPickupPoint(
            (int) $params['cart']->id,
            (int) $pickupPoint->id,
            !empty($shippingAddressObj->phone_mobile) ? $shippingAddressObj->phone_mobile : ''
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'Colissimo');
    }

    public function getConditions()
    {
        return $this->l('If the order has non-empty "relayID" field and a carrier belongs the module "colissimo"', 'Colissimo');
    }

    protected function getProductCode(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (false === empty($apiOrderData['additionalFields']['type-de-point'])) {
            return $apiOrderData['additionalFields']['type-de-point'];
        }
        if (false === empty($apiOrderData['additionalFields']['service_point_id'])) {
            $service_point_id = explode(':', $apiOrderData['additionalFields']['service_point_id']);

            if (count($service_point_id) > 1) {
                return $service_point_id[0];
            }
        }

        return 'A2P';
    }

    protected function formatPointId($pointId)
    {
        $parts = explode(':', $pointId);

        return isset($parts[1]) ? trim($parts[1]) : trim($parts[0]);
    }

    protected function getRelayId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (false === empty($apiOrderData['shippingAddress']['relayId'])) {
            return $this->formatPointId($apiOrderData['shippingAddress']['relayId']);
        }
        if ($this->isCdiscount($apiOrder) || $this->isManomano($apiOrder) || $this->isMonechelle($apiOrder)) {
            if (false === empty($apiOrderData['shippingAddress']['other'])) {
                return $this->formatPointId($apiOrderData['shippingAddress']['other']);
            }
        }
        if (false === empty($apiOrderData['shippingAddress']['relayID'])) {
            return $this->formatPointId($apiOrderData['shippingAddress']['relayID']);
        }
        if (false === empty($apiOrderData['additionalFields']['pickup_point_id'])) {
            return $this->formatPointId($apiOrderData['additionalFields']['pickup_point_id']);
        }
        if (false === empty($apiOrderData['additionalFields']['shippingRelayId'])) {
            return $this->formatPointId($apiOrderData['additionalFields']['shippingRelayId']);
        }
        if (false === empty($apiOrderData['additionalFields']['relais-id'])) {
            return $this->formatPointId($apiOrderData['additionalFields']['relais-id']);
        }
        if (false === empty($apiOrderData['additionalFields']['shipping_pudo_id'])) {
            return $this->formatPointId($apiOrderData['additionalFields']['shipping_pudo_id']);
        }
        if (false === empty($apiOrderData['additionalFields']['service_point_id'])) {
            return $this->formatPointId($apiOrderData['additionalFields']['service_point_id']);
        }

        return '';
    }

    protected function setDefaultPhoneMobile(&$phone_mobile)
    {
        if (empty($phone_mobile) || \Validate::isPhoneNumber($phone_mobile) === false) {
            $phone_mobile = '0611111111';
        }
    }
}
