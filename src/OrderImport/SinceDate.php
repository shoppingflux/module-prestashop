<?php


namespace ShoppingfeedAddon\OrderImport;


use Configuration;
use DateInterval;
use \DateTime;
use Shoppingfeed;

class SinceDate
{
    const DATE_FORMAT_PS = 'Y-m-d';

    const DATE_FORMAT_SF = 'Y-m-dTH:i:s';

    public function get($format = self::DATE_FORMAT_PS)
    {
        $date = DateTime::createFromFormat(
            self::DATE_FORMAT_PS,
            Configuration::get(Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE)
        );

        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        $date = new DateTime();
        $date->sub($this->getDefaultInterval());

        return $date->format($format);
    }

    public function set(DateTime $date)
    {
        Configuration::updateValue(
            Shoppingfeed::ORDER_IMPORT_PERMANENT_SINCE_DATE,
            $date->format(self::DATE_FORMAT_PS)
        );

        return $this;
    }

    protected function getDefaultInterval()
    {
        return new DateInterval('P1D');
    }
}
