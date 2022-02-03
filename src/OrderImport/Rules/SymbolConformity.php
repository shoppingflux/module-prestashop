<?php
/**
 *
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
 *
 */

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingfeedAddon\Services\SymbolValidator;
use Tools;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Validate;
use Address;

class SymbolConformity extends RuleAbstract implements RuleInterface
{
    /** @var SymbolValidator*/
    protected $validator;

    public function __construct($configuration = array())
    {
        parent::__construct($configuration);

        $this->validator = new SymbolValidator();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        if ($this->configuration['enabled']) {
            return true;
        }

        return false;
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
            $this->l('[Order: %s]', 'SymbolConformity'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        $this->updateAddress($orderData->shippingAddress);
        $this->updateAddress($orderData->billingAddress);

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Addresses updated.', 'SymbolConformity'),
            'Order'
        );
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('All orders', 'SymbolConformity');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('By activating this option, special characters prohibited by Prestashop will be removed from the order information. Be careful, this could falsify the delivery or billing data.', 'SymbolConformity');
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationSubform()
    {
        return array(
            array(
                'type' => 'switch',
                'label' =>
                $this->l('Conformity of characters', 'SymbolConformity'),
                'desc' =>
                    $this->l('By activating this option, special characters prohibited by Prestashop will be removed from the order information. Be careful, this could falsify the delivery or billing data.', 'SymbolConformity'),
                'name' => 'enabled',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'ok',
                        'value' => 1,
                    ),
                    array(
                        'id' => 'ko',
                        'value' => 0,
                    )
                ),
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConfiguration()
    {
        return array('enabled' => false);
    }

    protected function updateAddress(&$address)
    {
        $this->validator->validate(
            $address['firstName'],
            [
                'Validate',
                Address::$definition['fields']['firstname']['validate']
            ]
        );
        $this->validator->validate(
            $address['lastName'],
            [
                'Validate',
                Address::$definition['fields']['lastname']['validate']
            ]
        );
        $this->validator->validate(
            $address['company'],
            [
                'Validate',
                Address::$definition['fields']['company']['validate']
            ]
        );
        $this->validator->validate(
            $address['street'],
            [
                'Validate',
                Address::$definition['fields']['address1']['validate']
            ]
        );
        $this->validator->validate(
            $address['street2'],
            [
                'Validate',
                Address::$definition['fields']['address2']['validate']
            ]
        );
        $this->validator->validate(
            $address['postalCode'],
            [
                'Validate',
                Address::$definition['fields']['postcode']['validate']
            ]
        );
        $this->validator->validate(
            $address['city'],
            [
                'Validate',
                Address::$definition['fields']['city']['validate']
            ]
        );
        $this->validator->validate(
            $address['phone'],
            [
                'Validate',
                Address::$definition['fields']['phone']['validate']
            ]
        );
        $this->validator->validate(
            $address['mobilePhone'],
            [
                'Validate',
                Address::$definition['fields']['phone_mobile']['validate']
            ]
        );
    }
}
