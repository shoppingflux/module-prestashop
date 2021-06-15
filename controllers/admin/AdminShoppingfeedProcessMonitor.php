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
require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Admin\AdminProcessMonitorController;

/**
 * @inheritdoc
 */
class AdminShoppingfeedProcessMonitorController extends AdminProcessMonitorController
{
    public function __construct()
    {
        parent::__construct();
        $select = [
            'id_shoppingfeed_processmonitor',
            'name',
            'IFNULL(pid,"") as pid',
            'duration',
            'last_update'
        ];
        $this->_select = implode(',', $select);
    }

    public function initContent()
    {
        $this->module->setBreakingChangesNotices();

        parent::initContent();

        // For compatibility with PS version < 1.6.0.11
        if (version_compare(_PS_VERSION_, '1.6.0.11', '<')) {
            $this->context->smarty->assign('js_def', Media::getJsDef());
            $this->context->smarty->assign(
                'content',
                $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_.'javascript.tpl') . $this->content
            );
        }
    }
}
