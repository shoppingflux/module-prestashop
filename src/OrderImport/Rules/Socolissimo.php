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
use Customer;
use Configuration;
use Carrier;
use Translate;

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Registry;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class Socolissimo extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // There's no check on the carrier name in the old module, so we won't
        // do it here either
        $module_soliberte = Module::getInstanceByName('soliberte');
        if ($module_soliberte && $module_soliberte->active) {
            Registry::set(self::class . '_id_shipping_address', null);
            return true;
        }

        $module_soflexibilite = Module::getInstanceByName('soflexibilite');
        if ($module_soflexibilite && $module_soflexibilite->active
            && (
                class_exists('SoFlexibiliteDelivery')
                || class_exists('SoColissimoFlexibiliteDelivery')
            )
        ) {
            Registry::set(self::class . '_id_shipping_address', null);
            return true;
        }
    }

    public function afterCartCreation($params)
    {
        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Socolissimo'),
            $params['apiOrder']->getId()
        );
        $logPrefix .= '[' . $params['apiOrder']->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
                $this->l('Rule triggered.', 'Socolissimo'),
            'Order'
        );

        Registry::set(self::class . '_id_shipping_address', (int)$params['cart']->id_address_delivery);

        $module_soliberte = Module::getInstanceByName('soliberte');
        if ($module_soliberte && $module_soliberte->active) {
            $result = $this->insertSoLiberteData($params['cart']);

            if ($result) {
                ProcessLoggerHandler::logSuccess(
                    $logPrefix .
                        $this->l('Data inserted in soliberte module.', 'Socolissimo'),
                    'Order'
                );
            } else {
                ProcessLoggerHandler::logError(
                    $logPrefix .
                        $this->l('Failed to insert data in soliberte module.', 'Socolissimo'),
                    'Order'
                );
            }

            return $result;
        }

        $module_soflexibilite = Module::getInstanceByName('soflexibilite');
        if ($module_soflexibilite && $module_soflexibilite->active
            && (
                class_exists('SoFlexibiliteDelivery')
                || class_exists('SoColissimoFlexibiliteDelivery')
            )
        ) {
            $result = $this->insertSoFlexibiliteData($params['cart']);

            if ($result) {
                ProcessLoggerHandler::logSuccess(
                    $logPrefix .
                        $this->l('Data inserted in soflexibilite module.', 'Socolissimo'),
                    'Order'
                );
            } else {
                ProcessLoggerHandler::logError(
                    $logPrefix .
                        $this->l('Failed to insert data in soflexibilite module.', 'Socolissimo'),
                    'Order'
                );
            }

            return $result;
        }
    }

    public function afterOrderCreation($params)
    {
        // Avoid SoColissimo module to change the address by the one he created
        $id_shipping_address = Registry::get(self::class . '_id_shipping_address');
        if ($id_shipping_address) {
            Registry::set(self::class . '_id_shipping_address', null);
            Db::getInstance()->update(
                'orders',
                array('id_address_delivery' => (int)$id_shipping_address),
                'id_order = ' . (int)$params['id_order']
            );
        }
    }

    protected function insertSoLiberteData($cart)
    {
        $shippingAddress = new Address((int)$cart->id_address_delivery);
        $shippingCountry = new Country($shippingAddress->id_country);
        $customer = new Customer((int) $cart->id_customer);

        $socotable_name = 'socolissimo_delivery_info';

        $socovalues = array(
            'id_cart' => (int) $cart->id,
            'id_customer' => (int) $customer->id,
            'prfirstname' => pSQL($shippingAddress->firstname),
            'cename' => pSQL($shippingAddress->lastname),
            'cefirstname' => pSQL($shippingAddress->firstname),
            'cecountry' => pSQL($shippingCountry->iso_code),
            'ceemail' => pSQL($customer->email),
        );
        return Db::getInstance()->insert($socotable_name, $socovalues);
    }

    protected function insertSoFlexibiliteData($cart)
    {
        $shippingAddress = new Address((int)$cart->id_address_delivery);
        if ($shippingAddress->phone_mobile) {
            $phone = $shippingAddress->phone_mobile;
        } else {
            $phone = $shippingAddress->phone;
        }
        $shippingCountry = new Country($shippingAddress->id_country);
        $customer = new Customer((int)$cart->id_customer);

        // SoFlexibilite module may use a different class name depending of the
        // module's version.
        // Version 2.0 seems to be using the class SoColissimoFlexibiliteDelivery
        // and versions 3.0 are using the class SoFlexibiliteDelivery
        if (class_exists('SoFlexibiliteDelivery')) {
            $so_delivery = new \SoFlexibiliteDelivery();
        } else {
            $so_delivery = new \SoColissimoFlexibiliteDelivery();
        }

        $so_delivery->id_cart = (int)$cart->id;
        $so_delivery->id_order = -time();
        $so_delivery->id_point = null;
        $so_delivery->id_customer = (int)$customer->id;
        $so_delivery->firstname = $shippingAddress->firstname;
        $so_delivery->lastname = $shippingAddress->lastname;
        $so_delivery->company = $shippingAddress->company;
        $so_delivery->telephone = $phone;
        $so_delivery->email = $customer->email;
        $so_delivery->postcode = $shippingAddress->postcode;
        $so_delivery->city = $shippingAddress->city;
        $so_delivery->country = $shippingCountry->iso_code;
        $so_delivery->address1 = $shippingAddress->address1;
        $so_delivery->address2 = $shippingAddress->address2;

        // determine type
        $soflexibilite_conf_key = array(
            'SOFLEXIBILITE_DOM_ID',
            'SOFLEXIBILITE_DOS_ID',
            'SOFLEXIBILITE_BPR_ID',
            'SOFLEXIBILITE_A2P_ID'
        );
        $conf = Configuration::getMultiple($soflexibilite_conf_key, null, null, null);

        $carrier = new Carrier($cart->id_carrier);
        if (isset($carrier->id_reference)) {
            $id_reference = $carrier->id_reference;
        } else {
            $id_reference = $carrier->id;
        }

        if ($id_reference == $conf['SOFLEXIBILITE_DOM_ID'] ||
            $carrier->id == $conf['SOFLEXIBILITE_DOM_ID']
        ) {
            $so_delivery->type = 'DOM';
        }

        if ($id_reference == $conf['SOFLEXIBILITE_DOS_ID'] ||
            $carrier->id == $conf['SOFLEXIBILITE_DOS_ID']
        ) {
            $so_delivery->type = 'DOS';
        }

        if ($id_reference == $conf['SOFLEXIBILITE_BPR_ID'] ||
            $carrier->id == $conf['SOFLEXIBILITE_BPR_ID']
        ) {
            $so_delivery->type = 'BPR';
        }

        if ($id_reference == $conf['SOFLEXIBILITE_A2P_ID'] ||
            $carrier->id == $conf['SOFLEXIBILITE_A2P_ID']
        ) {
            $so_delivery->type = 'A2P';
        }

        return (bool)$so_delivery->saveDelivery();
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the \'soliberte\' or \'soflexibilite\' module is installed.', 'Socolissimo');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the corresponding module\'s table.', 'Socolissimo');
    }
}
