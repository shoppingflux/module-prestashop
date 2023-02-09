<?php
/*
 * 2007-2023 PayPal
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 *  versions in the future. If you wish to customize PrestaShop for your
 *  needs please refer to http://www.prestashop.com for more information.
 *
 *  @author 2007-2023 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *  @copyright PayPal
 *
 */

namespace ShoppingfeedAddon\OrderImport\Rules;

use Db;
use Order;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Tools;
use Validate;

class RetifTax extends RuleAbstract implements RuleInterface
{
    protected $logPrefix = '';

    protected $db;

    public function __construct($configuration = [])
    {
        parent::__construct($configuration);

        $this->db = Db::getInstance();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->logPrefix = sprintf(
            $this->l('[Order: %s]', 'RetifTax'),
            $apiOrder->getId()
        );
        $this->logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (preg_match('#^retif#', Tools::strtolower($apiOrder->getChannel()->getName())) == false) {
            return false;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Rule triggered.', 'RetifTax'),
            'Order'
        );

        return true;
    }

    public function getDescription()
    {
        return $this->l('Remove a tax in an order', 'RetifTax');
    }

    public function getConditions()
    {
        return $this->l('If an order came from Retif.', 'RetifTax');
    }

    public function onPostProcess($params)
    {
        if (empty($params['id_order'])) {
            return;
        }

        $order = new Order($params['id_order']);

        if (false == Validate::isLoadedObject($order)) {
            return;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Reset the tax', 'RetifTax'),
            'Order',
            $params['id_order']
        );

        foreach ($order->getOrderDetailList() as $orderDetail) {
            $this->db->delete(
                'order_detail_tax',
                'id_order_detail = ' . (int) $orderDetail['id_order_detail']
            );
        }
    }
}
