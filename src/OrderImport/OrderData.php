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

namespace ShoppingfeedAddon\OrderImport;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use Tools;
use Validate;

/**
 * This class is a mutable copy of \ShoppingFeed\Sdk\Api\Order\OrderResource. Its
 * only purpose is holding the original object's data so they can be modified
 * during the process.
 *
 * NO PROCESSING MUST BE DONE HERE. This class should NEVER hold responsibility
 * for modifying data.
 *
 * Also note that the order's shopping feed ID, reference and channel are not
 * held here, as there should be no need to modify them.
 */
class OrderData
{
    /** @var string|null */
    public $storeReference;

    /** @var string */
    public $status;

    /** @var \DateTimeImmutable|null */
    public $acknowledgedAt;

    /** @var \DateTimeImmutable|null */
    public $updatedAt;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var array */
    public $shippingAddress;

    /** @var array */
    public $billingAddress;

    /** @var array */
    public $payment;

    /** @var array */
    public $shipment;

    /** @var array An array of \ShoppingfeedAddon\OrderImport\OrderItemData */
    public $items = [];

    /** @var array An array of \ShoppingfeedAddon\OrderImport\OrderItemData */
    public $itemsReferencesAliases = [];

    /** @var array */
    public $additionalFields;

    protected $isoCountryMap = [
        'UK' => 'GB',
    ];

    /** @var OrderCustomerData */
    protected $customer;

    /** @var OrderResource */
    protected $apiOrder;

    public function __construct(OrderResource $apiOrder)
    {
        $this->storeReference = $apiOrder->getStoreReference();
        $this->status = $apiOrder->getStatus();
        $this->acknowledgedAt = $apiOrder->getAcknowledgedAt();
        $this->updatedAt = $apiOrder->getUpdatedAt();
        $this->createdAt = $apiOrder->getCreatedAt();
        $this->shippingAddress = $this->validateISO($apiOrder->getShippingAddress());
        $this->billingAddress = $this->validateISO($apiOrder->getBillingAddress());
        $this->payment = $apiOrder->getPaymentInformation();
        $this->shipment = $apiOrder->getShipment();
        $this->itemsReferencesAliases = $apiOrder->getItemsReferencesAliases();
        $this->apiOrder = $apiOrder;
        // TODO : OrderResource should likely have a "getAdditionalFields" method
        $apiOrderData = $apiOrder->toArray();
        $this->additionalFields = empty($apiOrderData['additionalFields']) === false ? $apiOrderData['additionalFields'] : [];

        /** @var \ShoppingFeed\Sdk\Api\Order\OrderItem $apiOrderItem */
        foreach ($apiOrder->getItems() as $apiOrderItem) {
            $this->items[] = new OrderItemData($apiOrderItem);
        }
    }

    /**
     * @param array $address
     *
     * @return array
     */
    protected function validateISO($address)
    {
        if (false === is_array($address)) {
            return $address;
        }

        if (false === isset($address['country'])) {
            return $address;
        }

        // ISO in PrestaShop can differ one of Shoppingfeed
        if (isset($this->isoCountryMap[$address['country']])) {
            $address['country'] = $this->isoCountryMap[$address['country']];
        }

        return $address;
    }

    public function getCustomer()
    {
        if ($this->customer instanceof OrderCustomerData) {
            return $this->customer;
        }

        $this->customer = new OrderCustomerData();

        if (false == empty($this->billingAddress['firstName'])) {
            $firstName = Tools::substr($this->billingAddress['firstName'], 0, 32);
            // Numbers are forbidden in firstname / lastname
            $firstName = preg_replace('/\-?\d+/', '', $firstName);
            $this->customer->setFirstName($firstName);
        }

        if (false == empty($this->billingAddress['lastName'])) {
            $lastName = Tools::substr($this->billingAddress['lastName'], 0, 32);
            // Numbers are forbidden in firstname / lastname
            $lastName = preg_replace('/\-?\d+/', '', $lastName);
            $this->customer->setLastName($lastName);
        }

        if (Validate::isEmail($this->billingAddress['email'])) {
            $this->customer->setEmail($this->billingAddress['email']);
        } elseif (Validate::isEmail($this->shippingAddress['email'])) {
            $this->customer->setEmail($this->shippingAddress['email']);
        } else {
            $this->customer->setEmail($this->apiOrder->getId() . '@' . $this->apiOrder->getChannel()->getName() . '.sf');
        }

        return $this->customer;
    }

    public function setCustomer(OrderCustomerData $customerData)
    {
        $this->customer = $customerData;
    }
}
