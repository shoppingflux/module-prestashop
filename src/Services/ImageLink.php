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

namespace ShoppingfeedAddon\Services;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Image;
use Module;
use Shop;

class ImageLink
{
    /** @var bool */
    protected $allow;

    public function __construct()
    {
        $this->allow = (int) \Configuration::get('PS_REWRITING_SETTINGS') !== 0;
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
            $shop = new \Shop($idShop);
        } else {
            $shop = \Context::getContext()->shop;
        }

        static $watermarkLogged = null;
        static $watermarkHash = null;
        static $psLegacyImages = null;
        if ($watermarkLogged === null) {
            $watermarkLogged = \Configuration::get('WATERMARK_LOGGED');
            $watermarkHash = \Configuration::get('WATERMARK_HASH');
            $psLegacyImages = \Configuration::get('PS_LEGACY_IMAGES');
        }

        // Check if module is installed, enabled, customer is logged in and watermark logged option is on
        if (!empty($type) && $watermarkLogged
            && (\Module::isInstalled('watermark') && \Module::isEnabled('watermark'))
            && isset(\Context::getContext()->customer->id)
        ) {
            $type .= '-' . $watermarkHash;
        }

        // legacy mode or default image
        $theme = ((\Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . '-' . \Context::getContext()->shop->theme_name . '.jpg')) ? '-' . \Context::getContext()->shop->theme_name : '');
        if (($psLegacyImages
                && file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . $theme . '.jpg'))
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
            $theme = ((\Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . \Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . '-' . (int) \Context::getContext()->shop->theme_name . '.jpg')) ? '-' . \Context::getContext()->shop->theme_name : '');
            if ($this->allow == 1) {
                $uriPath = $shop->getBaseURI() . $idImage . ($type ? '-' . $type : '') . $theme . '/' . $name . '.jpg';
            } else {
                $uriPath = _THEME_PROD_DIR_ . \Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . $theme . '.jpg';
            }
        }

        return \Context::getContext()->link->protocol_content . \Tools::getMediaServer($uriPath) . $uriPath;
    }
}
