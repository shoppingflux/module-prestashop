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
        $this->content = $this->renderTokenForm();

        parent::initContent();
    }

    /**
     * Renders the HTML for the token form
     * @return string the rendered form's HTML
     */
    public function renderTokenForm()
    {
        $fields_form = array(
            'legend' => array(
                'title' => $this->l('API Token', 'AdminShoppingfeedConfiguration'),
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
                            $this->module->l('Your token can be found on the %url% of your merchant interface', 'AdminShoppingfeedConfiguration')
                        ) .
                        '</div>',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Token', 'AdminShoppingfeedConfiguration'),
                    'name' => Shoppingfeed::AUTH_TOKEN,
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save', 'AdminShoppingfeedConfiguration'),
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
     * @inheritdoc
     */
    public function postProcess()
    {
        if (Tools::isSubmit('saveToken')) {
            return $this->saveToken();
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
}