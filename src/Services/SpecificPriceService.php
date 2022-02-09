<?php


namespace ShoppingfeedAddon\Services;

use DbQuery;
use Db;
use SpecificPrice;
use SpecificPriceRule;

class SpecificPriceService
{
    /**
     * @param int $idRule
     * @return int[]
     */
    public function getProductIdsByRule($idRule)
    {
        $query = (new DbQuery())
            ->select('id_product')
            ->from(SpecificPrice::$definition['table'], 'sp')
            ->leftJoin(SpecificPriceRule::$definition['table'], 'spr', 'sp.id_specific_price_rule = spr.id_specific_price_rule')
            ->where('spr.id_specific_price_rule = ' . (int)$idRule);

        $res = Db::getInstance()->executeS($query);

        if (empty($res)) {
            return [];
        }

        return array_map(
            function($row) {
                return (int)$row['id_product'];
            },
            $res
        );
    }
}
