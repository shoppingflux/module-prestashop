<?php

namespace ShoppingfeedAddon\OrderInvoiceSync;

class Marketplace
{
    protected $id = '';
    protected $name = '';
    protected $isEnabled = false;

    public function __construct($id, $name, $isEnabled)
    {
        $this->id = (string) $id;
        $this->name = (string) $name;
        $this->isEnabled = (bool) $isEnabled;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isEnabled()
    {
        return $this->isEnabled;
    }
}
