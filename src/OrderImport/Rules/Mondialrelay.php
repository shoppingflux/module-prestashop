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
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use Tools;

class Mondialrelay extends AbstractMondialrelay
{
    /** @var Module */
    protected $module_mondialRelay;

    public function isApplicable(OrderResource $apiOrder)
    {
        if (parent::isApplicable($apiOrder) === false) {
            return false;
        }
        $apiOrderShipment = $apiOrder->getShipment();

        // Check only for name presence in the string; the relay ID could be appended
        // to the field (see ShoppingfeedAddon\OrderImport\Rules\RueducommerceMondialrelay)
        if (preg_match('#mondial relay#', Tools::strtolower($apiOrderShipment['carrier']))) {
            return true;
        }

        if (preg_match('#Livraison Magasin produit jusqu\'à 30 kg#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#Livraison Point MR 24R#i', $apiOrderShipment['carrier'])) {
            return true;
        }

        if (preg_match('#^rel$#i', trim($apiOrderShipment['carrier']))) {
            return true;
        }

        return false;
    }

    public function getRelayId($orderData)
    {
        return $orderData->shippingAddress['other'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order has \'Mondial Relay\' in its carrier name.', 'Mondialrelay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
