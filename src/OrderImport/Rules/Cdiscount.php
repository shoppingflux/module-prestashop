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
use Tools;
use Translate;

use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class Cdiscount extends \ShoppingfeedAddon\OrderImport\RuleAbstract
{
    public function isApplicable(OrderResource $apiOrder)
    {
        // TODO : Where is TotalFees on the new API ?
        return preg_match('#^cdiscount$#', Tools::strtolower($apiOrder->getChannel()->getName()));
    }
    
    
    public function onPostProcess($params)
    {
        /** @var \ShoppingfeedAddon\OrderImport\OrderData $orderData */
        $orderData = $params['orderData'];
        $apiOrder = $params['apiOrder'];
        $psOrder = new Order($params['sfOrder']->id_order);
        
        $logPrefix = sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'Cdiscount'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';
        
        ProcessLoggerHandler::logInfo(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Rule triggered.', 'Cdiscount'),
            'Order'
        );
        
        // TODO : Where is TotalFees on the new API ?
        return;
        $processingFees = null;
        
        // See old module _updatePrices
        // Retrieve the order invoice ID to associate it with the FDG.
        // This way, the FDG will appears in the invoice.
        $idOrderInvoice = Db::getInstance()->getValue('
        SELECT `id_order_invoice`
        FROM `'._DB_PREFIX_.'order_invoice`
        WHERE `id_order` =  '.(int)$psOrder->id);

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
            'product_price' => (float) $processingFees,
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
            'total_price_tax_incl' => (float) $processingFees,
            'total_price_tax_excl' => (float) $processingFees,
            'unit_price_tax_incl' => (float) $processingFees,
            'unit_price_tax_excl' => (float) $processingFees,
            'total_shipping_price_tax_incl' => 0,
            'total_shipping_price_tax_excl' => 0,
            'purchase_supplier_price' => 0,
            'original_product_price' => 0
        );

        // Insert the FDG-ShoppingFlux in the order details
        SfLogger::getInstance()->log(SF_LOG_ORDERS, 'Inserting Cdiscount fees, total fees = ' . $processingFees);
        Db::getInstance()->insert('order_detail', $fdgInsertFields);

        // insert doesn't return the id, we therefore need to make another request to find out the id_order_detail_fdg
        $sql = 'SELECT od.id_order_detail FROM '._DB_PREFIX_.'order_detail od
            WHERE od.id_order = '.(int)$psOrder->id.' AND od.product_reference = "FDG-ShoppingFlux"';
        $id_order_detail_fdg = Db::getInstance()->getValue($sql);

        // Insert the FDG in the tax details
        SfLogger::getInstance()->log(SF_LOG_ORDERS, 'Inserting Cdiscount fees in order_detail_tax, id_order_detail_fdg = '.$id_order_detail_fdg.', fdg_tax_amount = 0');
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
        foreach($orderFieldsToIncrease as $orderField => &$orderValue) {
            $orderValue = array(
                'type' => 'sql',
                'value' => '`' . pSQL($orderField) . '` + ' . (float)$processingFees
            );
        }
        Db::getInstance()->update('orders', $orderFieldsToIncrease, '`id_order` = '.(int)$psOrder->id);
        
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
                'value' => '`' . pSQL($orderInvoiceField) . '` + ' . (float)$processingFees
            );
        }
        Db::getInstance()->update('order_invoice', $orderInvoiceFieldsToIncrease, '`id_order` = '.(int)$psOrder->id);
        
        ProcessLoggerHandler::logSuccess(
            $logPrefix .
                Translate::getModuleTranslation('shoppingfeed', 'Fees added to order.', 'Cdiscount'),
            'Order'
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'If the order is from CDiscount and has the \'TotalFees\' field set.', 'Cdiscount');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Translate::getModuleTranslation('shoppingfeed', 'Adds an \'Operation Fee\' product to the order, so the amount will show in the invoice.', 'Cdiscount');
    }
}
