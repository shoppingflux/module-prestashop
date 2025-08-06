<?php

/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedClasslib\Extensions\ProcessMonitor\Controllers\Admin\AdminProcessMonitorController;

/**
 * {@inheritdoc}
 */
class AdminShoppingfeedProcessMonitorController extends AdminProcessMonitorController
{
    /** @var Shoppingfeed */
    public $module;

    public function __construct()
    {
        parent::__construct();
        $select = [
            'id_shoppingfeed_processmonitor',
            'name',
            'IFNULL(pid,"") as pid',
            'duration',
            'last_update',
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
                $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl') . $this->content
            );
        }
    }

    public function renderCronTasks()
    {
        if (empty($this->module->cronTasks)) {
            throw new Exception($this->module->l('Unable to find cronTasks declaration in module.', 'AdminProcessMonitorController'));
        }

        $fieldsList = [
            'name' => [
                'title' => $this->module->l('Technical name', 'AdminProcessMonitorController'),
                'name' => 'name',
            ],
            'title' => [
                'title' => $this->module->l('Title', 'AdminProcessMonitorController'),
                'name' => 'title',
            ],
            'frequency' => [
                'title' => $this->module->l('Frequency', 'AdminProcessMonitorController'),
                'name' => 'frequency',
            ],
            'url' => [
                'title' => $this->l('URL'),
                'name' => 'url',
                'class' => 'cron-url',
            ],
        ];

        $list = [];

        foreach ($this->module->cronTasks as $controller => $data) {
            $title = null;
            $token = (new ShoppingfeedToken())->getDefaultToken();

            if (isset($data['title'][$this->context->language->iso_code])) {
                $title = $data['title'][$this->context->language->iso_code];
            } elseif (isset($data['title']['en'])) {
                $title = $data['title']['en'];
            }

            $listItem = [
                'name' => $data['name'],
                'title' => $title,
                'frequency' => $data['frequency'],
            ];

            if ($data['name'] == 'shoppingfeed:syncAll') {
                $listItem['url'] = $this->context->link->getModuleLink(
                    $this->module->name,
                    $controller,
                    [
                        'feed_key' => empty($token['feed_key']) ? '' : $token['feed_key'],
                    ]
                );
            } else {
                $listItem['url'] = $this->context->link->getModuleLink(
                    $this->module->name,
                    $controller,
                    [
                        'secure_key' => $this->module->secure_key,
                    ]
                );
            }

            $list[] = $listItem;
        }

        $helper = new HelperList();
        $this->setHelperDisplay($helper);
        $helper->title = $this->module->l('Cron Tasks', 'AdminProcessMonitorController');
        $helper->actions = ['runCron'];
        $helper->bulk_actions = [];
        $helper->no_link = true;

        return $helper->generateList($list, $fieldsList);
    }
}
