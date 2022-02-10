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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shoppingfeed/vendor/autoload.php';

use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedOrderImportController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS(_PS_MODULE_DIR_ . 'shoppingfeed/views/css/process_monitor/process_monitor.css');
        Media::addJsDef(
            [
                'shoppingfeedProcessMonitorController' => $this->context->link->getAdminLink('AdminShoppingfeedProcessMonitor'),
                'shoppingfeedProcessOrderImportController' => $this->context->link->getAdminLink('AdminShoppingfeedOrderImport'),
            ]
        );
        $this->addJS($this->module->getPathUri() . 'views/js/order_import/import.js');
    }

    public function initContent()
    {
        $this->content = $this->welcomeForm();
        $this->content .= $this->getProcessModal();

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
        $helper->base_folder = $this->getTemplatePath() . 'shoppingfeed_order/';
        $helper->base_tpl = 'shoppingfluxexport.tpl';
        $this->context->smarty->assign(
            'isOrderSyncAvailable',
            Module::isInstalled('shoppingfluxexport') === true && Module::isEnabled('shoppingfluxexport') === false
        );

        return $helper->generateForm([['form' => $fields_form]]);
    }

    protected function getProcessModal()
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'shoppingfeed/views/templates/admin/shoppingfeed_order/process_modal.tpl');
    }

    private function checkModuleShoppingfluxexport()
    {
        if (Module::isInstalled('shoppingfluxexport') === false) {
            $this->ajaxDie(
                Tools::jsonEncode(
                    [
                        'errors' => [
                            $this->module->l('shoppingfluxexport not install, impossible to import order from shoppingfluxexport', 'adminshoppingfeedorderimport'),
                        ],
                    ]
                )
            );
        }

        if (Module::isEnabled('shoppingfluxexport') === true) {
            $this->ajaxDie(
                Tools::jsonEncode(
                    [
                        'errors' => [
                            $this->module->l('shoppingfluxexport is enabled, impossible to import order from shoppingfluxexport', 'adminshoppingfeedorderimport'),
                        ],
                    ]
                )
            );
        }
    }

    private function createSfOrder($apiOrder, $id_order, $id_shoppingfeed_token)
    {
        $orderData = new OrderData($apiOrder);
        $sfOrder = new ShoppingfeedOrder();
        $sfOrder->id_order = $id_order;
        $sfOrder->id_internal_shoppingfeed = (string) $apiOrder->getId();
        $sfOrder->id_order_marketplace = $apiOrder->getReference();
        $sfOrder->name_marketplace = $apiOrder->getChannel()->getName();
        $sfOrder->id_shoppingfeed_token = (int) $id_shoppingfeed_token;
        $paymentInformation = $orderData->payment;
        $sfOrder->payment_method = !empty($paymentInformation['method']) ? $paymentInformation['method'] : '-';
        if ($orderData->createdAt->getTimestamp() !== 0) {
            $sfOrder->date_marketplace_creation = $orderData->createdAt->format('Y-m-d H:i:s');
        }
        $sfOrder->save();
    }

    public function ajaxProcessImportShoppingfluxexport()
    {
        $this->checkModuleShoppingfluxexport();
        $filters = [
            'acknowledgment' => 'acknowledged',
            'since' => date('Y-m-d\TH:m:s', strtotime('-7 days')),
        ];
        $errors = [];
        $resultImport = [];
        $sft = new ShoppingfeedToken();
        $shoppingfluxexport = Module::getInstanceByName('shoppingfluxexport');
        $allTokensShoppingfluxexport = $shoppingfluxexport->getAllTokensOfShop(false, true);
        foreach ($allTokensShoppingfluxexport as $curentTokensShoppingfluxexport) {
            $currentToken = $sft->findByToken($curentTokensShoppingfluxexport['token']);
            if ($currentToken === false) {
                $errors[] = $error = sprintf(
                    $this->module->l('Token %s not found in shoppingfeed', 'adminshoppingfeedorderimport'),
                    $curentTokensShoppingfluxexport['token']
                );
                ProcessLoggerHandler::logError($error);
                continue;
            }
            try {
                $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($currentToken['id_shoppingfeed_token']);
                if ($shoppingfeedApi === false) {
                    $errors[] = $error = sprintf(
                        $this->module->l('Could not retrieve Shopping Feed API for token %s.', 'adminshoppingfeedorderimport'),
                        $curentTokensShoppingfluxexport['token']
                    );
                    ProcessLoggerHandler::logError($error);
                    continue;
                }
                $result = $shoppingfeedApi->getOrdersFromSf($filters);
            } catch (Exception $e) {
                $errors[] = $error = sprintf(
                    $this->module->l('Could not retrieve orders to import : %s.', 'adminshoppingfeedorderimport'),
                    $curentTokensShoppingfluxexport['token']
                );
                ProcessLoggerHandler::logError($error);
                continue;
            }
            $resultImport[$curentTokensShoppingfluxexport['token']] = [
                'token' => $curentTokensShoppingfluxexport['token'],
                'total' => count($result),
                'success' => 0,
                'errors' => 0,
            ];
            foreach ($result as $apiOrder) {
                if (ShoppingfeedOrder::existsInternalId($apiOrder->getId())) {
                    $error = sprintf(
                        $this->module->l('Order %s not imported; already present.', 'adminshoppingfeedorderimport'),
                        $apiOrder->getId()
                    );
                    ProcessLoggerHandler::logInfo($error);
                    ++$resultImport[$curentTokensShoppingfluxexport['token']]['errors'];
                    continue;
                }
                $apiOrderData = $apiOrder->toArray();
                $id_order = $apiOrderData['storeReference'];
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                    SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . (int) $id_order
                );
                if ($result === false) {
                    $error = sprintf(
                        $this->module->l('Order %s not imported; order not found.', 'adminshoppingfeedorderimport'),
                        $apiOrder->getId()
                    );
                    ProcessLoggerHandler::logInfo($error);
                    ++$resultImport[$curentTokensShoppingfluxexport['token']]['errors'];
                    continue;
                }
                try {
                    $this->createSfOrder($apiOrder, (int) $id_order, (int) $currentToken['id_shoppingfeed_token']);
                    ++$resultImport[$curentTokensShoppingfluxexport['token']]['success'];
                } catch (Exception $e) {
                    $error = sprintf(
                        $this->module->l('Order %s not imported, %s.', 'adminshoppingfeedorderimport'),
                        $apiOrder->getId(),
                        $e->getMessage()
                    );
                    ProcessLoggerHandler::logInfo($error);
                    ++$resultImport[$curentTokensShoppingfluxexport['token']]['errors'];
                    continue;
                }
            }
        }
        foreach ($resultImport as $result) {
            $errors[] = $log = sprintf(
                $this->module->l('Token %s: %d orders to import; %d success; %d errors', 'adminshoppingfeedorderimport'),
                $result['token'],
                $result['total'],
                $result['success'],
                $result['errors']
            );
            ProcessLoggerHandler::logInfo($log);
        }
        ProcessLoggerHandler::closeLogger();
        $this->ajaxDie(
            Tools::jsonEncode(['errors' => $errors])
        );
    }
}
