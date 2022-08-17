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

class GoSportMondialrelay extends AbstractMondialrelay
{
    /** @var Module */
    protected $module_mondialRelay;

    public function isApplicable(OrderResource $apiOrder)
    {
        if (parent::isApplicable($apiOrder) === false) {
            return false;
        }
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        if (empty($apiOrderAdditionalFields['shipping_pudo_id'])
            || $apiOrderAdditionalFields['shipping_type_code']) {
            return false;
        }

        return true;
    }

    public function getRelayId($orderData)
    {
        return $orderData['additionalFields']['shipping_pudo_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If the order comes from Mondial Relay and has non-empty "shipping_pudo_id" and "shipping_type_code" additional fields.', 'Mondial Relay');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Adds the order in the Mondial Relay module\'s table.', 'Mondialrelay');
    }
}
