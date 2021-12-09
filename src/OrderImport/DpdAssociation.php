<?php


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
