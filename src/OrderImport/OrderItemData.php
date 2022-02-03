<?php
/**
 *
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *
 */

namespace ShoppingfeedAddon\OrderImport;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Sdk\Api\Order\OrderItem;

/**
 * This class is a mutable copy of \ShoppingFeed\Sdk\Api\Order\OrderItem. Its
 * only purpose is holding the original object's data so they can be modified
 * during the process.
 *
 * NO PROCESSING MUST BE DONE HERE. This class should NEVER hold responsibility
 * for modifying data.
 */
class OrderItemData
{

    /** @var string $reference */
    public $reference;

    /** @var int $quantity */
    public $quantity;

    /** @var float $unitPrice */
    public $unitPrice;

    /** @var float $taxAmount */
    public $taxAmount;

    public function __construct(\ShoppingFeed\Sdk\Api\Order\OrderItem $orderItem)
    {
        $this->reference = $orderItem->getReference();
        $this->quantity = $orderItem->getQuantity();
        $this->unitPrice = $orderItem->getUnitPrice();
        $this->taxAmount = $orderItem->getTaxAmount();
    }

    public function getTotalPrice()
    {
        return $this->unitPrice * $this->quantity;
    }
}
