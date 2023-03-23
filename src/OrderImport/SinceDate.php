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

namespace ShoppingfeedAddon\OrderImport;

use Configuration;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Shoppingfeed;

class SinceDate implements SinceDateInterface
{
    const DATE_FORMAT_PS = 'Y-m-d';

    const DATE_FORMAT_SF = 'Y-m-dTH:i:s';

    public function get($format = self::DATE_FORMAT_PS, $id_shop = null)
    {
        $date = DateTime::createFromFormat(
            self::DATE_FORMAT_PS,
            Configuration::get(
                Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE,
                null,
                null,
                $id_shop
            )
        );

        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        $date = new DateTime();
        $date->sub($this->getDefaultInterval());

        return $date->format($format);
    }

    public function getForShipped($format = self::DATE_FORMAT_PS, $id_shop = null)
    {
        $date = DateTime::createFromFormat(
            self::DATE_FORMAT_PS,
            Configuration::get(
                Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE,
                null,
                null,
                $id_shop
            )
        );

        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        $date = new DateTime();
        $date->sub($this->getDefaultInterval());

        return $date->format($format);
    }

    public function getForShippedByMarketplace($format = self::DATE_FORMAT_PS, $id_shop = null)
    {
        $date = DateTime::createFromFormat(
            self::DATE_FORMAT_PS,
            Configuration::get(
                Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE,
                null,
                null,
                $id_shop
            )
        );

        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        $date = new DateTime();
        $date->sub($this->getDefaultInterval());

        return $date->format($format);
    }

    public function set(DateTimeInterface $date, $id_shop = null)
    {
        Configuration::updateValue(
            Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE,
            $date->format(self::DATE_FORMAT_PS),
            false,
            null,
            $id_shop
        );

        return $this;
    }

    public function setForShipped(DateTimeInterface $date, $id_shop = null)
    {
        Configuration::updateValue(
            Shoppingfeed::ORDER_SHIPPED_IMPORT_PERMANENT_SINCE_DATE,
            $date->format(self::DATE_FORMAT_PS),
            false,
            null,
            $id_shop
        );

        return $this;
    }

    public function setForShippedByMarketplace(DateTimeInterface $date, $id_shop = null)
    {
        Configuration::updateValue(
            Shoppingfeed::ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE,
            $date->format(self::DATE_FORMAT_PS),
            false,
            null,
            $id_shop
        );

        return $this;
    }

    protected function getDefaultInterval()
    {
        return new DateInterval('P7D');
    }
}
