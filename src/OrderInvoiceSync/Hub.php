<?php

namespace ShoppingfeedAddon\OrderInvoiceSync;

use Configuration;
use Db;
use DbQuery;
use Shoppingfeed;

class Hub
{
    protected $db;

    protected $marketplaces = [];

    protected static $instance = null;

    protected function __construct()
    {
        $this->db = Db::getInstance();
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
            (new DbQuery())
                ->from('shoppingfeed_carrier')
                ->groupBy('name_marketplace')
                ->select('name_marketplace')
        );
        $configurations = json_decode(
            Configuration::getGlobalValue(Shoppingfeed::ORDER_INVOICE_SYNC_MARKETPLACES),
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
                (empty($configurations[$id]['isEnabled']) ? false : $configurations[$id]['isEnabled'])
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

        return Configuration::updateGlobalValue(
            Shoppingfeed::ORDER_INVOICE_SYNC_MARKETPLACES,
            json_encode($settings)
        );
    }
}