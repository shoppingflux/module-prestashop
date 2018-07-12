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
 * @version   develop
 */

TotLoader::import('shoppingfeed\classlib\extensions\ProcessLogger\ProcessLoggerObjectModel');

class ShoppingfeedAdminProcessLoggerController extends ModuleAdminController
{
    /** @var bool $bootstrap Active bootstrap for Prestashop 1.6 */
    public $bootstrap = true;

    /** @var Module Instance of your module automatically set by ModuleAdminController */
    public $module;

    /** @var string Associated object class name */
    public $className = 'ShoppingfeedProcessLoggerObjectModel';

    /** @var string Associated table name */
    public $table = 'shoppingfeed_processlogger';

    /** @var string|false Object identifier inside the associated table */
    public $identifier = 'id_shoppingfeed_processlogger';

    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_defaultOrderBy = 'id_shoppingfeed_processlogger';

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
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Would you like to delete the selected items?'),
            )
        );

        $this->fields_list = array(
            'id_shoppingfeed_processlogger' => array(
                'title'  => $this->l('ID'),
                'align'  => 'center',
                'class'  => 'fixed-width-xs',
                'search' => true,
            ),
            'name'                      => array(
                'title' => $this->l('Name'),
            ),
            'msg'                       => array(
                'title' => $this->l('Message'),
            ),
            'level'                     => array(
                'title'    => $this->l('Level'),
                'callback' => 'getLevel',
            ),
            'object_name'               => array(
                'title' => $this->l('Object Name'),
            ),
            'object_id'                 => array(
                'title'    => $this->l('Object ID'),
                'callback' => 'getObjectId',
            ),
            'date_add'                  => array(
                'title' => $this->l('Date'),
            ),
        );

        $this->fields_options = array(
            'processLogger' => array(
                'image'       => '../img/admin/cog.gif',
                'title'       => $this->l('Process Logger Settings'),
                'description' => $this->l('Here you can change the default configuration for this Process Logger'),
                'fields'      => array(
                    'SHOPPINGFEED_EXTLOGS_ERASING_DISABLED' => array(
                        'title'        => $this->l('Disable auto erasing'),
                        'hint'         => $this->l('If disabled, logs will be automatically erased after the delay'),
                        'validation'   => 'isBool',
                        'cast'         => 'intval',
                        'type'         => 'bool',
                    ),
                    'SHOPPINGFEED_EXTLOGS_ERASING_DAYSMAX' => array(
                        'title'        => $this->l('Auto erasing delay (in days)'),
                        'hint'         => $this->l('Choose the number of days you want to keep logs in database'),
                        'validation'   => 'isInt',
                        'cast'         => 'intval',
                        'type'         => 'text',
                        'defaultValue' => 5,
                    ),
                ),
                'submit'      => array('title' => $this->l('Save')),
            ),
        );
    }

    /**
     * @param $echo string Value of field
     * @param $tr array All data of the row
     * @return string
     */
    public function getObjectId($echo, $tr)
    {
        unset($tr);
        return empty($echo) ? '' : $echo;
    }

    /**
     * @param $echo string Value of field
     * @param $tr array All data of the row
     * @return string
     */
    public function getLevel($echo, $tr)
    {
        unset($tr);
        switch ($echo) {
            case 'info':
                $echo = '<span class="badge badge-info">'.$echo.'</span>';
                break;
            case 'success':
                $echo = '<span class="badge badge-success">'.$echo.'</span>';
                break;
            case 'error':
                $echo = '<span class="badge badge-danger">'.$echo.'</span>';
                break;
        }
        return $echo;
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

}
