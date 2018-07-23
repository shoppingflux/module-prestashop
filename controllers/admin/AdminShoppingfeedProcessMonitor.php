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

TotLoader::import('shoppingfeed\classlib\extensions\ProcessMonitor\AdminProcessMonitorController');

class AdminShoppingfeedProcessMonitorController extends ShoppingfeedAdminProcessMonitorController
{

    public $bootstrap = true;

    /**
     * @inheritdoc
     * @throws SmartyException
     */
    public function initContent()
    {
        parent::initContent();

        $this->content .= $this->renderCronUrls();

        $this->context->smarty->assign(array(
            'content' => $this->content,
        ));
    }

    /**
     * Renders a list with all the cron URLs.
     * TODO : load the list dynamically by reading the front controllers files
     * @return string the list's HTML
     * @throws SmartyException
     */
    public function renderCronUrls()
    {
        $list = array(
            ShoppingfeedProduct::ACTION_SYNC_STOCK => array(
                'action' => $this->l('Products stock synchronization'),
                'url' => $this->context->link->getModuleLink($this->module->name, 'syncStock', array('secure_key' => $this->module->secure_key)),
            )
        );

        $tpl = $this->createTemplate('cronUrls.tpl');
        $tpl->assign('cron_urls', $list);
        return $tpl->fetch();
    }
}
