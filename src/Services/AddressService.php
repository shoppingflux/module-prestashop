<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\Services;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AddressService
{
    /**
     * Get state id by ISO code and required country id.
     *
     * @param string $iso
     * @param int $id_country
     *
     * @return int|null returns state id or null when not found / invalid input
     */
    public function getStateIdByIso($iso, $id_country)
    {
        if (empty($iso) || empty($id_country) || !\Validate::isUnsignedId($id_country)) {
            return null;
        }

        try {
            $id = \State::getIdByIso($iso, (int) $id_country);
        } catch (\Throwable $e) {
            return null;
        }

        return $id ? (int) $id : null;
    }

    /**
     * Get state id by exact name and required country id.
     *
     * @param string $name
     * @param int $id_country
     *
     * @return int|null returns state id or null when not found / invalid input
     */
    public function getStateIdByName($name, $id_country)
    {
        if (empty($name) || empty($id_country) || !\Validate::isUnsignedId($id_country)) {
            return null;
        }

        $query = new \DbQuery();
        $query->select('s.`id_state`');
        $query->from('state', 's');
        $query->where('s.`name` = \'' . pSQL($name) . '\'');
        $query->where('s.`id_country` = ' . (int) $id_country);

        $result = \Db::getInstance()->getValue($query);

        return $result ? (int) $result : null;
    }
}
