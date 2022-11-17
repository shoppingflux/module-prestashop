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

namespace ShoppingfeedAddon\OrderImport;

use Address;
use Cart;
use Db;
use Validate;

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
            'id_customer' => (int) $cart->id_customer,
            'id_cart' => (int) $cart->id,
            'id_carrier' => (int) $cart->id_carrier,
            'service' => 'REL',
            'relay_id' => pSQL($relayId),
            'company' => pSQL($address->company),
            'address1' => pSQL($address->address1),
            'address2' => pSQL($address->address2),
            'postcode' => pSQL($address->postcode),
            'city' => pSQL($address->city),
            'id_country' => (int) $address->id_country,
        ];

        try {
            $result = Db::getInstance()->insert('dpdfrance_shipping', $data, false, true, Db::INSERT_IGNORE);
        } catch (\Throwable $e) {
            return false;
        }

        return $result;
    }
}
