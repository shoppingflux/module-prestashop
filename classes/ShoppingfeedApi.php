<?php
/**
 * Created by PhpStorm.
 * User: aandria
 * Date: 10/07/18
 * Time: 17:02
 */

/** IMPORTANT : Guzzle version is different between the SF SDK and PS. They can not be interchanged.
 *  So if we're using the SDK in a PS process which uses Guzzle, the SDK will likely break...
 */
require_once _PS_MODULE_DIR_ . "shoppingfeed/shoppingfeed-vendor/autoload.php";

use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate;

/**
 * Class ShoppingfeedApi
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
     * Returns the object's instance. If no session was initialized, creates it. No exceptions are handled here.
     * @param $id_shop the shop to use (one token per shop)
     * @param $token the token to use, if no shop is specified
     * @return ShoppingfeedApi|false
     */
    public static function getInstance($id_shop = null, $token = null)
    {
        if (static::$instance) {
            return static::$instance;
        }

        if (!$token && !$id_shop) {
            return false;
        } else if ($id_shop) {
            $token = Configuration::get(shoppingfeed::AUTH_TOKEN . "_" . $id_shop);
        }

        // Setup credentials to connect to the API, and create session
        $credential = new Token($token);
        /** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
        $session = Client::createSession($credential);

        static::$instance = new ShoppingfeedApi($session);
        return static::$instance;
    }

    /**
     * Makes the call to update the SF inventory
     * @param $products an array of product's references and quantities
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
     * @return TODO
     */
    public function updateMainStoreInventory($products)
    {
        $totalUpdates = 0;
        $nbSlices = ceil(count($products) / 200);
        $inventoryApi = $this->session->getMainStore()->getInventoryApi();

        // We should never need this loop, but just in case...
        $returns = array();
        for ($i = 0; $i < $nbSlices; ++$i) {
            $productsSlice = array_slice($products, $i * 200, 200);

            $inventoryUpdate = new InventoryUpdate();
            foreach ($productsSlice as $product) {
                $inventoryUpdate->add($product['reference'], $product['quantity']);
            }
            $r = $inventoryApi->execute($inventoryUpdate);
            $returns[] = $r;
        }

        return $returns;
    }
}