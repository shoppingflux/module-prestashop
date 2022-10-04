<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 *
 * @version   release/2.3.2
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedClasslib\Extensions\Diagnostic\Controllers\Admin\AdminDiagnosticController;

class AdminShoppingfeedDiagnosticController extends AdminDiagnosticController
{
    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/shoppingfeed_configuration/form.css',
            $this->module->getPathUri() . 'views/css/font-awesome.min.css',
        ]);
        $this->content = $this->welcomeForm();

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
        $helper->base_tpl = 'diagnostic.tpl';

        return $helper->generateForm([['form' => $fields_form]]);
    }
}
