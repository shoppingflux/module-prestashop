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

/**
 * This interface represents a specific rule to be applied during an SF order
 * import. See the classes in \ShoppingfeedAddon\OrderImport\Rules for examples.
 */
interface RuleInterface
{
    /**
     * If the configuration is not stored using the module's architecture, it
     * would be wise to set it here.
     *
     * @param array $configuration
     */
    public function __construct($configuration = array());

    /**
     * Returns true if a rule is applicable to an SF order
     *
     * @return bool
     */
    public function isApplicable(\ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder);

    /**
     * Returns an array with the rule's configuration
     *
     * @return array
     */
    public function getConfiguration();

    /**
     * Gets the configuration subform for a rule. This subform will be included
     * in the module's specific rules page.
     *
     * @return array an array of arrays formatted as inputs for HelperForm.
     */
    public function getConfigurationSubform();

    /**
     * Returns a description of the rule. This description will be included
     * in the module's specific rules page.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Returns a description of the conditions in which the rule should apply.
     * This description will be included in the module's specific rules page.
     *
     * @return string
     */
    public function getConditions();
}
