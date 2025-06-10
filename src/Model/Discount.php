<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

namespace ShoppingfeedAddon\Model;

class Discount
{
    protected $amount = 0;

    protected $from = '';

    protected $to = '';

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = (float) $amount;

        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom($from)
    {
        $this->from = (string) $from;

        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function setTo($to)
    {
        $this->to = (string) $to;

        return $this;
    }
}
