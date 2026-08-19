<?php
/**
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
 */

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Customer;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedAddon\Services\SymbolValidator;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

/**
 * Removes characters forbidden by Prestashop's customer name validation
 * (Customer::$definition['fields']['firstname'/'lastname']['validate'])
 * from the firstname/lastname of a customer about to be created, so that
 * $customer->add() does not fail on marketplace-provided names (eg. company
 * names containing commas or digits). Always applied, not configurable.
 */
class CustomerNameConformity extends RuleAbstract implements RuleInterface
{
    /** @var SymbolValidator */
    protected $validator;

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->validator = new SymbolValidator();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(OrderResource $apiOrder)
    {
        return true;
    }

    /**
     * Strips characters not accepted by Customer::$definition validation
     * from the firstname/lastname just before the customer is created.
     *
     * @param array $params
     */
    public function onCustomerCreation($params)
    {
        /** @var Customer $customer */
        $customer = $params['customer'];
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', 'CustomerNameConformity'),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        $this->validator->validate(
            $customer->firstname,
            [
                'Validate',
                Customer::$definition['fields']['firstname']['validate'],
            ],
            '',
            true
        );
        $this->validator->validate(
            $customer->lastname,
            [
                'Validate',
                Customer::$definition['fields']['lastname']['validate'],
            ],
            '',
            true
        );

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            $this->l('Rule triggered. Invalid characters removed from customer firstname/lastname.', 'CustomerNameConformity'),
            'Customer'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('All orders', 'CustomerNameConformity');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Removes characters prohibited by Prestashop from the customer firstname and lastname before the customer is created, so orders with invalid marketplace-provided names are not rejected.', 'CustomerNameConformity');
    }
}
