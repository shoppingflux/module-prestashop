<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from 202 ecommerce is strictly forbidden.
 *
 * @author    202 ecommerce <contact@202-ecommerce.com>
 * @copyright Copyright (c) 202 ecommerce 2017
 * @license   Commercial license
 *
 * Support <support@202-ecommerce.com>
 */

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Registry;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;


require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedCarrier.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedOrder.php');
require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedPaymentModule.php');

class ShoppingfeedOrderImportActions extends DefaultActions
{
    public static function getLogPrefix($id_internal_shoppingfeed = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderImportActions'),
            $id_internal_shoppingfeed
        );
    }
    
    // TODO : we'll need to handle specific marketplaces / carriers too
    // We may want to call some hooks during this process...
    // Should we delete what we created if the order creation fails ?
    
    public function verifyOrder()
    {
        
        if (empty($this->conveyor['apiOrder'])) {
            ProcessLoggerHandler::logError(
                $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $logPrefix = self::getLogPrefix($apiOrder->getId());
        
        // See old module checkData()
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Checking order...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // Check if order already exists
        if (ShoppingfeedOrder::existsInternalId($apiOrder->getId())) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Order not imported; already present.', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        
        // Check products
        $this->conveyor['prestashopProducts'] = array();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        foreach($apiOrder->getItems() as $apiProduct) {
            $psProduct = $sfModule->mapPrestashopProduct(
                $apiProduct->getReference(),
                $this->conveyor['id_shop']
            );
            
            // Does the product exist ?
            if (!Validate::isLoadedObject($psProduct)) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Product reference %s does not match a product.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->getReference()
                    ),
                    'Order'
                );
                return false;
            }
            
            // Is the product active ?
            if (!$psProduct->active) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Product %s is inactive.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
            
            // Can the product be ordered ?
            if (!$psProduct->available_for_order) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Product %s is not available for order.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    ),
                    'Order'
                );
                return false;
            }
            
            $this->conveyor['prestashopProducts'][$apiProduct->getReference()] = $psProduct;
        }
        
        // Check carrier
        $apiOrderShipment = $apiOrder->getShipment();
        $carrier = false;
        $sfCarrier = ShoppingfeedCarrier::getByMarketplaceAndName(
            $apiOrder->getChannel()->getName(),
            $apiOrderShipment['carrier']
        );
        if (Validate::isLoadedObject($sfCarrier)) {
            $carrier = Carrier::getCarrierByReference($sfCarrier->id_carrier_reference);
        }
        
        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE))) {
            $carrier = Carrier::getCarrierByReference(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }
        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get('PS_CARRIER_DEFAULT'))) {
            $carrier = new Carrier(Configuration::get('PS_CARRIER_DEFAULT'));
        }
        if (!Validate::isLoadedObject($carrier)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Could not find a valid carrier for order.', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        }
        $this->conveyor['carrier'] = $carrier;
        
        if (!Validate::isLoadedObject($sfCarrier)) {
            $sfCarrier = new ShoppingfeedCarrier();
            $sfCarrier->name_marketplace = $apiOrder->getChannel()->getName();
            $sfCarrier->name_carrier = $apiOrderShipment['carrier'];
            $sfCarrier->id_carrier_reference = $carrier->id;
            $sfCarrier->is_new = true;
            $sfCarrier->save();
        }
        
        return true;
    }
    
    public function createOrderCart()
    {
        if (empty($this->conveyor['apiOrder'])) {
            ProcessLoggerHandler::logError(
                $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            return false;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $logPrefix = self::getLogPrefix($apiOrder->getId());
        
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Start cart creation...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // TODO : We might want to split this in functions
        // Try to retrieve customer using the billing address mail
        $apiBillingAddress = $apiOrder->getBillingAddress();
        if (Validate::isEmail($apiBillingAddress['email'])) {
            $customerEmail = $apiBillingAddress['email'];
        } else {
            $customerEmail = $apiOrder->getId()
                . '@' . $apiOrder->getChannel()->getName()
                . '.sf';
        }
        
        // see old module _getCustomer()
        $customer = Customer::getByEmail($customerEmail);
        // Create customer if it doesn't exist
        if (!$customer) {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Creating customer...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        
            $customer = new Customer();
            $customer->lastname = !empty($apiBillingAddress['lastName']) ? Tools::substr($apiBillingAddress['lastName'], 0, 32) : '-';
            $customer->firstname = !empty($apiBillingAddress['firstName']) ? Tools::substr($apiBillingAddress['firstName'], 0, 32) : '-';
            $customer->passwd = md5(pSQL(_COOKIE_KEY_.rand()));
            $customer->id_default_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $customer->email = $customerEmail;
            $customer->newsletter = 0;
            $customer->add();
        } else {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Retrieved customer from billing address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
        }
        $this->conveyor['customer'] = $customer;
        
        // Create or update addresses
        try {
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Creating/updating billing address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            $id_billing_address = $this->getOrderAddressId(
                $apiBillingAddress,
                $customer->id,
                'Billing-'.$apiOrder->getId(),
                $apiOrder->getChannel()->getName(),
                $apiOrder->getShipment()
            );
            $this->conveyor['id_billing_address'] = $id_billing_address;
            
            ProcessLoggerHandler::logInfo(
                $logPrefix . ' ' .
                    $this->l('Creating/updating shipping address...', 'ShoppingfeedOrderImportActions'),
                'Order'
            );
            $id_shipping_address = $this->getOrderAddressId(
                $apiBillingAddress,
                $customer->id,
                'Billing-'.$apiOrder->getId(),
                $apiOrder->getChannel()->getName(),
                $apiOrder->getShipment()
            );
            $this->conveyor['id_shipping_address'] = $id_shipping_address;
        } catch (Exception $ex) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' . $ex->getMessage(),
                'Order'
            );
            return false;
        }
        
        /* Check products quantities
         * 
         * Check if there is enough stock associated to the products before
         * creating the order. If the stock is not sufficient, it will be
         * increased just enough to avoid an error during the creation of the
         * order.
         *
         * For orders managed directly by a marketplace, the product quantity
         * should not be impacted on Prestashop.
         * Therefore, we'll increase the stock so that it won't be decreased
         * after validating the order.
         */
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Checking products quantities...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        $isMarketplaceManagedQuantities = ShoppingfeedOrder::isMarketplaceManagedQuantities($apiOrder->getChannel()->getName());
        $isAdvancedStockEnabled = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 1 ? true : false;
        foreach($apiOrder->getItems() as $apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->getReference()];
            $useAdvancedStock = $isAdvancedStockEnabled && $psProduct->advanced_stock_management;
            
            // If the stock is managed by the marketplace
            if ($isMarketplaceManagedQuantities) {
                // We directly add the ordered quantity; it will be deduced when
                // the order is validated
                $currentStock = StockAvailable::getQuantityAvailableByProduct(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $this->conveyor['id_shop']
                );
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Order is managed by marketplace %s, increase product %s stock : original %d, add %d.', 'ShoppingfeedOrderImportActions'),
                        $apiOrder->getChannel()->getName(),
                        $apiProduct->getReference(),
                        (int)$currentStock,
                        (int)$apiProduct->getQuantity()
                    ),
                    'Order'
                );
                StockAvailable::updateQuantity(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    (int)$apiProduct->getQuantity(),
                    $this->conveyor['id_shop']
                );
                continue;
            }
            
            // If the product uses advanced stock management
            if ($useAdvancedStock) {
                // If there's not enough stock to place the order
                if (!$this->checkAdvancedStockQty($psProduct, $apiProduct->getQuantity())) {
                    ProcessLoggerHandler::logError(
                        sprintf(
                            $logPrefix . ' ' .
                                $this->l('Not enough stock for product %s.', 'ShoppingfeedOrderImportActions'),
                            $apiProduct->getReference()
                        ),
                        'Order'
                    );
                    return false;
                }
                continue;
            }
            
            // If the product doesn't use advanced stock management
            // If there's not enough stock to place the order
            if (!$psProduct->checkQty($apiProduct->getQuantity())) {
                // Add just enough stock
                $currentStock = StockAvailable::getQuantityAvailableByProduct(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $this->conveyor['id_shop']
                );
                $stockToAdd = (int)$apiProduct->getQuantity() - (int)$currentStock;
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Not enough stock for product %s: original %d, required %d, add %d.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->getReference(),
                        (int)$currentStock,
                        (int)$apiProduct->getQuantity(),
                        (int)$stockToAdd
                    ),
                    'Order'
                );
                StockAvailable::updateQuantity(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $stockToAdd,
                    $this->conveyor['id_shop']
                );
            }
        }
        
        // See old module _getCart()
        $paymentInformation = $apiOrder->getPaymentInformation();
        $currency = $paymentInformation['currency'];
        
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Creating cart...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // Create cart
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = $id_billing_address;
        $cart->id_address_delivery = $id_shipping_address;
        $cart->id_currency = Currency::getIdByIsoCode((string)$currency == '' ? 'EUR' : (string)$currency);
        $cart->id_lang = Configuration::get('PS_LANG_DEFAULT');
        
        $cart->recyclable = 0;
        $cart->secure_key = md5(uniqid(rand(), true));
        
        $cart->id_carrier = $this->conveyor['carrier']->id;
        $cart->add();

        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Adding products to cart...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // Add products to cart
        foreach($apiOrder->getItems() as $apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->getReference()];
            try {
                $addToCartResult = $cart->updateQty(
                    $apiProduct->getQuantity(),
                    $psProduct->id,
                    $psProduct->id_product_attribute
                );
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Could not add product %s to cart : %s', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->getReference(),
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    ),
                    'Order'
                );
                return false;
            }
            
            if ($addToCartResult < 0 || $addToCartResult === false) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Could not add product %s to cart.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->getReference()
                    ),
                    'Order'
                );
                return false;
            }
        }

        $cart->update();
        $this->conveyor['cart'] = $cart;
        
        return true;
    }
    
    public function validateOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        /** @var Cart $apiOrder */
        $cart = $this->conveyor['cart'];
        
        $logPrefix = self::getLogPrefix($apiOrder->getId());
        
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Validate order...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // See old module _validateOrder()
        $paymentModule = new ShoppingfeedPaymentModule();
        $paymentModule->name = 'sfpayment';
        $paymentModule->active = true;

        //we need to flush the cart because of cache problems
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->getDeliveryOption(null, false, false);
        
        Context::getContext()->currency = new Currency((int)$cart->id_currency);
        
        if (!Context::getContext()->country->active) {
            ProcessLoggerHandler::logInfo(
                sprintf(
                    $logPrefix . ' ' .
                        $this->l('Current context country %d is not active.', 'ShoppingfeedOrderImportActions'),
                    Context::getContext()->country->id
                ),
                'Order'
            );
            $addressDelivery = new Address($cart->id_address_delivery);
            if (Validate::isLoadedObject($addressDelivery)) {
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $logPrefix . ' ' .
                            $this->l('Setting context country to %d.', 'ShoppingfeedOrderImportActions'),
                        $addressDelivery->id_country
                    ),
                    'Order'
                );
                Context::getContext()->country = new Country($addressDelivery->id_country);
            }
        }
        
        // Validate order with payment module
        // TODO : we should change the customer mail before this, otherwise he'll get a confirmation mail
        $amount_paid = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);
        $paymentModule->validateOrder(
            (int)$cart->id,
            2,
            $amount_paid,
            Tools::strtolower($apiOrder->getChannel()->getName()),
            null,
            array(),
            $cart->id_currency,
            false,
            $cart->secure_key
        );
        $this->conveyor['id_order'] = $paymentModule->currentOrder;
        $this->conveyor['order_reference'] = $paymentModule->currentOrderReference;

        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Creating module sfOrder...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        // Create the ShoppingfeedOrder here; we need to know if it's been created
        // after this point
        $sfOrder = new ShoppingfeedOrder();
        $sfOrder->id_order = $this->conveyor['id_order'];
        $sfOrder->id_internal_shoppingfeed = (string)$apiOrder->getId();
        $sfOrder->id_order_marketplace = $apiOrder->getReference();
        $sfOrder->name_marketplace = $apiOrder->getChannel()->getName();
        
        $paymentInformation = $apiOrder->getPaymentInformation();
        $sfOrder->payment_method = $paymentInformation['method'];
        
        if ($apiOrder->getCreatedAt()->getTimestamp() != 0) {
            $sfOrder->date_marketplace_creation = $apiOrder->getCreatedAt()->format('Y-m-d H:i:s');
        }
        
        $sfOrder->save();
        $this->conveyor['sfOrder'] = $sfOrder;
        
        return true;
   }
    
    public function acknowledgeOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        
        $logPrefix = self::getLogPrefix($apiOrder->getId());
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Acknowledging order import...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );
        
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_shop']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        $result = $shoppingfeedApi->acknowledgeOrder(
            $this->conveyor['sfOrder']->id_order_marketplace,
            $this->conveyor['sfOrder']->name_marketplace,
            $this->conveyor['sfOrder']->id_order
        );
        if (!$result) {
            ProcessLoggerHandler::logError(
                $logPrefix .
                    $this->l('Failed to acknowledge order on Shoppingfeed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );
            return false;
        }
        
        return true;
    }
    
    public function recalculateOrderPrices()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $psOrder = new Order((int)$this->conveyor['id_order']);
        
        
        $logPrefix = self::getLogPrefix($apiOrder->getId());
        ProcessLoggerHandler::logInfo(
            $logPrefix . ' ' .
                $this->l('Recalculating order prices...', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // We may have multiple orders created for the same reference, (advanced stock management)
        // therefore the total price of each orders needs to be calculated separately
        $ordersList = array();
        
        // See old module _updatePrices()
        /** @var ShoppingFeed\Sdk\Api\Order\OrderItem $apiProduct */
        foreach($apiOrder->getItems() as $apiProduct) {
            /** @var Product $psProduct */
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->getReference()];
            
            $query = new DbQuery();
            $query->select('tax.rate AS tax_rate, od.id_order_detail, od.id_order')
                ->from('order_detail', 'od')
                ->leftJoin('orders', 'o', 'o.id_order = od.id_order')
                ->leftJoin('order_detail_tax', 'odt', 'odt.id_order_detail = od.id_order_detail')
                ->leftJoin('tax', 'tax', 'tax.id_tax = odt.id_tax')
                ->where('o.reference LIKE "' . pSQL($this->conveyor['order_reference']). '"')
                ->where('product_id = ' . (int)$psProduct->id)
            ;
            if ($psProduct->id_product_attribute) {
                $query->where('product_attribute_id = ' . (int)$psProduct->id_product_attribute);
            }
            $productOrderDetail = Db::getInstance()->getRow($query);
            
            // TODO : check if we actually got a row ?
            
            // The tax may not be defined for the country (linked to the invoice address)
            // Eg: Switzerland invoice address received in french shop (will depends of PS configuration)
            $tax_rate = $productOrderDetail['tax_rate'] === null ? 0 : $productOrderDetail['tax_rate'];

            // Retrieve the id_order linked to the order_reference (as there might be multiple orders created
            // from the same reference)
            if (!isset($ordersList[(int)$productOrderDetail['id_order']])) {
                // We populate the list of orders with the same reference
                $ordersList[(int)$productOrderDetail['id_order']] = array(
                    'total_products_tax_excl' => 0,
                    'total_products_tax_incl' => 0,
                );
            }
            
            $orderDetailPrice_tax_incl = (float)$apiProduct->getTotalPrice();
            $orderDetailPrice_tax_excl = (float)($orderDetailPrice_tax_incl / (1 + ($tax_rate / 100)));
            
            $ordersList[(int)$productOrderDetail['id_order']]['total_products_tax_incl'] += $orderDetailPrice_tax_incl;
            $ordersList[(int)$productOrderDetail['id_order']]['total_products_tax_excl'] += $orderDetailPrice_tax_excl;
            
            
            $original_product_price = Product::getPriceStatic(
                $psProduct->id,
                false,
                $psProduct->id_product_attribute,
                6
            );
            $updateOrderDetail = array(
                'product_price'        => (float)((float)$apiProduct->getUnitPrice() / (1 + ($tax_rate / 100))),
                'reduction_percent'    => 0,
                'reduction_amount'     => 0,
                'ecotax'               => 0,
                'total_price_tax_incl' => $orderDetailPrice_tax_incl,
                'total_price_tax_excl' => $orderDetailPrice_tax_excl,
                'unit_price_tax_incl'  => (float)$apiProduct->getUnitPrice(),
                'unit_price_tax_excl'  => (float)((float)$apiProduct->getUnitPrice() / (1 + ($tax_rate / 100))),
                'original_product_price' => $original_product_price,
            );
            Db::getInstance()->update(
                'order_detail',
                $updateOrderDetail,
                '`id_order` = '.(int)$productOrderDetail['id_order'] .
                    ' AND `product_id` = ' . (int)$psProduct->id .
                    ' AND `product_attribute_id` = ' . (int)$psProduct->id_product_attribute
            );
            
            if ($tax_rate > 0) {
                $updateOrderDetailTax = array(
                    'unit_amount'  => Tools::ps_round((float)((float)$apiProduct->getUnitPrice() - ((float)$apiProduct->getUnitPrice() / (1 + ($tax_rate / 100)))), 2),
                    'total_amount' => Tools::ps_round((float)(((float)$apiProduct->getUnitPrice() - ((float)$apiProduct->getUnitPrice() / (1 + ($tax_rate / 100)))) * $apiProduct->getQuantity()), 2),
                );
                Db::getInstance()->update(
                    'order_detail_tax',
                    $updateOrderDetailTax,
                    '`id_order_detail` = ' . (int)$productOrderDetail['id_order_detail']
                );
            } else {
                // delete tax so that it does not appear in tax details block in invoice
                Db::getInstance()->delete(
                    'order_detail_tax',
                    '`id_order_detail` = ' . (int)$productOrderDetail['id_order_detail']
                );
            }
        }
        
        $carrier = $this->conveyor['carrier'];
        $paymentInformation = $apiOrder->getPaymentInformation();
        
        // Carrier tax calculation START
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address = new Address($psOrder->id_address_invoice);
        } else {
            $address = new Address($psOrder->id_address_delivery);
        }
        $carrier_tax_rate = $carrier->getTaxesRate($address);
        $total_shipping_tax_excl = Tools::ps_round((float)((float)$paymentInformation['shippingAmount'] / (1 + ($carrier_tax_rate / 100))), 2);
        
        foreach ($ordersList as $id_order => $orderPrices) {
            $total_paid = (float)$orderPrices['total_products_tax_incl'];
            $total_paid_tax_excl = (float)$orderPrices['total_products_tax_excl'];
            // Only on main order
            if ($psOrder->id == (int)$id_order) {
                // main order
                $total_paid = (float)($total_paid + (float)$paymentInformation['shippingAmount']);
                $total_paid_tax_excl = (float)($orderPrices['total_products_tax_excl'] + (float)$total_shipping_tax_excl);
                $orderPrices['total_shipping'] = (float)($paymentInformation['shippingAmount']);
                $orderPrices['total_shipping_tax_incl'] = (float)($paymentInformation['shippingAmount']);
                $orderPrices['total_shipping_tax_excl'] = $total_shipping_tax_excl;
            } else {
                $orderPrices['total_shipping'] = 0;
                $orderPrices['total_shipping_tax_incl'] = 0;
                $orderPrices['total_shipping_tax_excl'] = 0;
            }

            $orderPrices['total_paid'] = (float)$total_paid;
            $orderPrices['total_paid_tax_incl'] = (float)$total_paid;
            $orderPrices['total_paid_tax_excl'] = (float)$total_paid_tax_excl;
        
            $updateOrder = array(
                'total_paid'              => (float)$orderPrices['total_paid'],
                'total_paid_tax_incl'     => (float)$orderPrices['total_paid_tax_incl'],
                'total_paid_tax_excl'     => (float)$orderPrices['total_paid_tax_excl'],
                'total_paid_real'         => (float)$orderPrices['total_paid'],
                'total_products'          => (float)$orderPrices['total_products_tax_excl'],
                'total_products_wt'       => (float)$orderPrices['total_products_tax_incl'],
                'total_shipping'          => (float)$orderPrices['total_shipping'],
                'total_shipping_tax_incl' => (float)$orderPrices['total_shipping_tax_incl'],
                'total_shipping_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
                'carrier_tax_rate'        => $carrier_tax_rate,
                'id_carrier'              => $carrier->id
            );

            $updateOrderInvoice = array(
                'total_paid_tax_incl'     => (float)$orderPrices['total_paid_tax_incl'],
                'total_paid_tax_excl'     => (float)$orderPrices['total_paid_tax_excl'],
                'total_products'          => (float)$orderPrices['total_products_tax_excl'],
                'total_products_wt'       => (float)$orderPrices['total_products_tax_incl'],
                'total_shipping_tax_incl' => (float)$orderPrices['total_shipping_tax_incl'],
                'total_shipping_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
            );

            $updateOrderTracking = array(
                'shipping_cost_tax_incl' => (float)$orderPrices['total_shipping'],
                'shipping_cost_tax_excl' => (float)$orderPrices['total_shipping_tax_excl'],
                'id_carrier' => $carrier->id
            );
        
            Db::getInstance()->update('orders', $updateOrder, '`id_order` = '.(int)$id_order);
            Db::getInstance()->update('order_invoice', $updateOrderInvoice, '`id_order` = '.(int)$id_order);
            Db::getInstance()->update('order_carrier', $updateOrderTracking, '`id_order` = '.(int)$id_order);
        }

        $updatePayment = array('amount' => (float)$paymentInformation['totalAmount']);
        Db::getInstance()->update('order_payment', $updatePayment, '`order_reference` = "'.$this->conveyor['order_reference'].'"');
        
        return true;
    }
    
    /**
     * Retrieves an address using it's alias, and creates or rewrites it
     * 
     * @param array $apiAddress
     * @param int $id_customer
     * @param string $addressAlias
     * @param string $marketPlace TODO unused for now; see old module _getAddress
     * @param string $shippingMethod TODO unused for now; see old module _getAddress
     */
    protected function getOrderAddressId($apiAddress, $id_customer, $addressAlias, $marketPlace, $shippingMethod)
    {
        $addressAlias = Tools::substr($addressAlias, 0, 32);
        
        $addressQuery = new DbQuery();
        $addressQuery->select('id_address')
            ->from('address')
            ->where('id_customer = ' . (int)$id_customer)
            ->where("alias = '" . pSQL($addressAlias) . "'");
        $id_address = Db::getInstance()->getValue($addressQuery);
        
        if ($id_address) {
            $address = new Address((int)$id_address);
        } else {
            $address = new Address();
        }
        
        $address->alias = $addressAlias;
        $address->id_customer = (int)$id_customer;
        $address->firstname = Tools::substr($apiAddress['firstName'], 0, 32);
        $address->lastname = Tools::substr($apiAddress['lastName'], 0, 32);
        $address->company = $apiAddress['company'] ? Tools::substr($apiAddress['company'], 0, 255) : null;
        $address->address1 = Tools::substr($apiAddress['street'], 0, 128);
        $address->address2 = Tools::substr($apiAddress['street2'], 0, 128);
        $address->other = $apiAddress['other'];
        $address->postcode = Tools::substr($apiAddress['postalCode'], 0, 12);
        $address->city = Tools::substr($apiAddress['city'], 0, 64);
        
        // We'll always fill both phone fields
        $address->phone = Tools::substr($apiAddress['phone'], 0, 32);
        $address->phone_mobile = Tools::substr($apiAddress['mobilePhone'], 0, 32);
        
        if (empty($address->phone) && empty($address->phone_mobile)) {
            throw new Exception(sprintf(
                $this->l('Address %s has no phone number.', 'ShoppingfeedOrderImportActions'),
                $addressAlias
            ));
        } elseif (empty($address->phone)) {
            $address->phone = $address->phone_mobile;
        } elseif (empty($address->phone_mobile)) {
            $address->phone_mobile = $address->phone;
        }
        
        // Check if country is valid and active
        $id_country = (int)Country::getByIso($apiAddress['country']);
        if (!$id_country) {
            throw new Exception(sprintf(
                $this->l('Country %s was not found for address %s.', 'ShoppingfeedOrderImportActions'),
                $apiAddress['country'],
                $addressAlias
            ));
        }
        
        $country = new Country($id_country);
        if (!$country->active) {
            throw new Exception(sprintf(
                $this->l('Address %s has country %s, but it is not active.', 'ShoppingfeedOrderImportActions'),
                $addressAlias,
                $apiAddress['country']
            ));
        }
        $address->id_country = $id_country;

        // Update state (needed for US)
        $state_iso_code = Tools::strtoupper(trim($apiAddress['street2']));
        $id_state = State::getIdByIso($state_iso_code, $address->id_country);
        if ($id_state) {
            $address->id_state = $id_state;
        }
        
        try {
            $address->save();
        } catch (Exception $e) {
            throw new Exception(sprintf(
                $this->l('Address %s could not be created : %s', 'ShoppingfeedOrderImportActions'),
                $addressAlias,
                $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
            ));
        }

        return $address->id;
    }
    
    /**
     * Checks available quantity for a given product when using advanced stock
     * management.
     * We'll check if the total quantity is available in a single warehouse,
     * to avoid splitting the order.
     * 
     * @param Product $product
     * @param int $quantityOrdered
     * @return boolean true if the order can be placed; false otherwise
     */
    protected function checkAdvancedStockQty($product, $quantityOrdered)
    {
        if (Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($product->id))) {
            return true;
        }
        
        $stockManager = StockManagerFactory::getManager();
        $productWarehouses = Warehouse::getWarehousesByProductId($product->id, $product->id_product_attribute);

        if (count($productWarehouses) == 0) {
            // No warehouses associated to this product
            return false;
        }
        
        foreach ($productWarehouses as $warehouse) {
            $id_warehouse = (int)$warehouse['id_warehouse'];

            $physicalQuantity = $stockManager->getProductPhysicalQuantities(
                $product->id,
                $product->id_product_attribute,
                $id_warehouse
            );

            if ($physicalQuantity - $quantityOrdered >= 0) {
                return true;
            }
        }

        return false;
    }
}

