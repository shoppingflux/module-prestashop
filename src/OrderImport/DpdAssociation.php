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

namespace ShoppingfeedAddon\OrderImport;

use Validate;
use Cart;
use Db;
use Address;

class DpdAssociation
{
    public function create(Cart $cart, $relayId)
    {
        if (false == Validate::isLoadedObject($cart)) {
            return false;
        }

        if (empty($relayId)) {
            return false;
        }

        $address = new Address($cart->id_address_delivery);

        if (false == Validate::isLoadedObject($address)) {
            return false;
        }

        $data = [
            'id_customer' => (int)$cart->id_customer,
            'id_cart' => (int)$cart->id,
            'id_carrier' => (int)$cart->id_carrier,
            'service' => 'REL',
            'relay_id' => pSQL($relayId),
            'company' => pSQL($address->company),
            'address1' => pSQL($address->address1),
            'address2' => pSQL($address->address2),
            'postcode' => pSQL($address->postcode),
            'city' => pSQL($address->city),
            'id_country' => (int)$address->id_country
        ];

        try {
            $result = Db::getInstance()->insert('dpdfrance_shipping', $data, false, true, Db::INSERT_IGNORE);
        } catch (\Throwable $e) {
            return false;
        }

        return $result;
    }
}
