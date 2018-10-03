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

require_once _PS_MODULE_DIR_ . 'shoppingfeed/shoppingfeed.php';

TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\AdminProcessLoggerController');

/**
 * @inheritdoc
 */
class AdminShoppingfeedProcessLoggerController extends ShoppingfeedAdminProcessLoggerController
{
    public $bootstrap = true;

    public function initContent()
    {
        if ($this->context->cookie->shopContext == null || $this->context->cookie->shopContext[0] == 'g') {
            Context::getContext()->controller->addCSS(_PS_MODULE_DIR_ .'shoppingfeed/views/css/config.css');
            $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/error_multishop.tpl');
            $this->context->smarty->assign('content', $this->content);
        } else {
            parent::initContent();
        }
    }
}