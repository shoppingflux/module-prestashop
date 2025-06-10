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

use Product;

class ProductAttributeCombination
{
    /**
     * The same as Product::getAttributeCombinations() but take in account $idShop
     *
     * @param \Product $product
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return []
     */
    public function get(\Product $product, $idLang = null, $idShop = null)
    {
        if (false == \Validate::isLoadedObject($product)) {
            return [];
        }

        if (!\Combination::isFeatureActive()) {
            return [];
        }

        if ($idShop == false) {
            $idShop = \Context::getContext()->shop->id;
        }

        if ($idLang == false) {
            $idLang = \Context::getContext()->language->id;
        }

        $sql = (new \DbQuery())
            ->select('pa.*')
            ->select('pas.*')
            ->select('ag.`id_attribute_group`, ag.`is_color_group`')
            ->select('agl.`name` AS group_name')
            ->select('al.`name` AS attribute_name')
            ->select('a.`id_attribute`')
            ->select('a.color')
            ->from('product_attribute', 'pa')
            ->innerJoin('product_attribute_shop', 'pas', 'pa.id_product_attribute = pas.id_product_attribute')
            ->leftJoin('product_attribute_combination', 'pac', 'pac.`id_product_attribute` = pa.`id_product_attribute`')
            ->leftJoin('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')
            ->leftJoin('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')
            ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute`')
            ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group`')
            ->where('pas.id_shop = ' . (int) $idShop)
            ->where('al.id_lang = ' . (int) $idLang)
            ->where('agl.id_lang = ' . (int) $idLang)
            ->where('pa.id_product = ' . (int) $product->id)
            ->orderBy('pa.`id_product_attribute`')
        ;

        try {
            $res = \Db::getInstance()->executeS($sql);
        } catch (\Throwable $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        $combinations = [];

        foreach ($res as $key => $row) {
            $idProductAttribute = $row['id_product_attribute'];
            if (empty($combinations[$row['id_product_attribute']])) {
                $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';
                if (!\Cache::isStored($cache_key)) {
                    \Cache::store(
                        $cache_key,
                        \StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute'])
                    );
                }
                $combinations[$idProductAttribute]['id_product_attribute'] = $idProductAttribute;
                $combinations[$idProductAttribute]['ean13'] = $row['ean13'];
                $combinations[$idProductAttribute]['upc'] = $row['upc'];
                $combinations[$idProductAttribute]['mpn'] = empty($row['mpn']) ? '' : $row['mpn'];
                $combinations[$idProductAttribute]['quantity'] = \Cache::retrieve($cache_key);
                $combinations[$idProductAttribute]['weight'] = $row['weight'];
                $combinations[$idProductAttribute]['reference'] = $row['reference'];
                $combinations[$idProductAttribute]['wholesale_price'] = $row['wholesale_price'];
                $combinations[$idProductAttribute]['ecotax'] = $row['ecotax'];
                $combinations[$idProductAttribute]['minimal_quantity'] = $row['minimal_quantity'];
            }

            $combinations[$idProductAttribute]['attributes'][$row['group_name']] = $row['attribute_name'];

            if ($row['is_color_group'] == 1) {
                $combinations[$idProductAttribute]['attributes'][$row['group_name'] . '-hexa'] = $row['color'];
                $path = 'co/' . $row['id_attribute'] . '.jpg';
                if (file_exists(_PS_IMG_DIR_ . $path) === true) {
                    $combinations[$idProductAttribute]['attributes'][$row['group_name'] . '-texture'] = _PS_BASE_URL_ . __PS_BASE_URI__ . 'img/' . $path;
                }
            }
        }

        return $combinations;
    }
}
