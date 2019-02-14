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

use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Credential\Password;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate;

/**
 * This class is a singleton, which is responsible for calling the SF API using the SDK
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
     * Returns the object's instance, using a token. If no session was initialized, creates it. No exceptions are handled here.
     * @param $id_shop the shop to use (one token per shop)
     * @param $token the token to use, if no shop is specified
     * @return ShoppingfeedApi
     */
    public static function getInstanceByToken($id_shop = null, $token = null)
    {
        if (static::$instance) {
            return static::$instance;
        }

        if (!$token && !$id_shop) {
            return false;
        } else if ($id_shop) {
            $token = Configuration::get(Shoppingfeed::AUTH_TOKEN, null, null, $id_shop)  ;
        }

        try {
            // Setup token to connect to the API, and create session
            $credential = new Token($token);
            /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential);

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

    /**
     * Returns the object's instance, using credentials. Always creates a new session. No exceptions are handled here.
     * @param $username
     * @param $password
     * @return ShoppingfeedApi
     */
    public static function getInstanceByCredentials($username, $password)
    {
        try {
            // Setup credentials to connect to the API, and create session
            $credential = new Password($username, $password);
            /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
            $session = Client::createSession($credential);
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
            ProcessLoggerHandler::logInfo(
                sprintf(
                    'API error (getInventoryApi): %s',
                    $e->getMessage()
                )
            );
            return false;
        }
    }
}
