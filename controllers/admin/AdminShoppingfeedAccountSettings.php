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

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedAccountSettingsController extends ModuleAdminController
{
    public $bootstrap = true;

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        $current_shop_context = $this->context->shop->getContext();
        if ($current_shop_context === Shop::CONTEXT_ALL) {
            Context::getContext()->controller->addCSS(
                _PS_MODULE_DIR_ . 'shoppingfeed/views/css/config.css'
            );
            $this->content = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/error_multishop.tpl'
            );
            $this->context->smarty->assign('content', $this->content);
            return;
        }

        $this->addCSS(array(
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css'
        ));

        $this->content = $this->welcomeForm();

        $id_shop = $this->context->shop->id;
        $token = Configuration::get(shoppingfeed::AUTH_TOKEN, null, null, $id_shop);
        if (!$token) {
            $this->content .= $this->renderLoginForm();
        }

        $this->content .= $this->renderTokenForm();

        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    public function welcomeForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->module->l('15 min Marketplace Updates - Shopping', 'AdminShoppingfeedAccountSettings'),
            )
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . "views/img/";
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'welcome.tpl';

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the token form
     * @return string the rendered form's HTML
     */
    public function renderTokenForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('API Token', 'AdminShoppingfeedAccountSettings'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">' .
                        str_replace(
                            "%url%",
                            '<a href="https://app.shopping-feed.com/v3/en/login" class="alert-link" target="_blank">' .
                            $this->module->l('My Access page', 'AdminShoppingfeedConfiguration') .
                            '</a>',
                            $this->module->l('Your token can be found on the %url% of your merchant interface', 'AdminShoppingfeedAccountSettings')
                        ) .
                        '</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Token', 'AdminShoppingfeedAccountSettings'),
                    'name' => Shoppingfeed::AUTH_TOKEN,
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save', 'AdminShoppingfeedAccountSettings'),
                'name' => 'saveToken'
            )
        );

        $id_shop = $this->context->shop->id;
        $fields_value = array(
            Shoppingfeed::AUTH_TOKEN => Configuration::get(ShoppingFeed::AUTH_TOKEN, null, null, $id_shop),
        );

        $helper = new HelperForm($this);
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;

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
                'title' => $this->l('Login', 'AdminShoppingfeedAccountSettings'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">
                    '.$this->module->l('Otherwise you can also fill directly the token form located lower', 'AdminShoppingfeedAccountSettings').'</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Username', 'AdminShoppingfeedAccountSettings'),
                    'name' => 'username',
                    'required' => true,
                ),
                array(
                    'type' => 'password',
                    'label' => $this->module->l('Password', 'AdminShoppingfeedAccountSettings'),
                    'name' => 'password',
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Send', 'AdminShoppingfeedAccountSettings'),
                'name' => 'login'
            )
        );

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
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveToken')) {
            return $this->saveToken();
        } elseif (Tools::isSubmit('login')) {
            return $this->login();
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
            $this->errors[] = $this->module->l('You must specify a valid token.', 'AdminShoppingfeedAccountSettings');
            return false;
        }

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByToken(null, $token);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedAccountSettings');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->module->l('This token was not recognized by the Shopping Feed API.', 'AdminShoppingfeedAccountSettings');
            } else {
                $this->errors[] = $this->module->l($e->getMessage(), 'AdminShoppingfeedAccountSettings');
            }
            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }


        $id_shop = $this->context->shop->id;
        Configuration::updateValue(shoppingfeed::AUTH_TOKEN, $token, null, null, $id_shop);

        $this->confirmations[] = $this->module->l('Your token has been saved.', 'AdminShoppingfeedAccountSettings');
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
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedAccountSettings');
                return false;
            }
        } catch (SfGuzzle\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->module->l('These credentials were not recognized by the Shopping Feed API.', 'AdminShoppingfeedAccountSettings');
            } else {
                $this->errors[] = $e->getMessage();
            }
            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $id_shop = $this->context->shop->id;
        Configuration::updateValue(shoppingfeed::AUTH_TOKEN, $shoppingFeedApi->getToken(), null, null, $id_shop);

        $this->confirmations[] = $this->module->l('Login successful; your token has been saved.', 'AdminShoppingfeedAccountSettings');
        return true;
    }
}