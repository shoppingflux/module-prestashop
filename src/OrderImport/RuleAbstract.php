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

use OrderState;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This abstract class represents a default specific rule to be applied during an SF order
 * import
 */
abstract class RuleAbstract implements RuleInterface
{
    protected $configuration;

    /**
     * @inheritdoc
     */
    public function __construct($configuration = array())
    {
        if (empty($configuration)) {
            $configuration = $this->getDefaultConfiguration();
        }
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    abstract public function isApplicable(\ShoppingFeed\Sdk\Api\Order\OrderResource $apiOrder);

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultConfiguration()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationSubform()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    protected function l($msg, $domain)
    {
        return \Translate::getModuleTranslation('shoppingfeed', $msg, $domain);
    }

    protected function isOrderStateValid($idOrderState)
    {
        try {
            $orderState = new OrderState((int)$idOrderState);
        } catch (\Throwable $e) {
            return false;
        }

        return Validate::isLoadedObject($orderState);
    }

    /**
     * @inheritdoc
     */
    abstract public function getDescription();

    /**
     * @inheritdoc
     */
    abstract public function getConditions();
}
