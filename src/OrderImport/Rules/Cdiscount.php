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

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Order;
use OrderDetail;
use Tools;
use DB;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class Cdiscount extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // TODO : Where is TotalFees on the new API ?
        // TODO : OrderResource should likely have a "getAdditionalFields" method
        $apiOrderData = $apiOrder->toArray();
        $apiOrderAdditionalFields = $apiOrderData['additionalFields'];

        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()))
            && !empty($apiOrderAdditionalFields['INTERETBCA'])
            && $apiOrderAdditionalFields['INTERETBCA'] > 0;
    }


    public function onPostProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $psOrder = new Order($params['sfOrder']->id_order);
        // TODO : Where is TotalFees on the new API ?
        $processingFees = $orderData->additionalFields['INTERETBCA'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'Cdiscount'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix .
               $this->l('Rule triggered.', 'Cdiscount'),
            'Order'
        );


        // See old module _updatePrices
        // Retrieve the order invoice ID to associate it with the FDG.
        // This way, the FDG will appears in the invoice.
        $idOrderInvoice = Db::getInstance()->getValue('
        SELECT `id_order_invoice`
        FROM `'._DB_PREFIX_.'order_invoice`
        WHERE `id_order` =  '.(int)$psOrder->id);

        $processingFees = Tools::ps_round($processingFees, 2);

        $fdgInsertFields = array(
            'id_order' => (int) $psOrder->id,
            'id_order_invoice' => empty($idOrderInvoice) ? 0 : (int)$idOrderInvoice,
            'id_warehouse' => 0,
            'id_shop' => (int) $psOrder->id_shop,
            'product_id' => 0,
            'product_attribute_id' => 0,
            'product_name' => 'CDiscount fees - ShoppingFlux',
            'product_quantity' => 1,
            'product_quantity_in_stock' => 1,
            'product_quantity_refunded' => 0,
            'product_quantity_return' => 0,
            'product_quantity_reinjected' => 0,
            'product_price' => $processingFees,
            'reduction_percent' => 0,
            'reduction_amount' => 0,
            'reduction_amount_tax_incl' => 0,
            'reduction_amount_tax_excl' => 0,
            'group_reduction' => 0,
            'product_quantity_discount' => 0,
            'product_ean13' => null,
            'product_upc' => null,
            'product_reference' => 'FDG-ShoppingFlux',
            'product_supplier_reference' => null,
            'product_weight' => 0,
            'tax_computation_method' => 0,
            'tax_name' => 0,
            'tax_rate' => 0,
            'ecotax' => 0,
            'ecotax_tax_rate' => 0,
            'discount_quantity_applied' => 0,
            'download_hash' => null,
            'download_nb' => 0,
            'download_deadline' => null,
            'total_price_tax_incl' => $processingFees,
            'total_price_tax_excl' => $processingFees,
            'unit_price_tax_incl' => $processingFees,
            'unit_price_tax_excl' => $processingFees,
            'total_shipping_price_tax_incl' => 0,
            'total_shipping_price_tax_excl' => 0,
            'purchase_supplier_price' => 0,
            'original_product_price' => 0
        );

        // Insert the FDG-ShoppingFlux in the order details
        $orderDetail = new OrderDetail();
        foreach ($fdgInsertFields as $key => $value) {
            $orderDetail->{$key} = $value;
        }
        $orderDetail->add(true, true);

        // insert doesn't return the id, we therefore need to make another request to find out the id_order_detail_fdg
        $sql = 'SELECT od.id_order_detail FROM '._DB_PREFIX_.'order_detail od
            WHERE od.id_order = '.(int)$psOrder->id.' AND od.product_reference = "FDG-ShoppingFlux"';
        $id_order_detail_fdg = Db::getInstance()->getValue($sql);

        // Insert the FDG in the tax details
        $insertOrderDetailTaxFgd = array(
            'id_order_detail' => $id_order_detail_fdg,
            'id_tax' => 0,
            'unit_amount'  => 0,
            'total_amount' => 0,
        );
        Db::getInstance()->insert('order_detail_tax', $insertOrderDetailTaxFgd);

        // Add fees to order
        $orderFieldsToIncrease = array(
            'total_paid' => '',
            'total_paid_tax_incl' => '',
            'total_paid_tax_excl' => '',
            'total_paid_real' => '',
            'total_products' => '',
            'total_products_wt' => '',
        );
        $psOrder = new Order($params['sfOrder']->id_order);
        foreach($orderFieldsToIncrease as $orderField => &$orderValue) {
            $psOrder->{$orderField} = $psOrder->{$orderField} + $processingFees;
        }
        $psOrder->save();

        // Add fees to order invoice
        $orderInvoiceFieldsToIncrease = array(
            'total_paid_tax_incl' => '',
            'total_paid_tax_excl' => '',
            'total_products' => '',
            'total_products_wt' => '',
        );
        foreach($orderInvoiceFieldsToIncrease as $orderInvoiceField => &$orderInvoiceValue) {
            $orderInvoiceValue = array(
                'type' => 'sql',
                'value' => '`' . pSQL($orderInvoiceField) . '` + ' . $processingFees
            );
        }
        Db::getInstance()->update('order_invoice', $orderInvoiceFieldsToIncrease, '`id_order` = '.(int)$psOrder->id);

        ProcessLoggerHandler::logSuccess(
            $logPrefix .
                $this->l('Fees added to order.', 'Cdiscount'),
            'Order'
        );
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('If the order is from CDiscount and has the \'INTERETBCA\' field set.', 'Cdiscount');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('Adds an \'Operation Fee\' product to the order, so the amount will show in the invoice.', 'Cdiscount');
    }
}
