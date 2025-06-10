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

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class GroupCustomer extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    protected $db;

    const DEFAULT_GROUP_CUSTOMER = 3;

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->db = \Db::getInstance();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->logPrefix = sprintf(
            $this->l('[Order: %s]', 'GroupCustomer'),
            $apiOrder->getId()
        );
        $this->logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (!empty($this->configuration['group_customer'])) {
            return true;
        }

        return false;
    }

    public function onCustomerCreation($params)
    {
        $params['customer']->id_default_group = $this->configuration['group_customer'];
        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Default customer group is: ', 'GroupCustomer') . $this->configuration['group_customer']
        );
    }

    public function beforeBillingAddressCreation($params)
    {
        $orderData = new OrderData($params['apiOrder']);
        /** @var \ShoppingfeedAddon\OrderImport\OrderCustomerData $orderCustomerData */
        $orderCustomerData = $orderData->getCustomer();
        $customer = new \Customer();
        $customer->getByEmail($orderCustomerData->getEmail());
        $customer->cleanGroups();
        $groups[] = $this->configuration['group_customer'];
        $customer->addGroups($groups);
        $customer->id_default_group = $this->configuration['group_customer'];

        $data['id_default_group'] = $this->configuration['group_customer'];
        \Db::getInstance()->update('customer', $data, '`id_customer` = ' . (int) $customer->id);

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Customer is added to the group ', 'GroupCustomer') . $this->configuration['group_customer']
        );
    }

    public function getConfigurationSubform()
    {
        $context = \Context::getContext();
        if (\Validate::isLoadedObject($context->employee)) {
            $id_lang = $context->employee->id_lang;
        } else {
            $id_lang = (int) \Configuration::get('PS_LANG_DEFAULT');
        }
        $groups = [
            [
                'type' => 'select',
                'label' => $this->l('Customer group', 'GroupCustomer'),
                'desc' => $this->l('By selecting a group, all customers imported from marketplaces will be mapped to this group.', 'GroupCustomer'),
                'name' => 'group_customer',
                'required' => false,
                'options' => [
                    'query' => \Group::getGroups($id_lang),
                    'id' => 'id_group',
                    'name' => 'name',
                ],
            ],
        ];

        return $groups;
    }

    public function getDescription()
    {
        return $this->l('Turn the customer into the selected group.', 'GroupCustomer');
    }

    public function getConditions()
    {
        return $this->l('For all orders.', 'GroupCustomer');
    }

    /**
     * {@inheritdoc}
     */
    protected function l($msg, $domain)
    {
        return \Translate::getModuleTranslation('shoppingfeed', $msg, $domain);
    }

    protected function getDefaultConfiguration()
    {
        return [
            'group_customer' => self::DEFAULT_GROUP_CUSTOMER,
        ];
    }
}
