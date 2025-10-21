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

use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate;
use ShoppingFeed\Sdk\Api\Catalog\PricingUpdate;
use ShoppingFeed\Sdk\Api\Order\Document\Invoice;
use ShoppingFeed\Sdk\Api\Order\Identifier\Id;
use ShoppingFeed\Sdk\Api\Order\ShipReturnInfo;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingFeed\Sdk\Credential\Password;
use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Http\Adapter\GuzzleHTTPAdapter;
use ShoppingfeedAddon\OrderImport\SinceDate;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * This class is a singleton, which is responsible for calling the SF API using
 * the SDK
 */
class ShoppingfeedApi
{
    /** @var ShoppingfeedApi|null */
    private static $instance;

    /** @var ShoppingFeed\Sdk\Api\Session\SessionResource */
    private $session;

    protected $id_shop;

    private function __construct($session)
    {
        $this->session = $session;
        $sfToken = (new ShoppingfeedToken())->findByToken($this->session->getToken());

        if (isset($sfToken['id_shop'])) {
            $this->id_shop = (int) $sfToken['id_shop'];
        }
    }

    /**
     * Returns the object's instance, using a token. If no session was
     * initialized, creates it. No exceptions are handled here.
     *
     * @param int|null $id_token the shop to use (one token per shop)
     * @param string|null $token the token to use, if no shop is specified
     *
     * @return ShoppingfeedApi|bool
     */
    public static function getInstanceByToken($id_token = null, $token = null)
    {
        if (self::$instance && self::$instance->getToken() == $token) {
            return self::$instance;
        }

        if (!$token && !$id_token) {
            return false;
        } elseif ($id_token) {
            $sft = new ShoppingfeedToken($id_token);
            $token = $sft->content;
        }

        try {
            // Setup token to connect to the API, and create session
            $credential = new Token($token);
            // Add Guzzle as HTTP interface
            $clientOptions = new ClientOptions();
            $clientOptions->setHttpAdapter(new GuzzleHTTPAdapter());
            /** @var ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential, $clientOptions); // @phpstan-ignore-line

            self::$instance = new ShoppingfeedApi($session);

            return self::$instance;
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error: %s',
                    $e->getMessage()
                )
            );
            ProcessLoggerHandler::saveLogsInDb();
            throw $e;
        }
    }

    /**
     * Returns the object's instance, using credentials. Always creates a new
     * session. No exceptions are handled here.
     *
     * @param string $username
     * @param string $password
     *
     * @return ShoppingfeedApi|bool
     */
    public static function getInstanceByCredentials($username, $password)
    {
        try {
            // Setup credentials to connect to the API, and create session
            $credential = new Password($username, $password);
            // Add Guzzle as HTTP interface
            $clientOptions = new ClientOptions();
            $clientOptions->setHttpAdapter(new GuzzleHTTPAdapter());
            /** @var ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential, $clientOptions); // @phpstan-ignore-line
            self::$instance = new ShoppingfeedApi($session);

            return self::$instance;
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error: %s',
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    public function getToken()
    {
        return $this->session->getToken();
    }

    public function getStores()
    {
        return $this->session->getStores();
    }

    public function getMainStore()
    {
        return $this->session->getMainStore();
    }

    /**
     * Makes the call to update the SF inventory
     *
     * @param array $products an array of product's references and quantities
     *                        <pre>
     *                        Array(
     *                        Array(
     *                        'reference' => 'ref1',
     *                        'quantity' => 7
     *                        ),
     *                        Array(
     *                        'reference' => 'ref2',
     *                        'quantity' => 1
     *                        ),
     *                        )
     *                        </pre>
     *
     * @return ShoppingFeed\Sdk\Api\Catalog\InventoryCollection|bool
     */
    public function updateMainStoreInventory($products, $shoppingfeed_store_id = null)
    {
        try {
            $inventoryApi = null;

            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($store->getId() == $shoppingfeed_store_id) {
                        $inventoryApi = $store->getInventoryApi();
                    }
                }
            } else {
                $inventoryApi = $this->session->getMainStore()->getInventoryApi();
            }

            if (!$inventoryApi) {
                throw new Exception('Invalid store ID');
            }

            $inventoryUpdate = new InventoryUpdate();
            foreach ($products as $product) {
                $inventoryUpdate->add($product['reference'], $product['quantity']);
            }

            return $inventoryApi->execute($inventoryUpdate);
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getInventoryApi): %s',
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    /**
     * Makes the call to update the SF prices
     *
     * @param array $products an array of product's references and prices
     *                        <pre>
     *                        Array(
     *                        Array(
     *                        'reference' => 'ref1',
     *                        'price' => 7.2
     *                        ),
     *                        Array(
     *                        'reference' => 'ref2',
     *                        'price' => 1.4
     *                        ),
     *                        )
     *                        </pre>
     *
     * @return ShoppingFeed\Sdk\Api\Catalog\InventoryCollection|bool
     */
    public function updateMainStorePrices($products, $shoppingfeed_store_id = null)
    {
        try {
            $pricingApi = null;

            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($store->getId() == $shoppingfeed_store_id) {
                        $pricingApi = $store->getPricingApi();
                    }
                }
            } else {
                $pricingApi = $this->session->getMainStore()->getPricingApi();
            }

            if (!$pricingApi) {
                throw new Exception('Invalid store ID');
            }

            $pricingUpdate = new PricingUpdate();
            foreach ($products as $product) {
                $pricingUpdate->add($product['reference'], $product['price']);
            }

            return $pricingApi->execute($pricingUpdate);
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getPricingApi): %s',
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    /**
     * Makes the call to request updates of the SF orders statuses
     *
     * @param array $taskOrders
     */
    public function updateMainStoreOrdersStatus($taskOrders, $shoppingfeed_store_id = null)
    {
        try {
            $orderApi = null;

            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($store->getId() == $shoppingfeed_store_id) {
                        $orderApi = $store->getOrderApi();
                    }
                }
            } else {
                $orderApi = $this->session->getMainStore()->getOrderApi();
            }

            if (!$orderApi) {
                throw new Exception('Invalid store ID');
            }

            $operation = new ShoppingFeed\Sdk\Api\Order\Operation();

            // improve tasks orders with custom data
            Hook::exec('actionShoppingfeedOrderOperation', ['taskOrders' => &$taskOrders]);

            foreach ($taskOrders as $taskOrder) {
                switch ($taskOrder['operation']) {
                    case Shoppingfeed::ORDER_OPERATION_SHIP:
                        if (empty($taskOrder['payload']) === false) {
                            $shipReturnInfo = new ShipReturnInfo(
                                !empty($taskOrder['payload']['return_info']['carrier']) ? $taskOrder['payload']['return_info']['carrier'] : null,
                                !empty($taskOrder['payload']['return_info']['tracking_number']) ? $taskOrder['payload']['return_info']['tracking_number'] : null
                            );
                        } else {
                            $shipReturnInfo = null;
                        }
                        $operation->ship(
                            new Id((int) $taskOrder['id_internal_shoppingfeed']),
                            (string) $taskOrder['payload']['carrier_name'],
                            (string) $taskOrder['payload']['tracking_number'],
                            (string) $taskOrder['payload']['tracking_url'],
                            !empty($taskOrder['payload']['items']) ? $taskOrder['payload']['items'] : [],
                            $shipReturnInfo,
                            !empty($taskOrder['payload']['warehouse_id']) ? $taskOrder['payload']['warehouse_id'] : null
                        );
                        continue 2;
                    case Shoppingfeed::ORDER_OPERATION_CANCEL:
                        $operation->cancel(
                            new Id((int) $taskOrder['id_internal_shoppingfeed']),
                            !empty($taskOrder['payload']['reason']) ? $taskOrder['payload']['reason'] : ''
                        );
                        continue 2;
                    case Shoppingfeed::ORDER_OPERATION_REFUND:
                        $operation->refund(
                            new Id((int) $taskOrder['id_internal_shoppingfeed']),
                            !empty($taskOrder['payload']['shipping']) ? $taskOrder['payload']['shipping'] : '',
                            !empty($taskOrder['payload']['products']) ? $taskOrder['payload']['products'] : []
                        );
                        continue 2;
                    case Shoppingfeed::ORDER_OPERATION_DELIVER:
                        $operation->deliver(
                            new Id((int) $taskOrder['id_internal_shoppingfeed'])
                        );
                        continue 2;
                    case Shoppingfeed::ORDER_OPERATION_UPLOAD_DOCUMENTS:
                        $operation->uploadDocument(
                            new Id((int) $taskOrder['id_internal_shoppingfeed']),
                            new Invoice($taskOrder['payload']['uri'])
                        );
                        continue 2;
                }
            }

            return $orderApi->execute($operation);
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getOrderApi): %s',
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    public function getTicketsByReference($taskOrders)
    {
        try {
            $ticketApi = $this->session->getMainStore()->getTicketApi();
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getTicketApi): %s',
                    $e->getMessage()
                )
            );

