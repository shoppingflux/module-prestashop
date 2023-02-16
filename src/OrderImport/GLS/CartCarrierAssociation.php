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

namespace ShoppingfeedAddon\OrderImport\GLS;

use Address;
use Cart;
use Configuration;
use Country;
use Db;
use Exception;

class CartCarrierAssociation
{
    protected $db;

    protected $glsAdapter;

    public function __construct(AdapterInterface $glsAdapter)
    {
        $this->db = Db::getInstance();
        $this->glsAdapter = $glsAdapter;
    }

    public function create(Cart $cart, $relayId)
    {
        $relay_detail = $this->glsAdapter->getRelayDetail($relayId);

        if (empty($relay_detail)) {
            throw new Exception('Data of relay point is missing');
        }

        $id_country = Country::getByIso($relay_detail['Country']);
        $address = new Address($cart->id_address_delivery);
        $gls_product = $this->glsAdapter->getGlsProductCode(
            Configuration::get('GLS_GLSRELAIS_ID', (int) $cart->id_carrier, $cart->id_shop_group, $cart->id_shop),
            $relay_detail['Country']
        );

        $this->db->delete('gls_cart_carrier', 'id_cart = "' . pSQL($cart->id) . '"');
        $sql = 'INSERT IGNORE INTO ' . $this->db->getPrefix() . "gls_cart_carrier VALUES (
                '" . (int) $cart->id . "',
                '" . (int) $cart->id_customer . "',
                '" . (int) Configuration::get('GLS_GLSRELAIS_ID', (int) $cart->id_carrier, $cart->id_shop_group, $cart->id_shop) . "',
                '" . pSQL($gls_product) . "',
                '" . pSQL($relayId) . "',
                '" . pSQL($relay_detail['Name1']) . "',
                '" . pSQL($relay_detail['Street1']) . "',
                '" . pSQL($relay_detail['Street2']) . "',
                '" . pSQL($relay_detail['ZipCode']) . "',
                '" . pSQL($relay_detail['City']) . "',
                '" . pSQL($relay_detail['Phone']) . "',
                '" . pSQL($relay_detail['Mobile']) . "',
                '" . pSQL($address->phone_mobile) . "',
                '" . (int) $id_country . "',
                '" . pSQL(json_encode($relay_detail['GLSWorkingDay'])) . "'
            )";

        return $this->db->execute($sql);
    }
}