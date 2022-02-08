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
    public function __construct($configuration = []);

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
     * @return array an array of arrays formatted as inputs for HelperForm
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
