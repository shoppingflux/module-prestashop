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
 * @version   feature/34626_diagnostic
 */

namespace ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Admin;

use ShoppingfeedClasslib\Extensions\ProcessMonitor\Classes\ProcessMonitorObjectModel;
use \HelperList;
use \Validate;
use \ObjectModel;
use \Db;
use \Media;
use \Tools;

class AdminProcessMonitorController extends \ModuleAdminController
{
    /** @var bool $bootstrap Active bootstrap for Prestashop 1.6 */
    public $bootstrap = true;

    /** @var \Module Instance of your module automatically set by ModuleAdminController */
    public $module;

    /** @var string Associated object class name */
    public $className = 'ShoppingfeedClasslib\Extensions\ProcessMonitor\Classes\ProcessMonitorObjectModel';

    /** @var string Associated table name */
    public $table = 'shoppingfeed_processmonitor';

    /** @var string|false Object identifier inside the associated table */
    public $identifier = 'id_shoppingfeed_processmonitor';

    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_defaultOrderBy = 'id_shoppingfeed_processmonitor';

    /** @var string Default ORDER WAY clause when $_orderWay is not defined */
    protected $_defaultOrderWay = 'DESC';

    /** @var bool List content lines are clickable if true */
    protected $list_no_link = true;

    /**
     * @see AdminController::__construct()
     */
    public function __construct()
    {
        parent::__construct();

        $this->addRowAction('delete');

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->module->l('Delete selected', 'AdminProcessMonitorController'),
                'confirm' => $this->module->l(
                    'Would you like to delete the selected items?',
                    'AdminProcessMonitorController'
                ),
            )
        );

        $this->fields_list = array(
            'id_shoppingfeed_processmonitor' => array(
                'title'  => $this->module->l('ID', 'AdminProcessMonitorController'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
                'search' => true,
            ),
            'name'                       => array(
                'title' => $this->module->l('Title', 'AdminProcessMonitorController'),
                'name'  => 'name',
            ),
            'pid'                        => array(
                'title' => $this->module->l('Status', 'AdminProcessMonitorController'),
                'name'  => 'pid',
                'callback' => 'getStatus'
            ),
            'duration'                   => array(
                'title' => $this->module->l('Duration', 'AdminProcessMonitorController'),
                'name'  => 'duration',
            ),
            'last_update'                => array(
                'title' => $this->module->l('Last update', 'AdminProcessMonitorController'),
                'name'  => 'last_update',
            ),
        );
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJS(_PS_MODULE_DIR_ . 'shoppingfeed/views/js/process_monitor/process_monitor.js');
        $this->addCSS(_PS_MODULE_DIR_ . 'shoppingfeed/views/css/process_monitor/process_monitor.css');
        Media::addJsDef(array('shoppingfeedProcessMonitorController' => $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor')));

    }

    /**
     * @param $echo string Value of field
     * @param $tr array All data of the row
     * @return string
     */
    public function getStatus($echo, $tr)
    {
        unset($tr);
        return empty($echo) ? '<span class="badge badge-info">'.
            $this->module->l('Not running', 'AdminProcessMonitorController').
            '</span>' : '<span class="badge badge-warning">'.
            $this->module->l('Is running', 'AdminProcessMonitorController').
            '</span>';
    }

    /**
     * @see AdminController::initPageHeaderToolbar()
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        // Remove the help icon of the toolbar which no useful for us
        $this->context->smarty->clearAssign('help_link');
    }

    /**
     * @see AdminController::initToolbar()
     */
    public function initToolbar()
    {
        parent::initToolbar();
        // Remove the add new item button
        unset($this->toolbar_btn['new']);
    }

    /**
     * @inheritdoc
     * @throws \SmartyException
     */
    public function initContent()
    {
        parent::initContent();

        $this->content .= $this->renderCronTasks();

        $this->content .= $this->getProcessModal();

        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * Renders a list with all cron tasks
     *
     * @return null|string
     * @throws \SmartyException
     * @throws \Exception
     */
    public function renderCronTasks()
    {
        if (empty($this->module->cronTasks)) {
            throw new \Exception(
                $this->module->l('Unable to find cronTasks declaration in module.', 'AdminProcessMonitorController')
            );
        }

        $fieldsList = array(
            'name'     => array(
                'title' => $this->module->l('Technical name', 'AdminProcessMonitorController'),
                'name'  => 'name',
            ),
            'title'     => array(
                'title' => $this->module->l('Title', 'AdminProcessMonitorController'),
                'name'  => 'title',
            ),
            'frequency' => array(
                'title' => $this->module->l('Frequency', 'AdminProcessMonitorController'),
                'name'  => 'frequency',
            ),
            'url'       => array(
                'title' => $this->l('URL'),
                'name'  => 'url',
                'class' => 'cron-url'
            ),
        );

        $list = array();

        foreach ($this->module->cronTasks as $controller => $data) {
            $title = null;
            if (isset($data['title'][$this->context->language->iso_code])) {
                $title = $data['title'][$this->context->language->iso_code];
            } elseif (isset($data['title']['en'])) {
                $title = $data['title']['en'];
            }
            $list[] = array(
                'name' => $data['name'],
                'title' => $title,
                'frequency' => $data['frequency'],
                'url' => $this->context->link->getModuleLink(
                    $this->module->name,
                    $controller,
                    array(
                        'secure_key' => $this->module->secure_key
                    )
                ),
            );
        }

        $helper = new HelperList();
        $this->setHelperDisplay($helper);
        $helper->title = $this->module->l('Cron Tasks', 'AdminProcessMonitorController');
        $helper->actions = array('runCron');
        $helper->bulk_actions = array();
        $helper->no_link = true;
        return $helper->generateList($list, $fieldsList);
    }
    
    /**
     * We can't use the ObjectModel's "save", "add" or "update" methods.
     * PS will natively call ObjectModel hooks, using the class name of the
     * ObjectModel. On PS 1.6, the namespace is not escaped from the class name,
     * resulting in an invalid hook name, e.g. :
     * actionObjectShoppingfeedClasslib\Extensions\ProcessMonitor\ProcessMonitorObjectModelUpdateBefore
     */
    public function processDelete()
    {
        if (Validate::isLoadedObject($object = $this->loadObject()))
        {
            $objectDefinition = ObjectModel::getDefinition($object);
            if ($res = Db::getInstance()->delete(
                    $objectDefinition['table'],
                    '`'.pSQL($objectDefinition['primary']).'` = '.(int)$object->id
            )) {
                $this->redirect_after = self::$currentIndex.'&conf=1&token='.$this->token;
            }
            $this->errors[] = $this->module->l('An error occurred during deletion.', 'AdminProcessMonitorController');
        } else {
            $this->errors[] = $this->module->l('An error occurred while deleting the object.', 'AdminProcessMonitorController').
                ' <b>'.$this->table.'</b> '.
                $this->module->l('(cannot load object)', 'AdminProcessMonitorController');
        }
        
        return $object;
    }

    public function displayRunCronLink($token, $id, $name)
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/process_monitor/runCronButton.tpl');
    }

    /**
     * Process ajax cron launch
     * @throws \PrestaShopException
     */
    public function ajaxProcessGetLastCronDuration()
    {
        $cronName = Tools::getValue('cronName');
        if (!$cronName) {
            throw new \PrestaShopException('Cron name not found');
        }

        $process = new ProcessMonitorObjectModel();
        $process = $process->findOneByName($cronName);
        die(json_encode(array('duration' => $process->duration)));
    }

    /**
     * @throws \SmartyException
     */
    protected function getProcessModal()
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/process_monitor/process_modal.tpl');
    }
}
