<?php


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
            ->where('pas.id_shop = ' . $idShop)
            ->where('al.id_lang = ' . $idLang)
            ->where('agl.id_lang = ' . $idLang)
            ->where('pa.id_product = ' . $product->id)
            ->groupBy('pa.`id_product_attribute`')
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

        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';

            if (!Cache::isStored($cache_key)) {
                Cache::store(
                    $cache_key,
                    StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute'])
                );
            }

            $res[$key]['quantity'] = Cache::retrieve($cache_key);
        }

        return $res;
    }
}
