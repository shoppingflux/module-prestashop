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

use ColissimoCartPickupPoint;
use ColissimoPickupPoint;
use Country;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;
use Validate;

abstract class AbstractColissimo extends RuleAbstract implements RuleInterface
{
    const COLISSIMO_MODULE_NAME = 'colissimo';

    protected $colissimo;

    protected $fileName;

    public function __construct($configuration = [])
    {
        parent::__construct($configuration);

        $this->colissimo = Module::getInstanceByName(self::COLISSIMO_MODULE_NAME);
    }

    protected function isModuleColissimoEnabled()
    {
        try {
            return Validate::isLoadedObject($this->colissimo) && $this->colissimo->active;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function beforeShippingAddressSave($params)
    {
        $this->setDefaultPhoneMobile($params['shippingAddress']->phone_mobile);
    }

    public function afterCartCreation($params)
    {
        if (class_exists(ColissimoPickupPoint::class) === false || class_exists(ColissimoCartPickupPoint::class) === false) {
            return;
        }
        /** @var OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];
        $cart = $params['cart'];
        $shippingAddressObj = new \Address($cart->id_address_delivery);

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'AbstractColissimo'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Saving Colissimo pickup point.', 'AbstractColissimo'),
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
            'country' => Tools::strtoupper(Country::getNameById($params['cart']->id_lang, Country::getByIso($shippingAddress['country']))),
            'iso_country' => $shippingAddress['country'],
            'product_code' => $productCode,
            'network' => '', //TODO; how do we get this field ? It's not in the data sent by SF. Ask Colissimo directly ? Then again, it's not required.
        ];
        $pickupPoint = ColissimoPickupPoint::getPickupPointByIdColissimo($colissimoPickupPointId);
        $pickupPoint->hydrate(array_map('pSQL', $pickupPointData));
        $pickupPoint->save();

        if (empty($pickupPoint) === true) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    sprintf(
                        $this->l('Linking Colissimo pickup failed point %s to cart %s. Pickup point not found in the Colissimo module.', 'AbstractColissimo'),
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
                    $this->l('Linking Colissimo pickup point %s to cart %s.', 'AbstractColissimo'),
                    $colissimoPickupPointId,
                    $params['cart']->id
                ),
            'Order'
        );

        // Save pickup point/cart association
        ColissimoCartPickupPoint::updateCartPickupPoint(
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
        return $this->l('Set the carrier to Colissimo Pickup Point and add necessary data in the colissimo module accordingly.', 'AbstractColissimo');
    }

    abstract protected function getProductCode(OrderResource $apiOrder);

    protected function getRelayId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (empty($apiOrderData['shippingAddress']['relayId'])) {
            return '';
        }

        return $apiOrderData['shippingAddress']['relayId'];
    }

    protected function setDefaultPhoneMobile(&$phone_mobile)
    {
        if (empty($phone_mobile) || Validate::isPhoneNumber($phone_mobile) === false) {
            $phone_mobile = '0611111111';
        }
    }
}
