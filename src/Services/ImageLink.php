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

namespace ShoppingfeedAddon\Services;

use Configuration;
use Context;
use Image;
use Module;
use Shop;
use Tools;

class ImageLink
{
    /** @var bool */
    protected $allow;

    public function __construct()
    {
        $this->allow = (int) Configuration::get('PS_REWRITING_SETTINGS');
    }

    /**
     * @uses The same as Link::getImageLink() except it takes in account a shop id
     *
     * @return string
     */
    public function getImageLink($name, $ids, $type = null, $idShop = null)
    {
        $notDefault = false;

        if ($idShop) {
            $shop = new Shop($idShop);
        } else {
            $shop = Context::getContext()->shop;
        }

        static $watermarkLogged = null;
        static $watermarkHash = null;
        static $psLegacyImages = null;
        if ($watermarkLogged === null) {
            $watermarkLogged = Configuration::get('WATERMARK_LOGGED');
            $watermarkHash = Configuration::get('WATERMARK_HASH');
            $psLegacyImages = Configuration::get('PS_LEGACY_IMAGES');
        }

        // Check if module is installed, enabled, customer is logged in and watermark logged option is on
        if (!empty($type) && $watermarkLogged &&
            (Module::isInstalled('watermark') && Module::isEnabled('watermark')) &&
            isset(Context::getContext()->customer->id)
        ) {
            $type .= '-' . $watermarkHash;
        }

        // legacy mode or default image
        $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . '-' . Context::getContext()->shop->theme_name . '.jpg')) ? '-' . Context::getContext()->shop->theme_name : '');
        if (($psLegacyImages
                && (file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . $theme . '.jpg')))
            || ($notDefault = strpos($ids, 'default') !== false)) {
            if ($this->allow == 1 && !$notDefault) {
                $uriPath = __PS_BASE_URI__ . $ids . ($type ? '-' . $type : '') . $theme . '/' . $name . '.jpg';
            } else {
                $uriPath = _THEME_PROD_DIR_ . $ids . ($type ? '-' . $type : '') . $theme . '.jpg';
            }
        } else {
            // if ids if of the form id_product-id_image, we want to extract the id_image part
            $splitIds = explode('-', $ids);
            $idImage = (isset($splitIds[1]) ? $splitIds[1] : $splitIds[0]);
            $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . '-' . (int) Context::getContext()->shop->theme_name . '.jpg')) ? '-' . Context::getContext()->shop->theme_name : '');
            if ($this->allow == 1) {
                $uriPath = $shop->getBaseURI() . $idImage . ($type ? '-' . $type : '') . $theme . '/' . $name . '.jpg';
            } else {
                $uriPath = _THEME_PROD_DIR_ . Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . $theme . '.jpg';
            }
        }

        return Context::getContext()->link->protocol_content . Tools::getMediaServer($uriPath) . $uriPath;
    }
}
