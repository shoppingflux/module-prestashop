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
 * This abstract class represents a specific rule to be applied during an SF order
 * import
 */
abstract class RuleAbstract
{
    protected $configuration;
    
    public function __construct($configuration = array())
    {
        if (empty($configuration)) {
            $configuration = $this->getDefaultConfiguration();
        }
        $this->configuration = $configuration;
    }
    
    /**
     * Returns true if a rule is applicable to an SF order
     * @return bool
     */
    abstract public function isApplicable(\ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder);
    
    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    /**
     * Gets default configuration for a rule
     *
     * @return array
     */
    protected function getDefaultConfiguration()
    {
        return array();
    }
    
    /**
     * Gets the configuration subform for a rule.
     *
     * @return array an array of arrays formatted as inputs for HelperForm.
     */
    public function getConfigurationSubform()
    {
        return array();
    }
    
    abstract public function getDescription();
    
    abstract public function getConditions();
}
