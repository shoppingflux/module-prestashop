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

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/shoppingfeed.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedApi.php');

TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\AdminProcessLoggerController');

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedConfigurationController extends ModuleAdminController
{
    public $bootstrap = true;

    public $nbr_prpoducts;
    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $this->addCSS($this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css');
        $this->addJS($this->module->getPathUri() . 'views/js/form_config.js');
        $this->addCSS($this->module->getPathUri() . 'views/fonts/font-awesome/css/font-awesome.min.css');

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $token = Configuration::get(shoppingfeed::AUTH_TOKEN, null, null, $id_shop);
        $this->nbr_prpoducts = count(Product::getSimpleProducts($this->context->language->id));
        $this->content = $this->welcomeForm();

        $this->content .= $this->renderTokenForm();

        if ($token) {
            $this->content .= $this->renderConfigurationForm();
        } else {
            $this->content .= $this->renderLoginForm();
        }

        $this->content .= $this->faqForm();

        return parent::initContent();
    }

    /**
     * Renders the HTML for the token form
     * @return string the rendered form's HTML
     */
    public function renderTokenForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('API Token'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">
                    '.str_replace("%url%",
                            '<a href="https://app.shopping-feed.com/v3/en/login" class="alert-link" target="_blank">'. $this->module->l('My Access page', 'AdminShoppingfeedConfiguration').'</a>'
                            , $this->module->l('Your token can be found on the %url% of your merchant interface', 'AdminShoppingfeedConfiguration', 'AdminShoppingfeedConfiguration')
                        ).'</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Token', 'AdminShoppingfeedConfiguration'),
                    'name' => Shoppingfeed::AUTH_TOKEN,
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'saveToken'
            )
        );

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $fields_value = array(
            Shoppingfeed::AUTH_TOKEN => Configuration::get(ShoppingFeed::AUTH_TOKEN, null, null, $id_shop),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function welcomeForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('15 min Marketplace Updates - Shopping', 'AdminShoppingfeedConfiguration'),
            )
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . "views/img/";
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'form_token.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the login form
     * @return string the rendered form's HTML
     */
    public function renderLoginForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Login'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">
                    '.$this->module->l('You may also enter your Shopping Feed credentials here to retrieve your token.', 'AdminShoppingfeedConfiguration').'</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Username', 'AdminShoppingfeedConfiguration'),
                    'name' => 'username',
                    'required' => true,
                ),
                array(
                    'type' => 'password',
                    'label' => $this->module->l('Password', 'AdminShoppingfeedConfiguration'),
                    'name' => 'password',
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Send', 'AdminShoppingfeedConfiguration'),
                'name' => 'login'
            )
        );

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $fields_value = array(
            'username' => '',
            'password' => '',
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'form_login.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the configuration form
     * @return string the rendered form's HTML
     */
    public function renderConfigurationForm()
    {
        switch (true) {
            case ($this->nbr_prpoducts <= 100):
                $message_realtime = $this->module->l('You have less than 100 products, the RealTime parameter on YES is recommended. You have little stock for each reference and for you the stock precision is fundamental. Moreover, no need to set up any cron job. Sending real-time inventory updates to the Feed API makes it easy for you to sync inventory in less than 15 minutes. However, this multiplies the calls to the Shopping API stream wchich can slow the loading time of pages that decrement or increment the stock, especially during order status updates.', 'AdminShoppingfeedConfiguration');
                break;
            case ($this->nbr_prpoducts < 1000 && $this->nbr_prpoducts > 100):
                $message_realtime = $this->module->l('You have between 100 and 1000 products, the Realtime parameter on NO is recommended. Updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances.', 'AdminShoppingfeedConfiguration');
                break;
            case ($this->nbr_prpoducts > 1000):
                $message_realtime = $this->module->l('You have more than 1000 products, Realtime parameter NO is required. You probably use an external tool (like an ERP) to manage your inventory which can lead to many updates at the same time. In this case, the updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances', 'AdminShoppingfeedConfiguration');
                break;
        }

        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'real_synch',
                    'html_content' => '<div id="real_synch" class="alert alert-info">
                    '.$message_realtime.'</div>',
                ),
                array(
                    'type' => 'switch',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'value' => 1,
                        ),
                        array(
                            'value' => 0,
                        )
                    ),
                    'label' => $this->module->l('Real-time synchronization', 'AdminShoppingfeedConfiguration'),
                    'hint' => $this->module->l('If checked, no CRON will be needed. Synchronization will occur as soon as the changes are made. This may impact user performance.', 'AdminShoppingfeedConfiguration'),
                    'name' => Shoppingfeed::REAL_TIME_SYNCHRONIZATION,
                ),
                array(
                    'type' => 'html',
                    'name' => 'for_real',
                    'html_content' => '<div id="for_real" class="alert alert-warning">
                    '.$this->module->l('The Max product update parameter is reserved for experts (100 by default). You can configure the number of products to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.', 'AdminShoppingfeedConfiguration').'</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Max. product update per request', 'AdminShoppingfeedConfiguration'),
                    'name' => Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS,
                    'required' => true,
                    'class' => 'for_real'
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save', 'AdminShoppingfeedConfiguration'),
                'name' => 'saveConfiguration'
            )
        );

        $fields_value = array(
            Shoppingfeed::REAL_TIME_SYNCHRONIZATION => Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION),
            Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS => Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();

        return $helper->generateForm(array(array('form' => $fields_form)));
    }


    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveToken')) {
            return $this->saveToken();
        } else if (Tools::isSubmit('login')) {
            return $this->login();
        } else if (Tools::isSubmit('saveConfiguration')) {
            return $this->saveConfiguration();
        }
    }

    /**
     * Checks if a token is valid by testing it against the SF API, and saves it if it succeeds
     * @return bool
     */
    public function saveToken()
    {
        $token = Tools::getValue(Shoppingfeed::AUTH_TOKEN);
        if (!$token || !preg_match("/^[\w\-\.\~\+\/]+=*$/", $token)) { // See https://tools.ietf.org/html/rfc6750
            $this->errors[] = $this->module->l('You must specify a valid token.', 'AdminShoppingfeedConfiguration');
            return false;
        }

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByToken(null, $token);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedConfiguration');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->module->l('This token was not recognized by the Shopping Feed API.', 'AdminShoppingfeedConfiguration');
            } else {
                $this->errors[] = $this->module->l($e->getMessage(), 'AdminShoppingfeedConfiguration');
            }
            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        Configuration::updateValue(shoppingfeed::AUTH_TOKEN, $token, null, null, $id_shop);

        $this->confirmations[] = $this->module->l('Your token has been saved.', 'AdminShoppingfeedConfiguration');
        return true;
    }

    /**
     * Attempts to retrieve a token from the SF API using credentials, and saves the token on success
     * @return bool
     */
    public function login()
    {
        $username = Tools::getValue('username');
        $password = Tools::getValue('password');

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByCredentials($username, $password);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedConfiguration');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->module->l('These credentials were not recognized by the Shopping Feed API.', 'AdminShoppingfeedConfiguration');
            } else {
                $this->errors[] = $this->l($e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        Configuration::updateValue(shoppingfeed::AUTH_TOKEN, $shoppingFeedApi->getToken(), null, null, $id_shop);

        $this->confirmations[] = $this->module->l('Login successful; your token has been saved.', 'AdminShoppingfeedConfiguration');
        return true;
    }

    /**
     * Saves the configuration for the module
     * @return bool
     */
    public function saveConfiguration()
    {
        $realtime_sync = Tools::getValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION);
        $stock_sync_max_products = (int)Tools::getValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);

        Configuration::updateValue(Shoppingfeed::REAL_TIME_SYNCHRONIZATION, $realtime_sync ? true : false);

        if (!is_numeric($stock_sync_max_products)) {
            $this->errors[] = $this->module->l('You must specify a valid \"Max. product update per request\" number.', 'AdminShoppingfeedConfiguration');
        } elseif ($stock_sync_max_products > 200 || $stock_sync_max_products <= 0) {
            $this->errors[] = $this->module->l('You must specify a \"Max. product update per request\" number between 1 and 200.', 'AdminShoppingfeedConfiguration');
        } else {
            Configuration::updateValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, $stock_sync_max_products);
        }

        return true;
    }

    public function faqForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Faq/Support', 'AdminShoppingfeedConfiguration'),
                'icon' => 'icon-question'
            )
        );


        $helper = new HelperForm($this);
        $helper->tpl_vars['REAL_TIME_SYNCHRONIZATION'] = Configuration::get(Shoppingfeed::REAL_TIME_SYNCHRONIZATION)?'true':'false';
        $helper->tpl_vars['nbr_prpoducts'] = $this->nbr_prpoducts;
        $helper->tpl_vars['shop_url'] = Tools::getShopDomain();
        $helper->tpl_vars['php_version'] = PHP_VERSION;
        $helper->tpl_vars['prestashop_version'] = _PS_VERSION_;
        $helper->tpl_vars['token'] = Configuration::get(Shoppingfeed::AUTH_TOKEN);
        $helper->tpl_vars['multiboutique'] = Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') ? 'true' : 'false';
        $helper->tpl_vars['STOCK_SYNC_MAX_PRODUCTS'] = Configuration::get(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS);
        $helper->tpl_vars['LAST_CRON_TIME_SYNCHRONIZATION'] = Configuration::get(Shoppingfeed::LAST_CRON_TIME_SYNCHRONIZATION);
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'faq.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }
}
