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


use Cache;
use Combination;
use Context;
use Db;
use DbQuery;
use Product;
use StockAvailable;
use Validate;

class ProductAttributeCombination
{
    /**
     * The same as Product::getAttributeCombinations() but take in account $idShop
     *
     * @param Product $product
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return []
     */
    public function get(Product $product, $idLang = null, $idShop = null)
    {
        if (false == Validate::isLoadedObject($product)) {
            return [];
        }

        if (!Combination::isFeatureActive()) {
            return [];
        }

        if ($idShop == false) {
            $idShop = Context::getContext()->shop->id;
        }

        if ($idLang == false) {
            $idLang = Context::getContext()->language->id;
        }

        $sql = (new DbQuery())
            ->select('pa.*')
            ->select('pas.*')
            ->select('ag.`id_attribute_group`, ag.`is_color_group`')
            ->select('agl.`name` AS group_name')
            ->select('al.`name` AS attribute_name')
            ->select('a.`id_attribute`')
            ->from('product_attribute', 'pa')
            ->innerJoin('product_attribute_shop', 'pas', 'pa.id_product_attribute = pas.id_product_attribute')
            ->leftJoin('product_attribute_combination', 'pac', 'pac.`id_product_attribute` = pa.`id_product_attribute`')
            ->leftJoin('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')
            ->leftJoin('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')
            ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute`')
            ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group`')
            ->where('pas.id_shop = ' . (int)$idShop)
            ->where('al.id_lang = ' . (int)$idLang)
            ->where('agl.id_lang = ' . (int)$idLang)
            ->where('pa.id_product = ' . (int)$product->id)
            ->orderBy('pa.`id_product_attribute`')
        ;

        try {
            $res = Db::getInstance()->executeS($sql);
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
                if (!Cache::isStored($cache_key)) {
                    Cache::store(
                        $cache_key,
                        StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute'])
                    );
                }
                $combinations[$idProductAttribute]['id_product_attribute'] = $idProductAttribute;
                $combinations[$idProductAttribute]['ean13'] = $row['ean13'];
                $combinations[$idProductAttribute]['upc'] = $row['upc'];
                $combinations[$idProductAttribute]['quantity'] = \Cache::retrieve($cache_key);
                $combinations[$idProductAttribute]['weight'] = $row['weight'];
                $combinations[$idProductAttribute]['reference'] = $row['reference'];
                $combinations[$idProductAttribute]['wholesale_price'] = $row['wholesale_price'];
            }

            $combinations[$idProductAttribute]['attributes'][$row['group_name']] = $row['attribute_name'];
        }

        return $combinations;
    }
}