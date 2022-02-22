<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class shoppingfeedfix32951 extends Module
{
    public function __construct()
    {
        $this->name = 'shoppingfeedfix32951';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = '202 ecommerce';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => '1.7',
        ];

        parent::__construct();

        $this->displayName = $this->l('shoppingfeed check fix 32951');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('actionValidateOrder');
    }

    public function hookActionValidateOrder($params)
    {
        $text = 'TEST ' . json_encode($params['order']);
        PrestaShopLogger::addLog($text);
    }

    public function uninstall()
    {
        return $this->unregisterHook('actionValidateOrder')
            && parent::uninstall();
    }
}
