<?php

namespace ShoppingfeedAddon\OrderImport\Rules;

use Address;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class SetDniToAddress extends RuleAbstract
{
    protected $className = 'SetDniToAddress';

    public function isApplicable(OrderResource $apiOrder)
    {
        $orderArray = $apiOrder->toArray();

        if (empty($orderArray['additionalFields']['buyer_identification_number'])) {
            return false;
        }

        return true;
    }

    public function getDescription()
    {
        return $this->l('Add dni to an address', $this->className);
    }

    public function getConditions()
    {
        return $this->l('If order.additionalFields.buyer_identification_number is not empty', $this->className);
    }

    protected function addDniToAddress(Address $address, OrderResource $apiOrder)
    {
        $orderArray = $apiOrder->toArray();
        $logPrefix = sprintf(
            '[Order: %s]',
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (empty($orderArray['additionalFields']['buyer_identification_number'])) {
            return $address;
        }

        if (empty(Address::$definition['fields']['dni']['validate'])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('DNI validation method is missing', $this->className),
                'Address',
                (int) $address->id
            );

            return $address;
        }

        $dni = $orderArray['additionalFields']['buyer_identification_number'];
        $validateMethod = Address::$definition['fields']['dni']['validate'];

        if (false === is_callable(['Validate', $validateMethod])) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('DNI validation method is not declared in the class Validate', $this->className),
                'Address',
                (int) $address->id
            );

            return $address;
        }

        if (call_user_func(['Validate', $validateMethod], $dni)) {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                $this->l('Rule triggered. Added dni to an address.', $this->className),
                'Address',
                (int) $address->id
            );
            $address->dni = $dni;
        } else {
            ProcessLoggerHandler::logInfo(
                $logPrefix .
                sprintf(
                    $this->l('DNI %s is not valid', $this->className),
                    $dni
                ),
                'Address',
                (int) $address->id
            );
        }

        return $address;
    }

    public function beforeBillingAddressSave($params)
    {
        $params['billingAddress'] = $this->addDniToAddress($params['billingAddress'], $params['apiOrder']);
    }

    public function beforeShippingAddressSave($params)
    {
        $params['shippingAddress'] = $this->addDniToAddress($params['shippingAddress'], $params['apiOrder']);
    }
}
