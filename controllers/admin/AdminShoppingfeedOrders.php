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

/**
 * This admin controller displays the module's general configuration forms
 */
class AdminShoppingfeedOrdersController extends ShoppingfeedAdminController
{
    public $bootstrap = true;

    public $table = 'shoppingfeed_order';

    protected $statuses_array = [];

    /**
     * @var array We would either have to query the DB or loop
     *            over the list each time we need to match a SF id to a PS id (see displayViewLink),
     *            so we'll loop once over the list when it's first retrieved and index each
     *            PS id with its matching SF id
     */
    protected $cache_sfIdToPsId = [];

    public function __construct()
    {
        parent::__construct();

        $this->_select = 'a.id_order, o.reference, ' .
            'CONCAT(LEFT(c.firstname, 1), \'. \', c.lastname) AS customer_name, ' .
            'o.total_paid, o.id_currency, a.payment_method, o.current_state, ' .
            'osl.name AS current_state_name, os.color AS current_state_color, ' .
            'a.id_order_marketplace, a.name_marketplace, ' .
            'IF(UNIX_TIMESTAMP(IFNULL(a.date_marketplace_creation, 0)) = 0, ' .
            'a.date_add, a.date_marketplace_creation) AS date_marketplace_creation';

        $this->_join = 'INNER JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = a.id_order ' .
            'INNER JOIN ' . _DB_PREFIX_ . 'customer c ON c.id_customer = o.id_customer ' .
            'LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON (os.id_order_state = o.current_state) ' .
            'LEFT JOIN ' . _DB_PREFIX_ . 'order_state_lang osl ON (os.id_order_state = osl.id_order_state AND osl.id_lang = ' . (int) $this->context->language->id . ')';

        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = [
            'id_order' => [
                'title' => $this->module->l('ID', 'AdminShoppingfeedOrders'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!id_order',
            ],
            'reference' => [
                'title' => $this->module->l('Reference', 'AdminShoppingfeedOrders'),
            ],
            'customer_name' => [
                'title' => $this->module->l('Customer', 'AdminShoppingfeedOrders'),
                'filter_key' => 'c!lastname',
            ],
            'total_paid' => [
                'title' => $this->module->l('Amount', 'AdminShoppingfeedOrders'),
                'align' => 'text-right',
                'callback' => 'setOrderCurrency',
            ],
            'current_state_name' => [
                'title' => $this->module->l('Status', 'AdminShoppingfeedOrders'),
                'type' => 'select',
                'color' => 'current_state_color',
                'list' => $this->statuses_array,
                'filter_key' => 'o!current_state',
                'filter_type' => 'int',
                'order_key' => 'current_state',
            ],
            'payment_method' => [
                'title' => $this->module->l('Payment method', 'AdminShoppingfeedOrders'),
            ],
            'id_order_marketplace' => [
                'title' => $this->module->l('Marketplace reference', 'AdminShoppingfeedOrders'),
            ],
            'name_marketplace' => [
                'title' => $this->module->l('Marketplace', 'AdminShoppingfeedOrders'),
            ],
            'date_marketplace_creation' => [
                'title' => $this->module->l('Marketplace creation date', 'AdminShoppingfeedOrders'),
            ],
        ];

        $this->actions = [
            'view',
        ];

        $this->_defaultOrderBy = 'date_marketplace_creation';
        $this->_defaultOrderWay = 'DESC';
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        $this->module->setBreakingChangesNotices();

        parent::initContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        $id_lang,
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null,
        $id_lang_shop = false
    ) {
        $result = parent::getList(
            $id_lang,
            $order_by,
            $order_way,
            $start,
            $limit,
            $id_lang_shop
        );

        // See doc for $cache_sfIdToPsId
        foreach ($this->_list as $listItem) {
            $this->cache_sfIdToPsId[$listItem['id_shoppingfeed_order']] = $listItem['id_order'];
        }

        return $result;
    }

    /**
     * From \AdminOrdersController
     *
     * @param arry $echo
     * @param arry $tr
     *
     * @return type
     */
    public static function setOrderCurrency($echo, $tr)
    {
        return Tools::displayPrice($echo, (int) $tr['id_currency']);
    }

    /**
     * OVERRIDE
     * On PS 1.7.6.2, we can't change the list's row link to whatever we want, so
     * we'll redirect when trying to view a shoppingfeed_order
     */
    public function initProcess()
    {
        parent::initProcess();
        if ($this->action == 'view') {
            $sfOrder = new ShoppingfeedOrder(Tools::getValue('id_shoppingfeed_order'));
            $this->redirectToOrder((int) $sfOrder->id_order);
        }
    }

    protected function redirectToOrder($idOrder)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminOrders') . '&' .
                implode('&', ['id_order=' . (int) $idOrder, 'vieworder'])
            );
        }

        Tools::redirectAdmin(
            $this->context->link->getAdminLink(
                'AdminOrders',
                true,
                [],
                [
                    'id_order' => (int) $idOrder,
                    'vieworder' => true,
                ]
            )
        );
    }
}
