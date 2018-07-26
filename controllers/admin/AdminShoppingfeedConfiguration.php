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

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $this->addCSS($this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css');

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $token = Configuration::get(shoppingfeed::AUTH_TOKEN, null, null, $id_shop);

        $this->content = $this->renderTokenForm();

        if ($token) {
            $this->content .= $this->renderConfigurationForm();
        } else {
            $this->content .= $this->renderLoginForm();
        }

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
                    'type' => 'text',
                    'label' => $this->l('Token'),
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
        $helper->tpl_vars = $this->getTemplateFormVars();
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
                    'type' => 'text',
                    'label' => $this->l('Username'),
                    'name' => 'username',
                    'required' => true,
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l('Password'),
                    'name' => 'password',
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Send'),
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
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
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
                    'label' => $this->l('Real-time synchronization'),
                    'hint' => $this->l('If checked, no CRON will be needed. Synchronization will occur as soon as the changes are made. This may impact user performance.'),
                    'name' => Shoppingfeed::REAL_TIME_SYNCHRONIZATION,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Max. product update per request'),
                    'name' => Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS,
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
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
            $this->errors[] = $this->l('You must specify a valid token.');
            return false;
        }

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByToken(null, $token);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->l('An error has occurred.');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->l('This token was not recognized by the Shopping Feed API.');
            } else {
                $this->errors[] = $this->l($e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        Configuration::updateValue(shoppingfeed::AUTH_TOKEN, $token, null, null, $id_shop);

        $this->confirmations[] = $this->l('Your token has been saved.');
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
                $this->errors[] = $this->l('An error has occurred.');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->l('These credentials were not recognized by the Shopping Feed API.');
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

        $this->confirmations[] = $this->l('Login successful; your token has been saved.');
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
            $this->errors[] = $this->l('You must specify a valid \"Max. product update per request\" number.');
        } elseif ($stock_sync_max_products > 200 || $stock_sync_max_products <= 0) {
            $this->errors[] = $this->l('You must specify a \"Max. product update per request\" number between 1 and 200.');
        } else {
            Configuration::updateValue(Shoppingfeed::STOCK_SYNC_MAX_PRODUCTS, $stock_sync_max_products);
        }

        return true;
    }
}
