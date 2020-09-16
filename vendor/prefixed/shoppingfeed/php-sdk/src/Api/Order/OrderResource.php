<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 * @version   [version]
 */

namespace ShoppingFeed\Sdk\Api\Order;

use ShoppingFeed\Sdk\Api\Channel\ChannelResource;
use ShoppingFeed\Sdk\Resource\AbstractResource;

class OrderResource extends AbstractResource
{
    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->getProperty('id');
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return (string) $this->getProperty('reference');
    }

    /**
     * @return null|string
     */
    public function getStoreReference()
    {
        return $this->getProperty('storeReference');
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return (string) $this->getProperty('status');
    }

    /**
     * @return null|\DateTimeImmutable
     */
    public function getAcknowledgedAt()
    {
        return $this->getPropertyDatetime('acknowledgedAt');
    }

    /**
     * @return null|\DateTimeImmutable
     */
    public function getUpdatedAt()
    {
        return $this->getPropertyDatetime('updatedAt');
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreatedAt()
    {
        return $this->getPropertyDatetime('createdAt');
    }

    /**
     * @return array
     */
    public function getShippingAddress()
    {
        return $this->getProperty('shippingAddress');
    }

    /**
     * @return array
     */
    public function getBillingAddress()
    {
        return $this->getProperty('billingAddress');
    }

    /**
     * @return array
     */
    public function getPaymentInformation()
    {
        return $this->getProperty('payment');
    }

    /**
     * @return array
     */
    public function getShipment()
    {
        return $this->getProperty('shipment');
    }

    /**
     * @return array
     */
    public function getItemsReferencesAliases()
    {
        return $this->getProperty('itemsReferencesAliases');
    }

    /**
     * Fetch order items details
     * The resource has to be loaded to access to items collection
     *
     * @return OrderItemCollection|OrderItem[]
     */
    public function getItems()
    {
        return OrderItemCollection::fromProperties(
            $this->getProperty('items', true) ?: []
        );
    }

    /**
     * @return ChannelResource A partial representation of the channel resource
     */
    public function getChannel()
    {
        return new ChannelResource(
            $this->resource->getFirstResource('channel')
        );
    }
}
