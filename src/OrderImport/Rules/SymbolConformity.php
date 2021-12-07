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
                        'value' => 1,
                    ),
                    array(
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
