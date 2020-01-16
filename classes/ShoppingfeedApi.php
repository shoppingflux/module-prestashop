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

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

use ShoppingFeed\Sdk\Http\Adapter\Guzzle6Adapter;
use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Credential\Password;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate;
use ShoppingFeed\Sdk\Api\Catalog\PricingUpdate;
use ShoppingFeed\Sdk\Api\Order\OrderOperation;

/**
 * This class is a singleton, which is responsible for calling the SF API using
 * the SDK
 */
class ShoppingfeedApi
{
    /** @var ShoppingfeedApi */
    private static $instance = null;

    /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
    private $session = null;

    private function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Returns the object's instance, using a token. If no session was
     * initialized, creates it. No exceptions are handled here.
     *
     * @param $id_shop the shop to use (one token per shop)
     * @param $token the token to use, if no shop is specified
     * @return ShoppingfeedApi
     */
    public static function getInstanceByToken($id_shop = null, $token = null)
    {
        if (static::$instance && static::$instance->getToken() == $token) {
            return static::$instance;
        }

        if (!$token && !$id_shop) {
            return false;
        } elseif ($id_shop) {
            $token = Configuration::get(Shoppingfeed::AUTH_TOKEN, null, null, $id_shop);
        }

        try {
            // Setup token to connect to the API, and create session
            $credential = new Token($token);
            // Add Guzzle as HTTP interface
            $clientOptions = new ClientOptions();
            $clientOptions->setHttpAdapter(new Guzzle6Adapter());
            /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential, $clientOptions);

            static::$instance = new ShoppingfeedApi($session);
            return static::$instance;
        } catch (Exception $e) {
            ProcessLoggerHandler::logInfo(
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
     * @param $username
     * @param $password
     * @return ShoppingfeedApi
     */
    public static function getInstanceByCredentials($username, $password)
    {
        try {
            // Setup credentials to connect to the API, and create session
            $credential = new Password($username, $password);
            // Add Guzzle as HTTP interface
            $clientOptions = new ClientOptions();
            $clientOptions->setHttpAdapter(new Guzzle6Adapter());
            /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential, $clientOptions);
            static::$instance = new ShoppingfeedApi($session);

            return static::$instance;
        } catch (Exception $e) {
            ProcessLoggerHandler::logInfo(
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

    /**
     * Makes the call to update the SF inventory
     * @param array $products an array of product's references and quantities
     * <pre>
     * Array(
     *      Array(
     *          'reference' => 'ref1',
     *          'quantity' => 7
     *      ),
     *      Array(
     *          'reference' => 'ref2',
     *          'quantity' => 1
     *      ),
     * )
     * </pre>
     * @return ShoppingFeed\Sdk\Api\Catalog\InventoryCollection
     */
    public function updateMainStoreInventory($products)
    {
        try {
            $inventoryApi = $this->session->getMainStore()->getInventoryApi();
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
     * @param array $products an array of product's references and prices
     * <pre>
     * Array(
     *      Array(
     *          'reference' => 'ref1',
     *          'price' => 7.2
     *      ),
     *      Array(
     *          'reference' => 'ref2',
     *          'price' => 1.4
     *      ),
     * )
     * </pre>
     * @return ShoppingFeed\Sdk\Api\Catalog\InventoryCollection
     */
    public function updateMainStorePrices($products)
    {
        try {
            $pricingApi = $this->session->getMainStore()->getPricingApi();
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
     * @param array $taskOrders
     */
    public function updateMainStoreOrdersStatus($taskOrders)
    {
        try {
            $orderApi = $this->session->getMainStore()->getOrderApi();
            $operation = new \ShoppingFeed\Sdk\Api\Order\OrderOperation();

            foreach ($taskOrders as $taskOrder) {
                switch($taskOrder['operation']) {
                    case OrderOperation::TYPE_SHIP:
                        $operation->ship(
                            $taskOrder['reference_marketplace'],
                            $taskOrder['marketplace'],
                            $taskOrder['payload']['carrier_name'],
                            $taskOrder['payload']['tracking_number'],
                            $taskOrder['payload']['tracking_url']
                        );
                        break;
                    case OrderOperation::TYPE_CANCEL:
                        $operation->cancel(
                            $taskOrder['reference_marketplace'],
                            $taskOrder['marketplace']
                        );
                        break;
                    case OrderOperation::TYPE_REFUND:
                        $operation->refund(
                            $taskOrder['reference_marketplace'],
                            $taskOrder['marketplace']
                        );
                        break;
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
        $tickets = array();
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
                return false;
            }
            
            if (!$ticket || !$ticket->getId()) {
                continue;
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }
    
    /**
     * Pings the Shopping Feed API. Always creates a new client.
     */
    public static function ping()
    {
        if (!interface_exists(SfGuzzle\GuzzleHttp\ClientInterface::class)) {
            throw new Exception("Shoppingfeed : Guzzle does not seem to be installed.");
        }
        
        if (version_compare(SfGuzzle\GuzzleHttp\ClientInterface::VERSION, '6', '<')
            || version_compare(SfGuzzle\GuzzleHttp\ClientInterface::VERSION, '7', '>=')
        ) {
            throw new Exception("Shoppingfeed : the module only supports Guzzle v6.");
        }
        
        $clientOptions = new ClientOptions();
        $clientOptions->setHttpAdapter(new Guzzle6Adapter());
        $client = new Client($clientOptions);
        return $client->ping();
    }
}
