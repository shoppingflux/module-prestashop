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

use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedAddon\OrderImport\OrderData;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class ShoppingfeedOrderImportActions extends DefaultActions
{
    /** @var ShoppingfeedOrderImportSpecificRulesManager $specificRulesManager */
    protected $specificRulesManager;

    protected $logPrefix = '';

    public function setLogPrefix($id_internal_shoppingfeed = '', $shoppingfeed_order_reference = '')
    {
        $this->logPrefix = '';
        if ($id_internal_shoppingfeed) {
            $this->logPrefix .= sprintf(
                $this->l('[Order: %s]', 'ShoppingfeedOrderImportActions'),
                $id_internal_shoppingfeed
            );
        }
        if ($shoppingfeed_order_reference) {
            $this->logPrefix .= '[' . $shoppingfeed_order_reference . ']';
        }
        if ($this->logPrefix) {
            $this->logPrefix .= ' ';
        }
    }

    /**
     * Sets generic variables in the action class
     *
     * @param ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder
     */
    protected function initProcess(ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder)
    {
        if (empty($this->conveyor['orderData'])) {
            $this->conveyor['orderData'] = new OrderData($apiOrder);
        }
        $this->setLogPrefix(
            $apiOrder->getId(),
            $apiOrder->getReference()
        );
    }

    public function registerSpecificRules()
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
        $this->initProcess($apiOrder);

        $this->specificRulesManager = new ShoppingfeedAddon\OrderImport\RulesManager($this->getIdShop(), $apiOrder);

        // Specific rule : give a chance to tweak general data before using them
        $this->specificRulesManager->applyRules(
            'onPreProcess',
            array(
                'apiOrder' => $this->conveyor['apiOrder'],
                'orderData' => $this->conveyor['orderData'],
            )
        );

        return true;
    }

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
        $this->initProcess($apiOrder);

        // See old module checkData()

        // Check if order already exists
        if (ShoppingfeedOrder::existsInternalId($apiOrder->getId())) {
            $this->values['error'] = $this->l('Order not imported; already present.', 'ShoppingfeedOrderImportActions');
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
            $this->forward('acknowledgeOrder');

            return false;
        }

        // Check products
        $this->conveyor['prestashopProducts'] = array();
        $sfModule = Module::getInstanceByName('shoppingfeed');
        if (count($this->conveyor['orderData']->items) === 0) {
            ProcessLoggerHandler::logError(
                sprintf(
                    $this->logPrefix .
                        $this->l('No items found on the Shopping feed order.', 'ShoppingfeedOrderImportActions')
                ),
                'Order'
            );
            return false;
        }

        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            if (isset($this->conveyor['orderData']->itemsReferencesAliases) === true
                    && empty($this->conveyor['orderData']->itemsReferencesAliases[$apiProduct->reference]) === false) {

                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Reference alias replace %s by %s.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        $this->conveyor['orderData']->itemsReferencesAliases[$apiProduct->reference]
                    ),
                    'Order'
                );
                $apiProduct->reference = $this->conveyor['orderData']->itemsReferencesAliases[$apiProduct->reference];
            }
            $psProduct = $sfModule->mapPrestashopProduct(
                $apiProduct->reference,
                $this->getIdShop()
            );

            // Does the product exist ?
            if (!Validate::isLoadedObject($psProduct)) {
                $this->values['error'] = sprintf(
                        $this->l('Product reference %s does not match a product on PrestaShop.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference
                    );
                ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                $this->forward('acknowledgeOrder');

                return false;
            }

            // Is the product active ?
            if (!$psProduct->active) {
                $this->values['error'] = sprintf(
                        $this->l('Product %s on PrestaShop is inactive.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    );
                ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                $this->forward('acknowledgeOrder');

                return false;
            }

            // Can the product be ordered ?
            if (!$psProduct->available_for_order) {
                $this->values['error'] = sprintf(
                        $this->l('Product %s on PrestaShop is not available for order.', 'ShoppingfeedOrderImportActions'),
                        $psProduct->reference
                    );
                ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                $this->forward('acknowledgeOrder');

                return false;
            }

            $this->conveyor['prestashopProducts'][$apiProduct->reference] = $psProduct;
        }

        // Check carrier
        $apiOrderShipment = &$this->conveyor['orderData']->shipment;
        $carrier = null;
        $skipSfCarrierCreation = false;

        // Specific rules to get a carrier
        $this->specificRulesManager->applyRules(
            'onCarrierRetrieval',
            array(
                'apiOrder' => $this->conveyor['apiOrder'],
                'apiOrderShipment' => &$apiOrderShipment,
                // Note : if you choose to set a carrier directly, you may want
                // to either set the $apiOrderShipment data accordingly, or skip
                // the ShoppingfeedCarrier creation step
                'carrier' => &$carrier,
                'skipSfCarrierCreation' => &$skipSfCarrierCreation
            )
        );

        $sfCarrier = ShoppingfeedCarrier::getByMarketplaceAndName(
            $apiOrder->getChannel()->getName(),
            $apiOrderShipment['carrier']
        );
        if (Validate::isLoadedObject($sfCarrier) && !Validate::isLoadedObject($carrier)) {
            $carrier = Carrier::getCarrierByReference($sfCarrier->id_carrier_reference);
        }

        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE))) {
            $carrier = Carrier::getCarrierByReference(Configuration::get(Shoppingfeed::ORDER_DEFAULT_CARRIER_REFERENCE));
        }

        if (!Validate::isLoadedObject($carrier) && !empty(Configuration::get('PS_CARRIER_DEFAULT'))) {
            $carrier = new Carrier(Configuration::get('PS_CARRIER_DEFAULT'));
        }

        if (!Validate::isLoadedObject($carrier)) {
            $this->values['error'] =
                $this->l('Could not find a valid carrier for order. Please configure a default carrier on PrestaShop module Shoppingfeed > Parameters > Order feed', 'ShoppingfeedOrderImportActions');
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
            $this->forward('acknowledgeOrder');

            return false;
        }

        $this->conveyor['carrier'] = $carrier;

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 1/11 : Carrier retrieved', 'ShoppingfeedOrderImportActions'),
            'Carrier',
            $carrier->id
        );

        if (!$skipSfCarrierCreation && !Validate::isLoadedObject($sfCarrier)) {
            $sfCarrier = new ShoppingfeedCarrier();
            $sfCarrier->name_marketplace = $apiOrder->getChannel()->getName();
            $sfCarrier->name_carrier = $apiOrderShipment['carrier'];
            $sfCarrier->id_carrier_reference = $carrier->id;
            $sfCarrier->is_new = true;
            try {
                $sfCarrier->save();
            } catch (Exception $e) {
                $errorMessage = $this->l('Could not add a valid carrier on PrestaShop for this order.', 'ShoppingfeedOrderImportActions') . $e->getMessage();
                ProcessLoggerHandler::logError($this->logPrefix . $errorMessage, 'Order');
            }
        }

        // Specific rules validation
        $this->specificRulesManager->applyRules(
            'onVerifyOrder',
            array(
                'apiOrder' => $this->conveyor['apiOrder']
            )
        );

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 2/11 : Order verified', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        return true;
    }

    public function createOrderCart()
    {
        if (empty($this->conveyor['apiOrder'])) {
            $this->values['error'] = $this->l('No apiOrder found', 'ShoppingfeedOrderImportActions');
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
            $this->forward('acknowledgeOrder');

            return false;
        }
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);

        // Try to retrieve customer using the billing address mail
        $apiBillingAddress = &$this->conveyor['orderData']->billingAddress;
        if (Validate::isEmail($apiBillingAddress['email'])) {
            $customerEmail = $apiBillingAddress['email'];
        } else {
            $customerEmail = $apiOrder->getId()
                . '@' . $apiOrder->getChannel()->getName()
                . '.sf';
        }

        // see old module _getCustomer()
        $customer = new Customer();
        $customer->getByEmail($customerEmail);

        // Create customer if it doesn't exist
        if (!Validate::isLoadedObject($customer)) {
            $customer = new Customer();
            $customer->lastname = !empty($apiBillingAddress['lastName']) ? Tools::substr($apiBillingAddress['lastName'], 0, 32) : '-';
            $customer->firstname = !empty($apiBillingAddress['firstName']) ? Tools::substr($apiBillingAddress['firstName'], 0, 32) : '-';
            $customer->passwd = md5(pSQL(_COOKIE_KEY_.rand()));
            $customer->id_default_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $customer->email = $customerEmail;
            $customer->newsletter = 0;
            $customer->id_shop = $this->getIdShop();

            // Numbers are forbidden in firstname / lastname
            $customer->lastname = preg_replace('/\-?\d+/', '', $customer->lastname);
            $customer->firstname = preg_replace('/\-?\d+/', '', $customer->firstname);

            // Specific rules
            $this->specificRulesManager->applyRules(
                'onCustomerCreation',
                array(
                    'apiOrder' => $apiOrder,
                    'customer' => $customer
                )
            );

            try {
                $customer->add();
            } catch (\Exception $e) {
                $msgError = sprintf(
                    $this->l('Fail : %s', 'syncOrder'),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                );
                ProcessLoggerHandler::logError($msgError);
                $this->values['error'] = $msgError;
                $this->forward('acknowledgeOrder');

                return false;
            }

            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Step 3/11 : Customer created', 'ShoppingfeedOrderImportActions'),
                'Customer',
                $customer->id
            );
        } else {
            // Specific rules
            $this->specificRulesManager->applyRules(
                'onCustomerRetrieval',
                array(
                    'apiOrder' => $apiOrder,
                    'customer' => $customer
                )
            );

            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Step 3/11 : Customer retrieved from billing address', 'ShoppingfeedOrderImportActions'),
                'Customer',
                $customer->id
            );
        }
        $this->conveyor['customer'] = $customer;

        // Create or update addresses
        try {
            // Specific rules
            $this->specificRulesManager->applyRules(
                'beforeBillingAddressCreation',
                array(
                    'apiBillingAddress' => &$apiBillingAddress,
                    'apiOrder' => $apiOrder,
                )
            );

            $billingAddress = $this->getOrderAddress(
                $apiBillingAddress,
                $customer,
                'Billing-'.$apiOrder->getId(),
                $this->conveyor['orderData']->shippingAddress
            );

            try {
                // Specific rules
                $this->specificRulesManager->applyRules(
                    'beforeBillingAddressSave',
                    array(
                        'apiBillingAddress' => &$apiBillingAddress,
                        'billingAddress' => $billingAddress,
                        'apiOrder' => $apiOrder,
                    )
                );
                $billingAddress->save();

                $this->conveyor['id_billing_address'] = $billingAddress->id;
            } catch (Exception $e) {
                throw new Exception(sprintf(
                    $this->l('Address %s could not be created : %s', 'ShoppingfeedOrderImportActions'),
                    'Billing-'.$apiOrder->getId(),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ));
            }

            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Step 4/11 : Billing address created / updated', 'ShoppingfeedOrderImportActions'),
                'Address',
                $billingAddress->id
            );

            $apiShippingAddress = &$this->conveyor['orderData']->shippingAddress;

            // Specific rules
            $this->specificRulesManager->applyRules(
                'beforeShippingAddressCreation',
                array(
                    'apiShippingAddress' => &$apiShippingAddress,
                    'apiOrder' => $apiOrder,
                )
            );

            $shippingAddress = $this->getOrderAddress(
                $apiShippingAddress,
                $customer,
                'Shipping-'.$apiOrder->getId(),
                $this->conveyor['orderData']->billingAddress
            );

            try {
                // Specific rules
                $this->specificRulesManager->applyRules(
                    'beforeShippingAddressSave',
                    array(
                        'apiShippingAddress' => &$apiShippingAddress,
                        'shippingAddress' => $shippingAddress,
                        'apiOrder' => $apiOrder,
                    )
                );
                $shippingAddress->save();

                $this->conveyor['id_shipping_address'] = $shippingAddress->id;
            } catch (Exception $e) {
                throw new Exception(sprintf(
                    $this->l('Address %s could not be created : %s', 'ShoppingfeedOrderImportActions'),
                    'Shipping-'.$apiOrder->getId(),
                    $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                ));
            }

            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                    $this->l('Step 5/11 : Shipping address created / updated', 'ShoppingfeedOrderImportActions'),
                'Address',
                $shippingAddress->id
            );
        } catch (Exception $ex) {
            $this->values['error'] = $ex->getMessage();
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
            $this->forward('acknowledgeOrder');

            return false;
        }

        /* Check products quantities
         *
         * Check if there is enough stock associated to the products before
         * creating the order. If the stock is not sufficient, it will be
         * increased just enough to avoid an error during the creation of the
         * order.
         */
        $isAdvancedStockEnabled = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 1 ? true : false;

        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];
            $useAdvancedStock = $isAdvancedStockEnabled && $psProduct->advanced_stock_management;

            $this->specificRulesManager->applyRules(
                'checkProductStock',
                array(
                    'id_shop' => $this->getIdShop(),
                    'apiOrder' => $apiOrder,
                    'orderData' => &$this->conveyor['orderData'],
                    'psProduct' => $psProduct,
                    'apiProduct' => &$apiProduct,
                )
            );

            // If the product uses advanced stock management
            if ($useAdvancedStock) {
                // If there's not enough stock to place the order
                if (!$this->checkAdvancedStockQty($psProduct, $apiProduct->quantity)) {
                    $this->values['error'] = sprintf(
                                    $this->l('Not enough stock for product %s.', 'ShoppingfeedOrderImportActions'),
                                    $apiProduct->reference
                                );
                    ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                    $this->forward('acknowledgeOrder');

                    return false;
                }
                continue;
            }

            // If the product doesn't use advanced stock management
            // If there's not enough stock to place the order
            if (!$psProduct->checkQty($apiProduct->quantity)) {
                // Add just enough stock
                $currentStock = StockAvailable::getQuantityAvailableByProduct(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $this->getIdShop()
                );
                $stockToAdd = (int)$apiProduct->quantity - (int)$currentStock;
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Not enough stock for product %s: original %d, required %d, add %d.', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        (int)$currentStock,
                        (int)$apiProduct->quantity,
                        (int)$stockToAdd
                    ),
                    'Order'
                );
                StockAvailable::updateQuantity(
                    $psProduct->id,
                    $psProduct->id_product_attribute,
                    $stockToAdd,
                    $this->getIdShop()
                );
            }
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 6/11 : Products quantities updated / validated.', 'ShoppingfeedOrderImportActions'),
            'Order'
        );

        // See old module _getCart()
        $paymentInformation = &$this->conveyor['orderData']->payment;

        // Create cart
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_address_invoice = $billingAddress->id;
        $cart->id_address_delivery = $shippingAddress->id;
        $cart->id_currency = Currency::getIdByIsoCode(
            (string)$paymentInformation['currency'] == '' ?
                'EUR' : (string)$paymentInformation['currency']
        );
        $cart->id_lang = Configuration::get('PS_LANG_DEFAULT');

        $cart->recyclable = 0;
        $cart->secure_key = md5(uniqid(rand(), true));

        $cart->id_carrier = $this->conveyor['carrier']->id;
        $cart->id_shop = $this->getIdShop();
        if (version_compare(_PS_VERSION_, '1.7.2.5', '>=')) {
            $cart->delivery_option = Tools::jsonEncode([$cart->id_address_delivery => $cart->id_carrier.',']);
        } else {
            $cart->delivery_option = @serialize([$cart->id_address_delivery => $cart->id_carrier.',']);
        }
        $cart->add();

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 7/11 : Cart created', 'ShoppingfeedOrderImportActions'),
            'Cart',
            $cart->id
        );

        // Add products to cart
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];
            try {
                $addToCartResult = $cart->updateQty(
                    $apiProduct->quantity,
                    $psProduct->id,
                    $psProduct->id_product_attribute
                );
            } catch (Exception $e) {
                $this->values['error'] = sprintf(
                        $this->l('Could not add product %s to cart : %s', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
                    );
                ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                $this->forward('acknowledgeOrder');

                return false;
            }

            if ($addToCartResult < 0 || $addToCartResult === false) {
                $this->values['error'] = sprintf(
                        $this->l('Could not add product %s to cart : %s', 'ShoppingfeedOrderImportActions'),
                        $apiProduct->reference,
                        $cart->id
                    );
                ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
                $this->forward('acknowledgeOrder');

                return false;
            }
        }

        $this->specificRulesManager->applyRules(
            'onCartCreation',
            array(
                'apiOrder' => $apiOrder,
                'orderData' => &$this->conveyor['orderData'],
                'cart' => &$cart,
            )
        );

        $cart->update();
        $this->conveyor['cart'] = $cart;

        $this->specificRulesManager->applyRules(
            'afterCartCreation',
            array(
                'apiOrder' => $apiOrder,
                'orderData' => &$this->conveyor['orderData'],
                'cart' => &$cart,
            )
        );

        if ($cart->nbProducts() === 0) {
            $this->values['error'] = sprintf(
                    $this->l('Could not add product to cart : %s', 'ShoppingfeedOrderImportActions'),
                    $cart->id
                );
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
            $this->forward('acknowledgeOrder');

            return false;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 8/11 : Products added to cart.', 'ShoppingfeedOrderImportActions'),
            'Cart',
            $cart->id
        );

        return true;
    }

    public function validateOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        /** @var Cart $apiOrder */
        $cart = $this->conveyor['cart'];

        $this->initProcess($apiOrder);

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
                    $this->logPrefix .
                        $this->l('Current context country %d is not active.', 'ShoppingfeedOrderImportActions'),
                    Context::getContext()->country->id
                ),
                'Country',
                Context::getContext()->country->id
            );
            $addressDelivery = new Address($cart->id_address_delivery);
            if (Validate::isLoadedObject($addressDelivery)) {
                Context::getContext()->country = new Country($addressDelivery->id_country);
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $this->logPrefix .
                            $this->l('Context country set to %d', 'ShoppingfeedOrderImportActions'),
                        $addressDelivery->id_country
                    ),
                    'Country',
                    Context::getContext()->country->id
                );
            }
        }

        // Validate order with payment module
        // Change the customer mail before this, otherwise he'll get a confirmation mail
        $customerEmail = $this->conveyor['customer']->email;
        $this->conveyor['customer']->email = 'do-not-send@alerts-shopping-flux.com';
        $this->conveyor['customer']->update();

        $amount_paid = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);
        try {
            $paymentModule->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                $amount_paid,
                Tools::strtolower($apiOrder->getChannel()->getName()),
                null,
                array(),
                $cart->id_currency,
                false,
                $cart->secure_key,
                new Shop($this->getIdShop())
            );
        } catch (Exception $e) {
            if (false === is_int($paymentModule->currentOrder) || $paymentModule->currentOrder === 0) {
                $order = $this->getOrderByCartId((int)$cart->id);

                if (Validate::isLoadedObject($order)) {
                    $paymentModule->currentOrder = $order->id;
                    $paymentModule->currentOrderReference = $order->reference;
                }
            }

            $log = [
                'Message: ' . $e->getMessage(),
                'File: ' . $e->getFile(),
                'Line: ' . $e->getLine(),
                'Error type: ' . get_class($e)
            ];
            $message = implode(';', $log);
            $this->values['error'] = $this->l('Order not valid on PrestaShop.', 'ShoppingfeedOrderImportActions') . ' ' .$message;
            ProcessLoggerHandler::logError($this->logPrefix . $this->values['error'], 'Order');
        }

        if ($paymentModule->currentOrder && $paymentModule->currentOrderReference) {
            $this->conveyor['id_order'] = $paymentModule->currentOrder;
            $this->conveyor['order_reference'] = $paymentModule->currentOrderReference;
        } else {
            return true;
        }

        // Reset customer mail
        $this->conveyor['customer']->email = $customerEmail;
        $this->conveyor['customer']->update();

        // Create the ShoppingfeedOrder here; we need to know if it's been created
        // after this point
        $sfOrder = new ShoppingfeedOrder();
        $sfOrder->id_order = $this->conveyor['id_order'];
        $sfOrder->id_internal_shoppingfeed = (string)$apiOrder->getId();
        $sfOrder->id_order_marketplace = $apiOrder->getReference();
        $sfOrder->name_marketplace = $apiOrder->getChannel()->getName();
        $sfOrder->id_shoppingfeed_token = (int)$this->conveyor['id_token'];

        $paymentInformation = $this->conveyor['orderData']->payment;
        $sfOrder->payment_method = !empty($paymentInformation['method']) ? $paymentInformation['method'] : '-';

        if ($this->conveyor['orderData']->createdAt->getTimestamp() != 0) {
            $sfOrder->date_marketplace_creation = $this->conveyor['orderData']->createdAt->format('Y-m-d H:i:s');
        }

        $sfOrder->save();
        $this->conveyor['sfOrder'] = $sfOrder;

        // Specific rules
        $this->specificRulesManager->applyRules(
            'afterOrderCreation',
            array(
                'apiOrder' => $apiOrder,
                'id_order' => $this->conveyor['id_order'],
                'order_reference' => $this->conveyor['order_reference']
            )
        );

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 9/11 : Order validated.', 'ShoppingfeedOrderImportActions'),
            'Order',
            $this->conveyor['id_order']
        );

        return true;
    }

    /**
     * We'll assume that this action should not prevent the following actions
     * from executing
     */
    public function acknowledgeOrder()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $this->initProcess($apiOrder);

        try {
            $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_token']);
        } catch (Exception $e) {
            $shoppingfeedApi = false;
        }

        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                empty($this->conveyor['sfOrder']) === false ? $this->conveyor['sfOrder']->id_order : ''
            );
            return true;
        }

        $isSucess = array_key_exists('id_order', $this->conveyor);

        try {
            $result = $shoppingfeedApi->acknowledgeOrder(
                $apiOrder->getReference(),
                $apiOrder->getChannel()->getName(),
                $isSucess ? $this->conveyor['id_order'] : null,
                $isSucess,
                empty($this->values['error']) ? null : $this->values['error']
            );
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                sprintf(
                    $this->l('An error while to acknowledge order. Error Message: %s.', 'ShoppingfeedOrderSyncActions'),
                    $e->getMessage()
                ),
                'Order',
                empty($this->conveyor['sfOrder']) === false ? $this->conveyor['sfOrder']->id_order : ''
            );

            return true;
        }

        if (!$result || !iterator_count($result->getTickets())) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                    $this->l('Failed to acknowledge order on Shoppingfeed API.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                empty($this->conveyor['sfOrder']) === false ? $this->conveyor['sfOrder']->id_order : ''
            );
            return true;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 10/11 : Order acknowledged with SF API.', 'ShoppingfeedOrderImportActions'),
            'Order',
            empty($this->conveyor['sfOrder']) === false ? $this->conveyor['sfOrder']->id_order : ''
        );

        return true;
    }

    public function recalculateOrderPrices()
    {
        /** @var ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder */
        $apiOrder = $this->conveyor['apiOrder'];
        $psOrder = new Order((int)$this->conveyor['id_order']);

        $this->initProcess($apiOrder);

        // We may have multiple orders created for the same reference, (advanced stock management)
        // therefore the total price of each orders needs to be calculated separately
        $ordersList = array();

        // See old module _updatePrices()
        /** @var ShoppingfeedAddon\OrderImport\OrderItemData $apiProduct */
        foreach ($this->conveyor['orderData']->items as &$apiProduct) {
            /** @var Product $psProduct */
            $psProduct = $this->conveyor['prestashopProducts'][$apiProduct->reference];

            $query = new DbQuery();
            $query->select('tax.rate AS tax_rate, od.id_order_detail, od.id_order')
                ->from('order_detail', 'od')
                ->leftJoin('orders', 'o', 'o.id_order = od.id_order')
                ->leftJoin('order_detail_tax', 'odt', 'odt.id_order_detail = od.id_order_detail')
                ->leftJoin('tax', 'tax', 'tax.id_tax = odt.id_tax')
                ->where('o.id_order = ' . (int)$this->conveyor['id_order'])
                ->where('product_id = ' . (int)$psProduct->id)
            ;
            if ($psProduct->id_product_attribute) {
                $query->where('product_attribute_id = ' . (int)$psProduct->id_product_attribute);
            }
            $productOrderDetail = Db::getInstance()->getRow($query);

            if (!$productOrderDetail) {
                ProcessLoggerHandler::logError(
                    $this->logPrefix .
                        $this->l('Failed to get OrderDetail object.', 'ShoppingfeedOrderImportActions'),
                    'Product',
                    $psProduct->id
                );
                continue;
            }

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
                'product_price'        => (float)((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100))),
                'reduction_percent'    => 0,
                'reduction_amount'     => 0,
                'ecotax'               => 0,
                'total_price_tax_incl' => $orderDetailPrice_tax_incl,
                'total_price_tax_excl' => $orderDetailPrice_tax_excl,
                'unit_price_tax_incl'  => (float)$apiProduct->unitPrice,
                'unit_price_tax_excl'  => (float)((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100))),
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
                    'unit_amount'  => Tools::ps_round((float)((float)$apiProduct->unitPrice - ((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100)))), 2),
                    'total_amount' => Tools::ps_round((float)(((float)$apiProduct->unitPrice - ((float)$apiProduct->unitPrice / (1 + ($tax_rate / 100)))) * $apiProduct->quantity), 2),
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
        $paymentInformation = &$this->conveyor['orderData']->payment;

        // Carrier tax calculation START
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address = new Address($psOrder->id_address_invoice);
        } else {
            $address = new Address($psOrder->id_address_delivery);
        }
        $carrier_tax_rate = $carrier->getTaxesRate($address);
        $total_shipping_tax_excl = Tools::ps_round((float) $paymentInformation['shippingAmount'] / (1 + ($carrier_tax_rate / 100)), 2);

        foreach ($ordersList as $id_order => $orderPrices) {
            $total_paid = Tools::ps_round($orderPrices['total_products_tax_incl'], 2);
            $total_paid_tax_excl = Tools::ps_round($orderPrices['total_products_tax_excl'], 4);
            // Only on main order
            if ($psOrder->id == (int)$id_order) {
                // main order
                $total_paid = Tools::ps_round($total_paid + (float) $paymentInformation['shippingAmount'], 2);
                $total_paid_tax_excl = Tools::ps_round($orderPrices['total_products_tax_excl'] + (float)$total_shipping_tax_excl, 4);
                $orderPrices['total_shipping'] = Tools::ps_round($paymentInformation['shippingAmount'], 2);
                $orderPrices['total_shipping_tax_incl'] = Tools::ps_round($paymentInformation['shippingAmount'], 2);
                $orderPrices['total_shipping_tax_excl'] = $total_shipping_tax_excl;
            } else {
                $orderPrices['total_shipping'] = 0;
                $orderPrices['total_shipping_tax_incl'] = 0;
                $orderPrices['total_shipping_tax_excl'] = 0;
            }

            $orderPrices['total_paid'] = Tools::ps_round($total_paid, 2);
            $orderPrices['total_paid_tax_incl'] = Tools::ps_round($total_paid, 2);
            $orderPrices['total_paid_tax_excl'] = Tools::ps_round($total_paid_tax_excl, 4);

            $updateOrder = array(
                'total_paid'              => Tools::ps_round($orderPrices['total_paid'], 2),
                'total_paid_tax_incl'     => Tools::ps_round($orderPrices['total_paid_tax_incl'], 2),
                'total_paid_tax_excl'     => Tools::ps_round($orderPrices['total_paid_tax_excl'], 4),
                'total_paid_real'         => Tools::ps_round($orderPrices['total_paid'], 2),
                'total_products'          => Tools::ps_round($orderPrices['total_products_tax_excl'], 2),
                'total_products_wt'       => Tools::ps_round($orderPrices['total_products_tax_incl'], 4),
                'total_shipping'          => Tools::ps_round($orderPrices['total_shipping'], 2),
                'total_shipping_tax_incl' => Tools::ps_round($orderPrices['total_shipping_tax_incl'], 4),
                'total_shipping_tax_excl' => Tools::ps_round($orderPrices['total_shipping_tax_excl'], 4),
                'carrier_tax_rate'        => $carrier_tax_rate,
                'id_carrier'              => $carrier->id
            );

            $updateOrderInvoice = array(
                'total_paid_tax_incl'     => Tools::ps_round($orderPrices['total_paid_tax_incl'], 2),
                'total_paid_tax_excl'     => Tools::ps_round($orderPrices['total_paid_tax_excl'], 4),
                'total_products'          => Tools::ps_round($orderPrices['total_products_tax_excl'], 4),
                'total_products_wt'       => Tools::ps_round($orderPrices['total_products_tax_incl'], 2),
                'total_shipping_tax_incl' => Tools::ps_round($orderPrices['total_shipping_tax_incl'], 2),
                'total_shipping_tax_excl' => Tools::ps_round($orderPrices['total_shipping_tax_excl'], 4),
            );

            $updateOrderTracking = array(
                'shipping_cost_tax_incl' => Tools::ps_round($orderPrices['total_shipping'], 2),
                'shipping_cost_tax_excl' => Tools::ps_round($orderPrices['total_shipping_tax_excl'], 4),
                'id_carrier' => $carrier->id
            );

            foreach ($updateOrder as $key => $value) {
                $psOrder->{$key} = $value;
            }
            $psOrder->save(true);
            Db::getInstance()->update('order_invoice', $updateOrderInvoice, '`id_order` = '.(int)$id_order);
            Db::getInstance()->update('order_carrier', $updateOrderTracking, '`id_order` = '.(int)$id_order);
        }

        $updatePayment = array('amount' => Tools::ps_round($paymentInformation['totalAmount'], 4));
        Db::getInstance()->update('order_payment', $updatePayment, '`order_reference` = "'.pSQL($this->conveyor['order_reference']).'"');
        Cache::clean('order_invoice_paid_*');

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
                $this->l('Step 11/11 : Order prices updated.', 'ShoppingfeedOrderImportActions'),
            'Order',
            $this->conveyor['id_order']
        );

        return true;
    }

    public function postProcess()
    {
        // Specific rule : after the process is complete
        $this->specificRulesManager->applyRules(
            'onPostProcess',
            array(
                'apiOrder' => $this->conveyor['apiOrder'],
                'orderData' => $this->conveyor['orderData'],
                'sfOrder' => $this->conveyor['sfOrder'],
            )
        );

        return true;
    }

    /**
     * Retrieves an address using it's alias, and creates or rewrites it
     *
     * @param array $apiAddress
     * @param \Customer $customer
     * @param string $addressAlias
     */
    protected function getOrderAddress($apiAddress, $customer, $addressAlias, $otherAddress = null)
    {
        $addressAlias = Tools::substr($addressAlias, 0, 32);

        $addressQuery = new DbQuery();
        $addressQuery->select('id_address')
            ->from('address')
            ->where('id_customer = ' . (int)$customer->id)
            ->where("alias = '" . pSQL($addressAlias) . "'");
        $id_address = Db::getInstance()->getValue($addressQuery);

        if ($id_address) {
            $address = new Address((int)$id_address);
        } else {
            $address = new Address();
        }

        $address->alias = $addressAlias;
        $address->id_customer = (int)$customer->id;
        $address->firstname = Tools::substr(
            !empty($apiAddress['firstName']) ? $apiAddress['firstName'] : $customer->firstname,
            0,
            32
        );
        $address->lastname = Tools::substr(
            !empty($apiAddress['lastName']) ? $apiAddress['lastName'] : $customer->lastname,
            0,
            32
        );
        $address->company = !empty($apiAddress['company']) ? Tools::substr($apiAddress['company'], 0, 255) : null;
        $address->address1 = Tools::substr($apiAddress['street'], 0, 128);
        $address->address2 = Tools::substr($apiAddress['street2'], 0, 128);
        $address->other = $apiAddress['other'];
        $address->postcode = Tools::substr($apiAddress['postalCode'], 0, 12);
        $address->city = Tools::substr($apiAddress['city'], 0, 64);

        // Numbers are forbidden in firstname / lastname
        $address->lastname = preg_replace('/\-?\d+/', '', $address->lastname);
        $address->firstname = preg_replace('/\-?\d+/', '', $address->firstname);

        // We'll always fill both phone fields
        $address->phone = Tools::substr($apiAddress['phone'], 0, 32);
        $address->phone_mobile = Tools::substr($apiAddress['mobilePhone'], 0, 32);

        // get phone on an other address
        if (empty($address->phone) && empty($address->phone_mobile)) {
            if (empty($otherAddress) !== false && empty($otherAddress->phone) !== false) {
                $address->phone = $otherAddress->phone;
            }
            if (empty($otherAddress) !== false && empty($otherAddress->phone_mobile) !== false) {
                $address->phone_mobile = $otherAddress->phone_mobile;
            }
        }
        if (empty($address->phone) && empty($address->phone_mobile)) {
            $address->phone = '0102030405';
        } elseif (empty($address->phone)) {
            $address->phone = $address->phone_mobile;
        } elseif (empty($address->phone_mobile)) {
            $address->phone_mobile = $address->phone;
        }

        // Check if country is valid and active
        if (!Validate::isLanguageIsoCode($apiAddress['country'])) {
            throw new Exception(sprintf(
                $this->l('Country ISO code %s is not valid for address %s.', 'ShoppingfeedOrderImportActions'),
                $apiAddress['country'],
                $addressAlias
            ));
        }
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

        return $address;
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

    /**
     * @param $idCart int
     * @return Order
     */
    protected function getOrderByCartId($idCart)
    {
        if (version_compare(_PS_VERSION_, '1.7.1', '<')) {
            return new Order(Order::getOrderByCartId((int)$idCart));
        } else {
            return new Order(Order::getIdByCartId((int)$idCart));
        }
    }

    /**
     * @return int
     */
    protected function getIdShop()
    {
        if (isset($this->conveyor['id_shop']) && (int)$this->conveyor['id_shop']) {
            return (int) $this->conveyor['id_shop'];
        }

        return (int) Configuration::get('PS_SHOP_DEFAULT');
    }
}
