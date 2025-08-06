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

use Shop;

class ShopSqlAssociator
{
    /**
     * do the same that Shop::addSqlAssociation() but allows to set a shop id
     */
    public function addAssociation($table, $alias, $inner_join = true, $on = null, $force_not_default = false, $idShop = null)
    {
        if (is_null($idShop)) {
            $idShop = \Shop::getContextShopID();
        }

        $table = pSQL($table);
        $alias = pSQL($alias);
        $table_alias = $table . '_shop';

        if (strpos($table, '.') !== false) {
            list($table_alias, $table) = explode('.', $table);
        }

        $asso_table = \Shop::getAssoTable($table);

        if (empty($asso_table) || $asso_table['type'] != 'shop') {
            return;
        }

        $sql = (($inner_join) ? ' INNER' : ' LEFT') . ' JOIN ' . _DB_PREFIX_ . $table . '_shop ' . $table_alias . '
        ON (' . $table_alias . '.id_' . $table . ' = ' . $alias . '.id_' . $table;

        if ((int) $idShop) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . (int) $idShop;
        } elseif (\Shop::checkIdShopDefault($table) && !$force_not_default) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . $alias . '.id_shop_default';
        } else {
            $sql .= ' AND ' . $table_alias . '.id_shop IN (' . implode(', ', \Shop::getContextListShopID()) . ')';
        }

        $sql .= (($on) ? ' AND ' . $on : '') . ')';

        return $sql;
    }
}
