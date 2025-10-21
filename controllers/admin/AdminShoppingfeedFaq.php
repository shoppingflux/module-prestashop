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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedFaqController extends ShoppingfeedAdminController
{
    /** @var Shoppingfeed */
    public $module;

    public $bootstrap = true;

    public $nbr_products;

    public $override_folder;

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css',
        ]);

        $this->nbr_products = count(Product::getSimpleProducts($this->context->language->id));

        $this->content = $this->welcomeForm();
        $this->content .= $this->faqForm();

        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    public function welcomeForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Shoppingfeed Prestashop Plugin (Feed&Order)', 'AdminShoppingfeedAccountSettings'),
            ],
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . 'views/img/';
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'welcome.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }

    public function faqForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Faq / Support', 'AdminShoppingfeedFaq'),
                'icon' => 'icon-question',
            ],
        ];

        $syncProductUrl = $this->context->link->getModuleLink(
            'shoppingfeed',
            'syncProduct',
            ['secure_key' => $this->module->secure_key]
        );

        $syncOrderUrl = $this->context->link->getModuleLink(
            'shoppingfeed',
            'syncOrder',
            ['secure_key' => $this->module->secure_key]
        );

        $helper = new HelperForm();
        $helper->tpl_vars['REAL_TIME_SYNCHRONIZATION'] = Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION) ? 'true' : 'false';
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

        return $helper->generateForm([['form' => $fields_form]]);
    }
}
