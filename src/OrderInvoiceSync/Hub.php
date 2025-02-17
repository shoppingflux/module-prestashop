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

namespace ShoppingfeedAddon\OrderInvoiceSync;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Hub
{
    protected $db;

    protected $marketplaces = [];

    protected static $instance;

    protected function __construct()
    {
        $this->db = \Db::getInstance();
        $this->initMarketplaces();
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getMarketplaces()
    {
        return $this->marketplaces;
    }

    /**
     * @return Marketplace|null
     */
    public function find($id)
    {
        return isset($this->marketplaces[$id]) ? $this->marketplaces[$id] : null;
    }

    public function findByName($name)
    {
        return $this->find($this->buildId($name));
    }

    public function enable($id)
    {
        $marketplace = $this->find($id);

        if (!$marketplace) {
            return false;
        }
        if ($marketplace->isEnabled()) {
            return true;
        }

        $this->marketplaces[$marketplace->getId()] = new Marketplace(
            $marketplace->getId(),
            $marketplace->getName(),
            true
        );

        return $this->update();
    }

    public function disable($id)
    {
        $marketplace = $this->find($id);

        if (!$marketplace) {
            return false;
        }
        if (false === $marketplace->isEnabled()) {
            return true;
        }

        $this->marketplaces[$marketplace->getId()] = new Marketplace(
            $marketplace->getId(),
            $marketplace->getName(),
            false
        );

        return $this->update();
    }

    protected function initMarketplaces()
    {
        $result = $this->db->executeS(
            (new \DbQuery())
                ->from('shoppingfeed_carrier')
                ->groupBy('name_marketplace')
                ->select('name_marketplace')
        );
        $configurations = json_decode(
            \Configuration::getGlobalValue(\Shoppingfeed::ORDER_INVOICE_SYNC_MARKETPLACES),
            true
        );

        if (empty($result)) {
            return;
        }

        foreach ($result as $row) {
            $id = $this->buildId($row['name_marketplace']);
            $this->marketplaces[$id] = new Marketplace(
                $id,
                $row['name_marketplace'],
                empty($configurations[$id]['isEnabled']) ? false : $configurations[$id]['isEnabled']
            );
        }
    }

    protected function buildId($name_marketplace)
    {
        return md5(
            trim(
                (string) $name_marketplace
            )
        );
    }

    protected function update()
    {
        $settings = [];

        foreach ($this->marketplaces as $marketplace) {
            $settings[$marketplace->getId()] = [
                'name' => $marketplace->getName(),
                'isEnabled' => $marketplace->isEnabled(),
            ];
        }

        return \Configuration::updateGlobalValue(
            \Shoppingfeed::ORDER_INVOICE_SYNC_MARKETPLACES,
            json_encode($settings)
        );
    }
}
