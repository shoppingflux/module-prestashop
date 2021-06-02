<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommence
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommence is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommence
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedFaqController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    public $nbr_products;
    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $this->addCSS(array(
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css'
        ));

        $this->nbr_products = count(Product::getSimpleProducts($this->context->language->id));

        $this->content = $this->welcomeForm();
        $this->content .= $this->faqForm();

        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    public function welcomeForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedAccountSettings'),
            )
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . "views/img/";
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'welcome.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function faqForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Faq / Support', 'AdminShoppingfeedFaq'),
                'icon' => 'icon-question'
            )
        );

        $syncProductUrl = $this->context->link->getModuleLink(
            'shoppingfeed',
            'syncProduct',
            array('secure_key' => $this->module->secure_key)
        );

        $syncOrderUrl = $this->context->link->getModuleLink(
            'shoppingfeed',
            'syncOrder',
            array('secure_key' => $this->module->secure_key)
        );

        $helper = new HelperForm($this);
        $helper->tpl_vars['REAL_TIME_SYNCHRONIZATION'] = Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)?'true':'false';
        $helper->tpl_vars['nbr_products'] = $this->nbr_products;
        $helper->tpl_vars['shop_url'] = Tools::getShopDomain();
        $helper->tpl_vars['php_version'] = PHP_VERSION;
        $helper->tpl_vars['prestashop_version'] = _PS_VERSION_;
        $helper->tpl_vars['module_version'] = $this->module->version;
        $helper->tpl_vars['token'] = Configuration::get(Shoppingfeed::AUTH_TOKEN);
        $helper->tpl_vars['multishop'] = Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') ? 'true' : 'false';
        $helper->tpl_vars['combination'] = Configuration::get('PS_COMBINATION_FEATURE_ACTIVE');
        $helper->tpl_vars['syncProductUrl'] = $syncProductUrl;
        $helper->tpl_vars['syncOrderUrl'] = $syncOrderUrl;
        $helper->tpl_vars['STOCK_SYNC_MAX_PRODUCTS'] = Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);
        $helper->tpl_vars['LAST_CRON_TIME_SYNCHRONIZATION'] = Configuration::get(Shoppingfeed::LAST_CRON_TIME_SYNCHRONIZATION);
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'faq.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }
}