            return false;
        }

        // There's no method to retrieve tickets in bulk using an array
        // of ticket numbers, so get them one by one...
        $tickets = [];
        foreach ($taskOrders as $taskOrder) {
            try {
                $ticket = $ticketApi->getOne($taskOrder['ticket_number']);
            } catch (Exception $e) {
                ProcessLoggerHandler::logError(
                    sprintf(
                        'API error (getTicketApi): %s',
                        $e->getMessage()
                    )
                );

                if (false == $e instanceof ShoppingfeedPrefix\GuzzleHttp\Exception\ClientException) {
                    return false;
                }

                $body = $e->getResponse()->getBody();
                $status = $e->getResponse()->getStatusCode();

                if ($status != 404) {
                    return false;
                }

                try {
                    $responseArray = json_decode($body->getContents(), true);
                } catch (Throwable $e) {
                    return false;
                }

                if (empty($responseArray['id'])) {
                    return false;
                }

                $orderTask = ShoppingfeedTaskOrder::getFromTicketNumber($responseArray['id']);

                if (false == Validate::isLoadedObject($orderTask)) {
                    return false;
                }

                $orderTask->delete();
            }

            if (empty($ticket) || !$ticket->getId()) {
                continue;
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    public function getUnacknowledgedOrders($iShipped = false, $shoppingfeed_store_id = null)
    {
        $status = $iShipped ? 'shipped' : 'waiting_shipment';
        // Criteria used to query order API
        $criteria = [
            'filters' => [
                // Only retrieve unacknowleged (non-imported) orders
                'acknowledgment' => 'unacknowledged',
                // Available Shoppingfeed status:
                // created, waiting_store_acceptance, refused, waiting_shipment, shipped,
                // cancelled, refunded, partially_refunded, partially_shipped
                'status' => $status,
                'since' => $this->getSinceDateService()->get(SinceDate::DATE_FORMAT_SF, $this->id_shop),
            ],
        ];

        Hook::exec(
            'ShoppingfeedOrderImportCriteria', // hook_name
            [
                'storeId' => $shoppingfeed_store_id,
                'criteria' => &$criteria,
                'iShipped' => &$iShipped,
            ] // hook_args
        );

        $orders = false;
        $orderApi = null;
        try {
            // Retrieve orders
            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($shoppingfeed_store_id == $store->getId()) {
                        $orderApi = $store->getOrderApi();
                    }
                }
            } else {
                $orderApi = $this->session->getMainStore()->getOrderApi();
            }

            if (!$orderApi) {
                throw new Exception('Invalid store ID');
            }

            $orders = $orderApi->getAll($criteria['filters']);
        } catch (Exception $ex) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getOrdersApi): %s',
                    $ex->getMessage()
                )
            );

            return [];
        }

        $ruleShippedByMarketplace = new ShoppingfeedAddon\OrderImport\Rules\ShippedByMarketplace();
        $filteredOrders = [];
        foreach ($orders as $order) {
            $orderRawData = $order->toArray();
            if ($orderRawData['isTest'] && !Configuration::get(Shoppingfeed::ORDER_IMPORT_TEST)) {
                continue;
            }
            if ($order->getStatus() === 'shipped' && false === $ruleShippedByMarketplace->isShippedByMarketplace($order)) {
                $createDate = $order->getCreatedAt();
                $restrictDate = DateTimeImmutable::createFromFormat(
                    SinceDate::DATE_FORMAT_PS,
                    $this->getSinceDateService()->getForShipped(SinceDate::DATE_FORMAT_PS, $this->id_shop)
                );
                if ($createDate->getTimestamp() < $restrictDate->getTimestamp()) {
                    continue;
                }
            }

            $filteredOrders[] = $order;
        }

        return $filteredOrders;
    }

    public function acknowledgeOrder($id_internal_shoppingfeed, $id_order_prestashop, $is_success = true, $message = '', $shoppingfeed_store_id = null)
    {
        try {
            $orderApi = null;

            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($store->getId() == $shoppingfeed_store_id) {
                        $orderApi = $store->getOrderApi();
                    }
                }
            } else {
                $orderApi = $this->session->getMainStore()->getOrderApi();
            }

            if (!$orderApi) {
                throw new Exception('Invalid store ID');
            }

            $operation = new ShoppingFeed\Sdk\Api\Order\Operation();
            $operation
                ->acknowledge(
                    new Id($id_internal_shoppingfeed),
                    (string) $id_order_prestashop,
                    ($is_success === true) ? 'success' : 'error',
                    $message
                );

            return $orderApi->execute($operation);
        } catch (Exception $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'API error (getOrderApi): %s',
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    /**
     * Pings the Shopping Feed API. Always creates a new client.
     */
    public static function ping()
    {
        if (!interface_exists(ShoppingfeedPrefix\GuzzleHttp\ClientInterface::class)) {
            throw new Exception('Shoppingfeed : Guzzle does not seem to be installed.');
        }

        $clientOptions = new ClientOptions();
        $clientOptions->setHttpAdapter(new GuzzleHTTPAdapter());
        $client = new Client($clientOptions);

        return $client->ping();
    }

    protected function getSinceDateService()
    {
        return new SinceDate();
    }

    public function getTicketsByBatchId($batchId, $filters = [], $shoppingfeed_store_id = null)
    {
        $tickets = [];
        $result = null;
        $ticketApi = null;

        try {
            if ($shoppingfeed_store_id) {
                foreach ($this->getStores() as $store) {
                    if ($store->getId() == $shoppingfeed_store_id) {
                        $ticketApi = $store->getTicketApi();
                    }
                }
            } else {
                $ticketApi = $this->session->getMainStore()->getTicketApi();
            }

            if (!$ticketApi) {
                throw new Exception('Invalid store ID');
            }

            $result = $ticketApi->getByBatch($batchId, $filters);
        } catch (Throwable $e) {
            ProcessLoggerHandler::logError(
                sprintf(
                    'Error in ShoppingfeedApi::getTicketsByBatchId(): %s',
                    $e->getMessage()
                )
            );

            return $tickets;
        }

        foreach ($result->getIterator() as $ticket) {
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    public function isExistedStore($store_id)
    {
        foreach ($this->getStores() as $store) {
            if ($store->getId() == $store_id) {
                return true;
            }
        }

        return false;
    }
}
