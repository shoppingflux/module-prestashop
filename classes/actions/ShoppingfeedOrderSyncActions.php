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

use ShoppingfeedAddon\Services\CarrierFinder;
use ShoppingfeedAddon\Services\TaskOrderCleaner;
use ShoppingfeedClasslib\Actions\DefaultActions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Registry;

class ShoppingfeedOrderSyncActions extends DefaultActions
{
    public static function getLogPrefix($id_order = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Order: %s]', 'ShoppingfeedOrderSyncActions'),
            $id_order
        );
    }

    public function saveOrder()
    {
        if (empty($this->conveyor['id_order'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('SF Order not imported; no ID order found', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $id_order = $this->conveyor['id_order'];
        $logPrefix = static::getLogPrefix($id_order);
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('SF Order not imported; Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );

            return false;
        }

        $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($id_order);
        if (Validate::isLoadedObject($shoppingfeedOrder)) {
            return true;
        }

        $newShoppingFeedOrder = new ShoppingfeedOrder();
        $newShoppingFeedOrder->name_marketplace = $order->payment;
        $newShoppingFeedOrder->id_order = $id_order;
        $newShoppingFeedOrder->payment_method = '-';
        $newShoppingFeedOrder->id_shoppingfeed_token = '1';
        $newShoppingFeedOrder->save();

        ProcessLoggerHandler::logSuccess(
            $logPrefix . ' ' .
                $this->l('SF Order successfully saved', 'ShoppingfeedOrderSyncActions'),
            'Order',
            $id_order
        );

        return true;
    }

    public function saveTaskOrder()
    {
        if (empty($this->conveyor['id_order'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('Order not registered for synchronization; no ID order found', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $id_order = $this->conveyor['id_order'];
        $logPrefix = static::getLogPrefix($id_order);
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );

            return false;
        }

        if (empty($this->conveyor['order_action'])) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; no Action found', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );

            return false;
        }
        $action = $this->conveyor['order_action'];

        // Before saving a task, we need to check if the SF order reference has
        // been retrieved
        $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($id_order);
        if (!Validate::isLoadedObject($shoppingfeedOrder)) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Order not registered for synchronization; SF Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );

            return false;
        }
        if (!$shoppingfeedOrder->setReferenceFromOrder()) {
            ProcessLoggerHandler::logError(
                $logPrefix . ' ' .
                    $this->l('Could not retrieve SF Order reference.', 'ShoppingfeedOrderSyncActions'),
                'Order',
                $id_order
            );

            return false;
        }

        // Check if task order already exists
        $shoppingfeedTaskOrder = ShoppingfeedTaskOrder::getFromOrderAction(
            $id_order,
            $action
        );

        if (false === $shoppingfeedTaskOrder || !Validate::isLoadedObject($shoppingfeedTaskOrder)) {
            $shoppingfeedTaskOrder = new ShoppingfeedTaskOrder();
            $shoppingfeedTaskOrder->action = $action;
            $shoppingfeedTaskOrder->id_order = (int) $id_order;
        }

        // When setting a status to "shipped", the synchronization must be
        // delayed to give the merchant time to fill the tracking number
        $updateAt = time();
        $shippedStatuses = json_decode(Configuration::get(Shoppingfeed::SHIPPED_ORDERS));
        if (in_array($order->current_state, $shippedStatuses)) {
            $updateAt += (60 * Configuration::get(Shoppingfeed::ORDER_STATUS_TIME_SHIFT));
        }

        // Save the task order
        $shoppingfeedTaskOrder->update_at = date('Y-m-d H:i:s', $updateAt);
        $shoppingfeedTaskOrder->save();

        ProcessLoggerHandler::logSuccess(
            sprintf(
                $logPrefix . ' ' .
                    $this->l('Order registered for synchronization; action %s', 'ShoppingfeedOrderSyncActions'),
                $action
            ),
            'Order',
            $id_order
        );

        return true;
    }

    public function getTaskOrders()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $id_shop = (int) $this->conveyor['id_shop'];
        // Remove old tasks
        $this->initTaskCleaner()->clean();

        if (empty($this->conveyor['order_action'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('Could not retrieve Task Orders; no order action found', 'ShoppingfeedProductSyncActions'),
                'Order'
            );

            return false;
        }
        $action = $this->conveyor['order_action'];

        $query = new DbQuery();
        $query->select('sto.*')
            ->from('shoppingfeed_task_order', 'sto')
            ->leftJoin('shoppingfeed_order', 'so', 'so.id_order = sto.id_order')
            ->innerJoin('orders', 'o', 'o.id_order = sto.id_order')
            ->where('action = "' . pSQL($action) . '"')
            ->where('update_at IS NOT NULL')
            ->where('update_at <= "' . pSQL(date('Y-m-d H:i:s')) . '"')
            ->where('id_shop = ' . (int) $id_shop)
            ->where('so.id_shoppingfeed_token IS NULL OR so.id_shoppingfeed_token = 0 OR so.id_shoppingfeed_token = ' . (int) $this->conveyor['id_token'])
            ->orderBy('sto.date_upd ASC')
            ->limit(100);
        $taskOrdersData = Db::getInstance()->executeS($query);

        if (empty($taskOrdersData)) {
            ProcessLoggerHandler::logInfo(
                $this->l('Could not retrieve Task Orders.', 'ShoppingfeedProductSyncActions'),
                'Order'
            );

            return false;
        }

        $this->conveyor['taskOrders'] = [];
        foreach ($taskOrdersData as $taskOrderData) {
            $taskOrder = new ShoppingfeedTaskOrder();
            $taskOrder->hydrate($taskOrderData);
            $this->conveyor['taskOrders'][] = $taskOrder;
        }

        return true;
    }

    public function prepareTaskOrdersSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $id_shop = (int) $this->conveyor['id_shop'];

        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('No Task Orders to prepare.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $taskOrders = $this->conveyor['taskOrders'];

        $shipped_status = json_decode(Configuration::get(Shoppingfeed::SHIPPED_ORDERS, null, null, $id_shop), true);
        $cancelled_status = json_decode(Configuration::get(Shoppingfeed::CANCELLED_ORDERS, null, null, $id_shop), true);
        $refunded_status = json_decode(Configuration::get(Shoppingfeed::REFUNDED_ORDERS, null, null, $id_shop), true);
        $delivered_status = json_decode(Configuration::get(Shoppingfeed::DELIVERED_ORDERS, null, null, $id_shop), true);

        $this->conveyor['preparedTaskOrders'] = [];
        foreach ($taskOrders as $taskOrder) {
            /** @var $taskOrder ShoppingfeedTaskOrder */
            $logPrefix = self::getLogPrefix($taskOrder->id_order);
            $order = new Order($taskOrder->id_order);

            if (!Validate::isLoadedObject($order)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            $sfOrder = ShoppingfeedOrder::getByIdOrder($taskOrder->id_order);
            if (empty($sfOrder->id_order_marketplace)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('No SF Order reference set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            if (empty($sfOrder->name_marketplace)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('No SF Order marketplace set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('syncStatusErrors');
                continue;
            }
            // Should reverse the history to set operation is based on a more recent order state
            $orderHistory = array_reverse($order->getHistory($order->id_lang));
            $taskOrderOperation = null;
            $taskOrderPayload = [];

            foreach ($orderHistory as $state) {
                $idOrderState = (int) $state['id_order_state'];
                if (in_array($idOrderState, $shipped_status)) {
                    $taskOrderOperation = Shoppingfeed::ORDER_OPERATION_SHIP;

                    // Default values...
                    $taskOrderPayload = [
                        'carrier_name' => '',
                        'tracking_number' => '',
                        'tracking_url' => '',
                        'items' => [],
                        'return_info' => null,
                        'warehouse_id' => null,
                    ];

                    $carrier = $this->initCarrierFinder()->findByOrder($order);
                    if (!Validate::isLoadedObject($carrier)) {
                        ProcessLoggerHandler::logError(
                            $logPrefix . ' ' .
                            $this->l('Carrier could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                            'Order',
                            $taskOrder->id_order
                        );
                    }

                    // We don't support multi-shipping; use the first shipping method
                    $orderShipping = $order->getShipping();
                    if (!empty($orderShipping[0])) {
                        $trackingNumber = $orderShipping[0]['tracking_number'];
                        // From the old module. Not sure if it's all useful, but
                        // we'll suppose it knows better.
                        // Build the tracking url.
                        $orderTrackingUrl = str_replace('http://http://', 'http://', $carrier->url);
                        $orderTrackingUrl = str_replace('@', $trackingNumber, $orderTrackingUrl);

                        $taskOrderPayload = [
                            // "state_name" is indeed the carrier's name...
                            'carrier_name' => $orderShipping[0]['state_name'],
                            'tracking_number' => $trackingNumber,
                            'tracking_url' => $orderTrackingUrl,
                            'items' => [],
                            'return_info' => null,
                            'warehouse_id' => null,
                        ];
                    }

                    Hook::exec('actionShoppingfeedTracking', ['order' => $order, 'taskOrderPayload' => &$taskOrderPayload]);
                    continue;
                } elseif (in_array($idOrderState, $cancelled_status)) {
                    $taskOrderOperation = Shoppingfeed::ORDER_OPERATION_CANCEL;
                    $taskOrderPayload = [
                        'reason' => '',
                    ];
                    continue;
                // The "reason" field is not supported (at least for now)
                } elseif (in_array($idOrderState, $refunded_status)) {
                    $taskOrderOperation = Shoppingfeed::ORDER_OPERATION_REFUND;
                    $taskOrderPayload = [
                        'shipping' => true,
                        'products' => [],
                    ];
                    continue;
                // No partial refund (at least for now), so no optional
                // parameters to set.
                } elseif (in_array($idOrderState, $delivered_status)) {
                    $taskOrderOperation = Shoppingfeed::ORDER_OPERATION_DELIVER;
                }
            }

            if (is_null($taskOrderOperation)) {
                ProcessLoggerHandler::logInfo(
                    sprintf(
                        $logPrefix . ' ' .
                        $this->l('No matching operation for Order State %d. Deleting Task Order.', 'ShoppingfeedOrderSyncActions'),
                        $order->current_state
                    ),
                    'Order'
                );
                $taskOrder->delete();
                continue;
            }

            $this->conveyor['preparedTaskOrders'][$taskOrderOperation][] = [
                'id_internal_shoppingfeed' => $sfOrder->id_internal_shoppingfeed,
                'reference_marketplace' => $sfOrder->id_order_marketplace,
                'marketplace' => $sfOrder->name_marketplace,
                'taskOrder' => $taskOrder,
                'operation' => $taskOrderOperation,
                'payload' => $taskOrderPayload,
            ];
        }

        return true;
    }

    public function prepareTaskOrdersSyncInvoice()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('No Task Orders to prepare.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        $taskOrders = $this->conveyor['taskOrders'];
        $this->conveyor['preparedTaskOrders'] = [];

        foreach ($taskOrders as $taskOrder) {
            /** @var $taskOrder ShoppingfeedTaskOrder */
            $logPrefix = self::getLogPrefix($taskOrder->id_order);
            $order = new Order($taskOrder->id_order);
            $sfOrder = ShoppingfeedOrder::getByIdOrder($taskOrder->id_order);

            if (!Validate::isLoadedObject($order)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                    $this->l('Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            if (empty($sfOrder->id_internal_shoppingfeed)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                    $this->l('No SF Order id set.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            foreach ($order->getInvoicesCollection() as $invoice) {
                $pdf = new PDF($invoice, PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);
                $cacheStorage = new ShoppingfeedClasslib\Utils\CacheStorage\CacheStorage();
                $file = $cacheStorage->getDirectory() . $invoice->id . '.pdf';
                $isInvoiceExist = file_put_contents($file, $pdf->render(false));

                if ($isInvoiceExist) {
                    $this->conveyor['preparedTaskOrders'][Shoppingfeed::ORDER_OPERATION_UPLOAD_DOCUMENTS][] = [
                        'id_internal_shoppingfeed' => $sfOrder->id_internal_shoppingfeed,
                        'taskOrder' => $taskOrder,
                        'file' => $file,
                        'operation' => Shoppingfeed::ORDER_OPERATION_UPLOAD_DOCUMENTS,
                        'payload' => [
                            'uri' => $file,
                        ],
                    ];
                } else {
                    ProcessLoggerHandler::logError(
                        $logPrefix . ' ' .
                        $this->l('Generation invoice is failed', 'ShoppingfeedOrderSyncActions'),
                        'Order',
                        $taskOrder->id_order
                    );
                    Registry::increment('syncStatusErrors');
                }
            }
        }

        return true;
    }

    protected function initCarrierFinder()
    {
        return new CarrierFinder();
    }

    public function sendTaskOrdersSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        if (empty($this->conveyor['preparedTaskOrders'])) {
            ProcessLoggerHandler::logError(
                $this->l('No prepared Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_token']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        foreach ($this->conveyor['preparedTaskOrders'] as $preparedTaskOrders) {
            $result = $shoppingfeedApi->updateMainStoreOrdersStatus($preparedTaskOrders, $this->conveyor['shoppingfeed_store_id']);

            if (!$result) {
                continue;
            }

            $batchId = current($result->getBatchIds());
            if (empty($batchId) === true) {
                continue;
            }

            foreach ($preparedTaskOrders as $preparedTaskOrder) {
                $taskOrder = $preparedTaskOrder['taskOrder'];
                $taskOrder->batch_id = $batchId;
                $taskOrder->action = ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_SYNC_STATUS;
                $taskOrder->save();

                ProcessLoggerHandler::logSuccess(
                    sprintf(
                        static::getLogPrefix($taskOrder->id_order) . ' ' .
                            $this->l('Ticket created for Order %s Status %s', 'ShoppingfeedOrderSyncActions'),
                        '',
                        $preparedTaskOrder['operation']
                    ),
                    'Order',
                    $taskOrder->id_order
                );

                $this->conveyor['successfulTaskOrders'][] = $taskOrder;
            }
        }
        if (empty($this->conveyor['successfulTaskOrders'])) {
            return false;
        }

        return true;
    }

    public function sendTaskOrdersSyncInvoice()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        if (empty($this->conveyor['preparedTaskOrders'])) {
            ProcessLoggerHandler::logError(
                $this->l('No prepared Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($this->conveyor['id_token']);
        if ($shoppingfeedApi == false) {
            ProcessLoggerHandler::logError(
                $this->l('Could not retrieve Shopping Feed API.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        foreach ($this->conveyor['preparedTaskOrders'] as $preparedTaskOrders) {
            $result = $shoppingfeedApi->updateMainStoreOrdersStatus($preparedTaskOrders, $this->conveyor['shoppingfeed_store_id']);

            if (!$result) {
                ProcessLoggerHandler::logError(
                    $this->l('API request is failed', 'ShoppingfeedOrderSyncActions'),
                    'Order'
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            $batchId = current($result->getBatchIds());
            if (empty($batchId) === true) {
                ProcessLoggerHandler::logError(
                    $this->l('API response does not contain batchId', 'ShoppingfeedOrderSyncActions'),
                    'Order'
                );
                Registry::increment('syncStatusErrors');
                continue;
            }

            foreach ($preparedTaskOrders as $preparedTaskOrder) {
                $taskOrder = $preparedTaskOrder['taskOrder'];
                $taskOrder->batch_id = $batchId;
                $taskOrder->action = ShoppingfeedTaskOrder::ACTION_CHECK_TICKET_UPLOAD_INVOICE;
                $taskOrder->save();

                ProcessLoggerHandler::logSuccess(
                    static::getLogPrefix($taskOrder->id_order) . ' ' . $this->l('Ticket created to upload an order invoice', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );

                $this->conveyor['successfulTaskOrders'][] = $taskOrder;
                unlink($preparedTaskOrder['file']);
            }
        }
        if (empty($this->conveyor['successfulTaskOrders'])) {
            return false;
        }

        return true;
    }

    public function prepareTaskOrdersCheckTicketsSyncStatus()
    {
        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logInfo(
                $this->l('No Task Orders to prepare.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $taskOrders = $this->conveyor['taskOrders'];

        // We don't have much to prepare; just make sure every task order has a
        // ticket number, and link each task to its SF order
        $this->conveyor['preparedTaskOrders'] = [];
        foreach ($taskOrders as $taskOrder) {
            /** @var $taskOrder ShoppingfeedTaskOrder */
            $logPrefix = self::getLogPrefix($taskOrder->id_order);
            $shoppingfeedOrder = ShoppingfeedOrder::getByIdOrder($taskOrder->id_order);
            if (!Validate::isLoadedObject($shoppingfeedOrder)) {
                ProcessLoggerHandler::logError(
                    $logPrefix . ' ' .
                        $this->l('Could not check ticket status; SF Order could not be loaded.', 'ShoppingfeedOrderSyncActions'),
                    'Order',
                    $taskOrder->id_order
                );
                Registry::increment('ticketsErrors');
                continue;
            }

            $this->conveyor['preparedTaskOrders'][] = [
                'taskOrder' => $taskOrder,
                'ticket_number' => $taskOrder->ticket_number,
                'reference_marketplace' => $shoppingfeedOrder->id_order_marketplace,
            ];
        }

        return true;
    }

    public function sendTaskOrdersCheckTicketsSyncStatus()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        if (empty($this->conveyor['taskOrders'])) {
            ProcessLoggerHandler::logError(
                $this->l('No Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }

        $tickets = $this->getTicketsForBatchIds(
            $this->extractBatchIds($this->conveyor['taskOrders']),
            $this->conveyor['id_token']
        );

        if (!$tickets) {
            return false;
        }

        // Index each task order with its order reference
        $preparedTaskOrders = [];
        foreach ($this->conveyor['preparedTaskOrders'] as $preparedTaskOrder) {
            $preparedTaskOrders[$preparedTaskOrder['reference_marketplace']] = $preparedTaskOrder;
        }
        unset($preparedTaskOrder);

        // Check each ticket, and match it with its task order
        // Don't delete task orders here, we may need them later !
        $this->conveyor['successfulTaskOrders'] = [];
        $this->conveyor['failedTaskOrders'] = [];
        foreach ($tickets as $ticket) {
            $ticketOrderReference = $ticket->getPayloadProperty('reference');
            // When the module gets the tickets by batch-id, the list might contain the ticket missed in $preparedTaskOrders
            if (empty($preparedTaskOrders[$ticketOrderReference])) {
                continue;
            }

            $taskOrder = $preparedTaskOrders[$ticketOrderReference]['taskOrder'];

            // Check ticket status.
            // Basically, we're just sorting them. We'll decide what to do with
            // them later...
            switch ($ticket->getStatus()) {
                case 'failed':
                    $this->conveyor['failedTaskOrders'][] = $taskOrder;
                    ProcessLoggerHandler::logError(
                        sprintf(
                            static::getLogPrefix($taskOrder->id_order) . ' ' .
                            $this->l('Ticket status : %s. Ticket details: %s', 'ShoppingfeedOrderSyncActions'),
                            $ticket->getStatus(),
                            json_encode($ticket)
                        ),
                        'Order',
                        $taskOrder->id_order
                    );
                    continue 2;
                case 'scheduled':
                case 'running':
                    // Don't do anything, we'll just send them again later
                    continue 2;
                case 'canceled':
                    $this->conveyor['successfulTaskOrders'][] = $taskOrder;
                    ProcessLoggerHandler::logInfo(
                        sprintf(
                            static::getLogPrefix($taskOrder->id_order) . ' ' .
                            $this->l('Ticket status : %s. Ticket details: %s', 'ShoppingfeedOrderSyncActions'),
                            $ticket->getStatus(),
                            json_encode($ticket)
                        ),
                        'Order',
                        $taskOrder->id_order
                    );
                    continue 2;
                case 'succeed':
                    $this->conveyor['successfulTaskOrders'][] = $taskOrder;
                    ProcessLoggerHandler::logSuccess(
                        sprintf(
                            static::getLogPrefix($taskOrder->id_order) . ' ' .
                            $this->l('Ticket status : %s', 'ShoppingfeedOrderSyncActions'),
                            $ticket->getStatus()
                        ),
                        'Order',
                        $taskOrder->id_order
                    );
                    continue 2;
            }
        }

        return true;
    }

    public function sendFailedTaskOrdersMail()
    {
        if (empty($this->conveyor['id_shop'])) {
            ProcessLoggerHandler::logError(
                $this->l('No ID Shop found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $id_shop = $this->conveyor['id_shop'];

        if (empty($this->conveyor['failedTaskOrders'])) {
            ProcessLoggerHandler::logError(
                $this->l('No Task Orders found.', 'ShoppingfeedOrderSyncActions'),
                'Order'
            );

            return false;
        }
        $failedTaskOrders = $this->conveyor['failedTaskOrders'];

        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT', null, null, $id_shop);

        if (false === $this->isEmailTemplateExists('order-sync-errors', $id_lang, $id_shop)) {
            $id_lang = (int) Language::getIdByIso('en');

            if ($id_lang == false) {
                return false;
            }
        }

        // Get order data
        $failedTaskOrdersMailData = [];

        foreach ($failedTaskOrders as $taskOrder) {
            $order = new Order($taskOrder->id_order);
            $orderState = new OrderState($order->current_state);
            $sfOrder = ShoppingfeedOrder::getByIdOrder($order->id);

            // Send mail only once
            if ($sfOrder->failed_ticket == 1) {
                continue;
            }

            $sfOrder->failed_ticket = 1;
            $sfOrder->save();
            $failedTaskOrdersMailData[] = [
                'reference' => $order->reference,
                'status' => !empty($orderState->name[$id_lang]) ? $orderState->name[$id_lang] : reset($orderState->name),
            ];
        }

        if (empty($failedTaskOrdersMailData)) {
            return true;
        }

        $listFailuresHtml = $this->getEmailTemplateContent(
            'order-sync-errors-list.tpl',
            $id_lang,
            $id_shop,
            ['failedTaskOrdersData' => $failedTaskOrdersMailData]
        );
        $listFailuresTxt = $this->getEmailTemplateContent(
            'order-sync-errors-list.txt',
            $id_lang,
            $id_shop,
            ['failedTaskOrdersData' => $failedTaskOrdersMailData]
        );

        return Mail::Send(
            (int) $id_lang,
            'order-sync-errors',
            $this->l('Shopping Feed synchronization errors', 'ShoppingfeedOrderSyncActions'),
            [
                '{list_order_sync_errors_html}' => $listFailuresHtml,
                '{list_order_sync_errors_txt}' => $listFailuresTxt,
            ],
            Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'shoppingfeed/mails/',
            false,
            $id_shop
        );
    }

    /**
     * Finds a module mail template for the specified lang and shop
     *
     * @param string $template_name template name with extension
     * @param int $id_lang
     * @param int $id_shop
     * @param array $var will be assigned to the smarty template
     *
     * @return string the template's content, or an empty string if no template
     *                was found
     */
    protected function getEmailTemplateContent($template_name, $id_lang, $id_shop, $var)
    {
        $shop = new Shop((int) $id_shop);

        if (false === Validate::isLoadedObject($shop)) {
            return '';
        }

        $templatePath = $this->getEmailTemplatePath($template_name, $id_lang, $id_shop);

        if (!$templatePath) {
            return '';
        }

        // Multi-shop / multi-theme might not work properly when using
        // the basic "$context->smarty->createTemplate($tpl_name)" syntax, as
        // the template's compile_id will be the same for every shop / theme
        // See https://github.com/PrestaShop/PrestaShop/pull/13804
        $context = Context::getContext();
        $scope = $context->smarty->createData($context->smarty);
        $scope->assign($var);

        if (isset($shop->theme)) {
            // PS17
            $themeName = $shop->theme->getName();
        } else {
            // PS16
            $themeName = $shop->theme_name;
        }

        $templateContent = $context->smarty->createTemplate(
            $templatePath,
            $scope,
            $themeName
        )->fetch();

        return $templateContent;
    }

    /**
     * @param string $template_name template name with extension
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return string the template's path
     */
    protected function getEmailTemplatePath($template_name, $id_lang, $id_shop)
    {
        $templatePath = '';
        $isoLang = Language::getIsoById($id_lang);
        $shop = new Shop($id_shop);

        if (false === Validate::isLoadedObject($shop)) {
            return $templatePath;
        }

        if (isset($shop->theme)) {
            // PS17
            $themeName = $shop->theme->getName();
        } else {
            // PS16
            $themeName = $shop->theme_name;
        }

        $pathsToCheck = [
            _PS_ALL_THEMES_DIR_ . $themeName . '/shoppingfeed/mails/' . $isoLang . '/' . $template_name,
            _PS_MODULE_DIR_ . 'shoppingfeed/mails/' . $isoLang . '/' . $template_name,
        ];

        foreach ($pathsToCheck as $path) {
            if (Tools::file_exists_cache($path)) {
                $templatePath = $path;
                break;
            }
        }

        return $templatePath;
    }

    /**
     * @param string $template_name template name with extension
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return bool
     */
    protected function isEmailTemplateExists($template_name, $id_lang, $id_shop)
    {
        $mailType = Configuration::get('PS_MAIL_TYPE', null, null, $id_shop);

        if (($mailType == Mail::TYPE_BOTH || $mailType == Mail::TYPE_HTML) && empty($this->getEmailTemplatePath($template_name . '.html', $id_lang, $id_shop))) {
            return false;
        }

        if (($mailType == Mail::TYPE_BOTH || $mailType == Mail::TYPE_TEXT) && empty($this->getEmailTemplatePath($template_name . '.txt', $id_lang, $id_shop))) {
            return false;
        }

        return true;
    }

    protected function initTaskCleaner()
    {
        return new TaskOrderCleaner();
    }

    protected function extractBatchIds($taskOrders)
    {
        $batchIds = [];
        /** @var ShoppingfeedTaskOrder $taskOrder */
        foreach ($taskOrders as $taskOrder) {
            if (in_array($taskOrder->batch_id, $batchIds)) {
                continue;
            }

            $batchIds[] = $taskOrder->batch_id;
        }

        return $batchIds;
    }

    protected function getTicketsForBatchIds($batchIds, $idShoppingfeedToken)
    {
        $shoppingfeedApi = ShoppingfeedApi::getInstanceByToken($idShoppingfeedToken);
        $sfToken = new ShoppingfeedToken($idShoppingfeedToken);
        $tickets = [];

        foreach ($batchIds as $batchId) {
            $tickets = array_merge($tickets, $shoppingfeedApi->getTicketsByBatchId($batchId, [], $sfToken->shoppingfeed_store_id));
        }

        return $tickets;
    }
}
