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

class AdminShoppingfeedConfigurationController extends ModuleAdminController
{
    public $bootstrap = true;

    public $display = 'form';

    public function initContent()
    {
        $this->addCSS($this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css');

        // renderForm() uses Guzzle for some reason, so we can't use fields_form because of the lib conflict...
        $this->fields_options['TokenAuth'] = array(
            'image' => '../img/admin/cog.gif',
            'icon' => 'icon-user',
            'info' => $this->createInfoTemplate(),
            'title' => $this->l('API Token'),
            'fields' => array(
                shoppingfeed::AUTH_TOKEN => array(
                    'title' => $this->l('Token'),
                    'type' => 'text',
                    'required' => true,
                    'auto_value' => false,
                    'value' => Configuration::get(shoppingfeed::AUTH_TOKEN),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'saveToken'
            ),
        );

        return parent::initContent();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('saveToken')) {
            return $this->saveToken();
        }
    }

    public function saveToken()
    {
        $token = Tools::getValue(shoppingfeed::AUTH_TOKEN);
        if (!$token || !preg_match("/^[\w\-\.\~\+\/]+=*$/", $token)) { // See https://tools.ietf.org/html/rfc6750
            $this->errors[] = $this->l("You must specify a valid token.");
            return false;
        }

        try {
            $shoppingFeedApi = ShoppingfeedApi::getInstance($token);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 401) {
                $this->errors[] = $this->l("This token was not recognized by the Shopping Feed API.");
            }
            $shoppingFeedApi = false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $shoppingFeedApi = false;
        }

        if (!$shoppingFeedApi) {
            return false;
        }

        $shops = Shop::getContextListShopID();
        foreach ($shops as $id_shop) {
            Configuration::updateValue(shoppingfeed::AUTH_TOKEN . "_" . $id_shop, $token);
        }

        return true;
    }

    public function createInfoTemplate()
    {
        $template = $this->createTemplate("info.tpl");
        $template->assign('img_path', $this->module->getPathUri() . "views/img/");
        return $template->fetch();
    }
}