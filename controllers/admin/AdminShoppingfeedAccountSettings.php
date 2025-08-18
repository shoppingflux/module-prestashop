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

use ShoppingfeedAddon\PrestaShopCloudSync\CloudSyncView;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedAccountSettingsController extends ShoppingfeedAdminController
{
    /** @var Shoppingfeed */
    public $module;
    public $bootstrap = true;
    public $override_folder;

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        $shops = Shop::getShops();
        $currencies = Currency::getCurrencies();
        $languagues = Language::getLanguages();

        $this->addCSS([
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css',
        ]);
        $this->addJS($this->module->getPathUri() . 'views/js/account-settings-page.js');

        $tokens = new ShoppingfeedToken();
        $listTokens = $tokens->findAll();
        $this->content = $this->compatibiltyPrestashop();

        if (empty($listTokens) === false) {
            $this->content .= $this->welcomeForm();
        } else {
            $this->content .= $this->registerForm();
        }

        $this->content .= $this->renderCloudSyncSection();
        $this->content .= $this->renderTokensList();
        $this->content .= $this->renderLoginForm($shops, $currencies, $languagues);
        $this->content .= $this->renderTokenForm($shops, $currencies, $languagues);

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

    public function compatibiltyPrestashop()
    {
        if (version_compare(phpversion(), '7.1', '<') || version_compare(_PS_VERSION_, '1.7.6', '<')) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/shoppingfeed_compatibility/account_settings.tpl');
        }

        return '';
    }

    public function registerForm()
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Shoppingfeed PrestaShop Plugin (Feed & Order)', 'AdminShoppingfeedAccountSettings'),
            ],
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->tpl_vars['img_path'] = $this->module->getPathUri() . 'views/img/';
        $helper->base_folder = $this->getTemplatePath();
        $helper->base_tpl = 'shoppingfeed_account_settings/register.tpl';

        $id_default_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $default_country = new Country($id_default_country);
        $this->context->smarty->assign('phone', Tools::safeOutput(Configuration::get('PS_SHOP_PHONE')));
        $this->context->smarty->assign('email', Tools::safeOutput(Configuration::get('PS_SHOP_EMAIL')));
        $this->context->smarty->assign('lang', Tools::strtolower($default_country->iso_code));
        $this->context->smarty->assign('shoppingfeed_token', md5((string) rand()));

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Renders the HTML for the token form
     *
     * @return string the rendered form's HTML
     */
    public function renderTokenForm($shops, $currencies, $languagues)
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('API Token', 'AdminShoppingfeedAccountSettings'),
                'icon' => 'icon-user',
            ],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">' .
                        str_replace(
                            '%url%',
                            '<a href="https://app.shopping-feed.com/v3/en/login" class="alert-link" target="_blank">' .
                            $this->module->l('My Account', 'AdminShoppingfeedConfiguration') .
                            '</a>',
                            $this->module->l('Your token can be found on the page %url% of your merchant interface', 'AdminShoppingfeedAccountSettings')
                        ) .
                        '</div>',
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Token', 'AdminShoppingfeedAccountSettings'),
                    'name' => Shoppingfeed::AUTH_TOKEN,
                    'required' => true,
                ],
                [
                    'type' => 'shoppingfeed-button-get-store-id',
                    'name' => '',
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Shop'),
                    'name' => 'shop',
                    'required' => true,
                    'options' => [
                        'query' => $shops,
                        'id' => 'id_shop',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Language'),
                    'name' => 'language',
                    'required' => true,
                    'options' => [
                        'query' => $languagues,
                        'id' => 'id_lang',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Currency'),
                    'name' => 'currency',
                    'required' => true,
                    'options' => [
                        'query' => $currencies,
                        'id' => 'id_currency',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('StoreID ShoppingFeed'),
                    'name' => 'store_id',
                    'required' => true,
                    'options' => [
                        'query' => [],
                        'id' => 'store_id',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminShoppingfeedAccountSettings'),
                'name' => 'saveToken',
            ],
        ];
        $id_shop = $this->context->shop->id;
        $fields_value = [
            Shoppingfeed::AUTH_TOKEN => Configuration::get(Shoppingfeed::AUTH_TOKEN, null, null, $id_shop),
            'shop' => Context::getContext()->shop->id,
            'language' => Context::getContext()->language->id,
            'currency' => Context::getContext()->currency->id,
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'token-form.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Renders the HTML for the login form
     *
     * @return string the rendered form's HTML
     */
    public function renderLoginForm($shops, $currencies, $languagues)
    {
        $fields_form = [
            'legend' => [
                'title' => $this->module->l('Login', 'AdminShoppingfeedAccountSettings'),
                'icon' => 'icon-user',
            ],
            'input' => [
                [
                    'type' => 'html',
                    'name' => 'employee_avatar',
                    'html_content' => '<div id="employee-avatar-thumbnail" class="alert alert-info">
                    ' . $this->module->l('Otherwise you can also fill directly the token form located lower', 'AdminShoppingfeedAccountSettings') . '</div>',
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Username', 'AdminShoppingfeedAccountSettings'),
                    'name' => 'username',
                    'required' => true,
                    'autocomplete' => 'off',
                ],
                [
                    'type' => 'password',
                    'label' => $this->module->l('Password', 'AdminShoppingfeedAccountSettings'),
                    'name' => 'password',
                    'required' => true,
                ],
                [
                    'type' => 'shoppingfeed-button-get-store-id',
                    'name' => '',
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Shop'),
                    'name' => 'shop',
                    'required' => true,
                    'options' => [
                        'query' => $shops,
                        'id' => 'id_shop',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Language'),
                    'name' => 'language',
                    'required' => true,
                    'options' => [
                        'query' => $languagues,
                        'id' => 'id_lang',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Currency'),
                    'name' => 'currency',
                    'required' => true,
                    'options' => [
                        'query' => $currencies,
                        'id' => 'id_currency',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('StoreID ShoppingFeed'),
                    'name' => 'store_id',
                    'required' => true,
                    'options' => [
                        'query' => [],
                        'id' => 'store_id',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Send', 'AdminShoppingfeedAccountSettings'),
                'name' => 'login',
            ],
        ];

        $fields_value = [
            'username' => '',
            'password' => '',
            'shop' => Context::getContext()->shop->id,
            'language' => Context::getContext()->language->id,
            'currency' => Context::getContext()->currency->id,
        ];

        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->fields_value = $fields_value;
        $helper->tpl_vars = $this->getTemplateFormVars();
        $helper->base_folder = $this->getTemplatePath() . $this->override_folder;
        $helper->base_tpl = 'form_login.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        parent::postProcess();

        $result = true;
        $shop_id = Tools::getValue('shop');
        $lang_id = Tools::getValue('language');
        $currency_id = Tools::getValue('currency');

        if (Tools::isSubmit('saveToken')) {
            $result &= $this->saveToken($shop_id, $lang_id, $currency_id);
        } elseif (Tools::isSubmit('login')) {
            $result &= $this->login($shop_id, $lang_id, $currency_id);
        }

        $this->module->updateShoppingfeedStoreId();

        return $result;
    }

    /**
     * Checks if a token is valid by testing it against the SF API, and saves it if it succeeds
     *
     * @return bool
     */
    public function saveToken($shop_id, $lang_id, $currency_id)
    {
        $token = Tools::getValue(Shoppingfeed::AUTH_TOKEN);
        $store_id = (int) Tools::getValue('store_id');

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
            if (false === $shoppingFeedApi->isExistedStore($store_id)) {
                $this->errors[] = $this->module->l('You must specify a valid store ID.', 'AdminShoppingfeedAccountSettings');

                return false;
            }
            (new ShoppingfeedToken())->addToken(
                $shop_id,
                $lang_id,
                $currency_id,
                $token,
                $store_id,
                $shoppingFeedApi->getMainStore()->getName()
            );
        } catch (ShoppingfeedPrefix\GuzzleHttp\Exception\ClientException $e) {
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
     *
     * @return bool
     */
    public function login($shop_id, $lang_id, $currency_id)
    {
        $username = Tools::getValue('username');
        $password = Tools::getValue('password');
        $store_id = (int) Tools::getValue('store_id');

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstanceByCredentials($username, $password);

            if (!$shoppingFeedApi) {
                $this->errors[] = $this->module->l('An error has occurred.', 'AdminShoppingfeedAccountSettings');

                return false;
            }
            if (false === $shoppingFeedApi->isExistedStore($store_id)) {
                $this->errors[] = $this->module->l('You must specify a valid store ID.', 'AdminShoppingfeedAccountSettings');

                return false;
            }
            (new ShoppingfeedToken())->addToken(
                $shop_id,
                $lang_id,
                $currency_id,
                $shoppingFeedApi->getToken(),
                $store_id,
                $shoppingFeedApi->getMainStore()->getName()
            );
        } catch (ShoppingfeedPrefix\GuzzleHttp\Exception\ClientException $e) {
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

        $id_shop = (int) $this->context->shop->id;
        Configuration::updateValue(Shoppingfeed::AUTH_TOKEN, $shoppingFeedApi->getToken(), false, null, $id_shop);

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
                ['feed_key' => $listToken['feed_key']],
                true,
                (int) Configuration::get('PS_LANG_DEFAULT'),
                $listToken['id_shop']
            );
            $listTokens[$key]['link'] = $link;
        }

        $fieldsList = [
            'shop_name' => [
                'title' => $this->module->l('Shop', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'lang_name' => [
                'title' => $this->module->l('Languague', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'currency_name' => [
                'title' => $this->module->l('Currency', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'merchant' => [
                'title' => $this->module->l('Merchant', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'shoppingfeed_store_id' => [
                'title' => $this->module->l('Store ID', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'link' => [
                'title' => $this->module->l('Product feed URL', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
            ],
            'active' => [
                'title' => $this->module->l('Active', 'AdminShoppingfeedAccountSettings'),
                'search' => false,
                'active' => 'active',
                'align' => 'text-center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'orderby' => false,
                'ajax' => true,
            ],
        ];

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
        if (!$id_shoppingfeed_token = (int) Tools::getValue('id_shoppingfeed_token')) {
            exit(json_encode(['success' => false, 'error' => true, 'text' => $this->module->l('Failed to update the status')]));
        }
        $shoppingfeedToken = new ShoppingfeedToken((int) $id_shoppingfeed_token);
        if (Validate::isLoadedObject($shoppingfeedToken) === false) {
            exit(json_encode(['success' => false, 'error' => true, 'text' => $this->module->l('Failed to update the status')]));
        }
        $shoppingfeedToken->active = !$shoppingfeedToken->active;
        $shoppingfeedToken->save() ?
            exit(json_encode(['success' => true, 'text' => $this->module->l('The status has been updated successfully')])) :
            exit(json_encode(['success' => false, 'error' => true, 'text' => $this->module->l('Failed to update the status')]));
    }

    public function ajaxProcessGetStoreId()
    {
        $api = null;
        $storeID = [];

        try {
            if (Tools::getValue(Shoppingfeed::AUTH_TOKEN)) {
                $api = ShoppingfeedApi::getInstanceByToken(null, Tools::getValue(Shoppingfeed::AUTH_TOKEN));
            } elseif (Tools::getValue('username') && Tools::getValue('password')) {
                $api = ShoppingfeedApi::getInstanceByCredentials(
                    Tools::getValue('username'),
                    Tools::getValue('password')
                );
            }
        } catch (ShoppingfeedPrefix\GuzzleHttp\Exception\ClientException $e) {
        }

        if (!$api) {
            exit(json_encode([
                'success' => false,
                'message' => $this->module->l('Wrong credentials'),
            ]));
        }

        foreach ($api->getStores() as $store) {
            $storeID[] = $store->getId();
        }

        exit(json_encode([
            'success' => true,
            'storeID' => $storeID,
        ]));
    }

    protected function renderCloudSyncSection()
    {
        return (new CloudSyncView())->render();
    }
}
