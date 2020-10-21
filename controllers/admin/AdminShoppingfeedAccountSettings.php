<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

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
        $shops = Shop::getShops();
        $currencies = Currency::getCurrencies();
        $languagues = Language::getLanguages();

        $this->addCSS(array(
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css'
        ));

        $tokens = new ShoppingfeedToken();
        $listTokens = $tokens->findAll();
        if (empty($listTokens) === false) {
            $this->content = $this->welcomeForm();
        } else {
            $this->content = $this->registerForm();
        }
        $this->content .= $this->renderTokensList();
        $this->content .= $this->renderLoginForm($shops, $currencies, $languagues);
        $this->content .= $this->renderTokenForm($shops, $currencies, $languagues);

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

    public function registerForm()
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
        $helper->base_tpl = 'shoppingfeed_account_settings/register.tpl';

        $id_default_country = Configuration::get('PS_COUNTRY_DEFAULT');
        $this->default_country = new Country($id_default_country);
        $this->context->smarty->assign('phone', Tools::safeOutput(Configuration::get('PS_SHOP_PHONE')));
        $this->context->smarty->assign('email', Tools::safeOutput(Configuration::get('PS_SHOP_EMAIL')));
        $this->context->smarty->assign('lang', Tools::strtolower($this->default_country->iso_code));
        $this->context->smarty->assign('shoppingfeed_token', md5(rand()));

        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    /**
     * Renders the HTML for the token form
     * @return string the rendered form's HTML
     */
    public function renderTokenForm($shops, $currencies, $languagues)
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
                            $this->module->l('My Account', 'AdminShoppingfeedConfiguration') .
                            '</a>',
                            $this->module->l('Your token can be found on the page %url% of your merchant interface', 'AdminShoppingfeedAccountSettings')
                        ) .
                        '</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Token', 'AdminShoppingfeedAccountSettings'),
                    'name' => Shoppingfeed::AUTH_TOKEN,
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Shop'),
                    'name' => 'shop',
                    'required' => true,
                    'options' => array(
                        'query' => $shops,
                        'id' => 'id_shop',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Language'),
                    'name' => 'language',
                    'required' => true,
                    'options' => array(
                        'query' => $languagues,
                        'id' => 'id_lang',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Currency'),
                    'name' => 'currency',
                    'required' => true,
                    'options' => array(
                        'query' => $currencies,
                        'id' => 'id_currency',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save', 'AdminShoppingfeedAccountSettings'),
                'name' => 'saveToken',
            )
        );
        $id_shop = $this->context->shop->id;
        $fields_value = array(
            Shoppingfeed::AUTH_TOKEN => Configuration::get(ShoppingFeed::AUTH_TOKEN, null, null, $id_shop),
            'shop' => Context::getContext()->shop->id,
            'language' => Context::getContext()->language->id,
            'currency' => Context::getContext()->currency->id,
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
    public function renderLoginForm($shops, $currencies, $languagues)
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
                    'autocomplete' => 'off',
                ),
                array(
                    'type' => 'password',
                    'label' => $this->module->l('Password', 'AdminShoppingfeedAccountSettings'),
                    'name' => 'password',
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Shop'),
                    'name' => 'shop',
                    'required' => true,
                    'options' => array(
                        'query' => $shops,
                        'id' => 'id_shop',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Language'),
                    'name' => 'language',
                    'required' => true,
                    'options' => array(
                        'query' => $languagues,
                        'id' => 'id_lang',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Currency'),
                    'name' => 'currency',
                    'required' => true,
                    'options' => array(
                        'query' => $currencies,
                        'id' => 'id_currency',
                        'name' => 'name'
                    )
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
            'shop' => Context::getContext()->shop->id,
            'language' => Context::getContext()->language->id,
            'currency' => Context::getContext()->currency->id,
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
        parent::postProcess();

        $shop_id = Tools::getValue('shop');
        $lang_id = Tools::getValue('language');
        $currency_id = Tools::getValue('currency');

        if (Tools::isSubmit('saveToken')) {
            return $this->saveToken($shop_id, $lang_id, $currency_id);
        } elseif (Tools::isSubmit('login')) {
            return $this->login($shop_id, $lang_id, $currency_id);
        }
    }

    /**
     * Checks if a token is valid by testing it against the SF API, and saves it if it succeeds
     * @return bool
     */
    public function saveToken($shop_id, $lang_id, $currency_id)
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
            (new ShoppingfeedToken())->addToken($shop_id, $lang_id, $currency_id, $token);

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

        $this->confirmations[] = $this->module->l('Your token has been saved.', 'AdminShoppingfeedAccountSettings');
        return true;
    }

    /**
     * Attempts to retrieve a token from the SF API using credentials, and saves the token on success
     * @return bool
     */
    public function login($shop_id, $lang_id, $currency_id)
    {
        $username = Tools::getValue('username');
        $password = Tools::getValue('password');

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByCredentials($username, $password);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedAccountSettings');
                return false;
            }
            (new ShoppingfeedToken())->addToken($shop_id, $lang_id, $currency_id, $shoppingFeedApi->getToken());

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

    public function renderTokensList()
    {
        $tokens = new ShoppingfeedToken();
        $listTokens = $tokens->findAll();

        if (count($listTokens) === 0) {

            return '';
        }

        foreach ($listTokens as $key => $listToken) {
            $link = $this->context->link->getModuleLink(
                'shoppingfeed',
                'product',
                ['token' => $listToken['token']],
                true,
                Configuration::get('PS_LANG_DEFAULT'),
                Configuration::get('PS_SHOP_DEFAULT')
            );
            $listTokens[$key]['link'] = $link;
        }

        $fieldsList = array(
            'token' => array(
                'title' => $this->module->l('Tokens', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ),
            'shop_name' => array(
                'title' => $this->module->l('Shop', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ),
            'lang_name' => array(
                'title' => $this->module->l('Languague', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ),
            'currency_name' => array(
                'title' => $this->module->l('Currency', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ),
            'link' => array(
                'title' => $this->module->l('Product feed URL', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ),
            'active' => array(
                'title' => $this->module->l('Active', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
                'active' => 'active',
                'align' => 'text-center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'orderby' => false,
                'ajax' => true,

            ),
        );

        $helper = new HelperList();
        $this->setHelperDisplay($helper);
        $helper->title = $this->module->l('Tokens', 'AdminShoppingfeedAccountSettings');
        $helper->no_link = true;
        $helper->listTotal = count($listTokens);
        $helper->identifier = ShoppingfeedToken::$definition['primary'];

        return $helper->generateList($listTokens, $fieldsList);
    }

    public function ajaxProcessActiveConfiguration()
    {
        if (!$id_shoppingfeed_token = (int)Tools::getValue('id_shoppingfeed_token')) {
            die(Tools::jsonEncode(array('success' => false, 'error' => true, 'text' => $this->l('Failed to update the status'))));
        }
        $shoppingfeedToken = new ShoppingfeedToken((int)$id_shoppingfeed_token);
        if (Validate::isLoadedObject($shoppingfeedToken) === false) {
            die(Tools::jsonEncode(array('success' => false, 'error' => true, 'text' => $this->l('Failed to update the status'))));
        }
        $shoppingfeedToken->active = !$shoppingfeedToken->active;
        $shoppingfeedToken->save() ?
            die(Tools::jsonEncode(array('success' => true, 'text' => $this->l('The status has been updated successfully')))) :
            die(Tools::jsonEncode(array('success' => false, 'error' => true, 'text' => $this->l('Failed to update the status'))));
    }
}
