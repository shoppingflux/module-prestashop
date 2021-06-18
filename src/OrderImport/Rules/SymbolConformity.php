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

namespace ShoppingfeedAddon\OrderImport\Rules;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Tools;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource;

use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class SymbolConformity extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        if ($this->configuration['enabled']) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getConditions()
    {
        return $this->l('All orders', 'SymbolConformity');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->l('By activating this option, special characters prohibited by Prestashop will be removed from the order information. Be careful, this could falsify the delivery or billing data.', 'SymbolConformity');
    }

    /**
     * @inheritdoc
     */
    public function getConfigurationSubform()
    {
        return array(
            array(
                'type' => 'switch',
                'label' =>
                $this->l('Conformity of characters', 'SymbolConformity'),
                'desc' =>
                    $this->l('By activating this option, special characters prohibited by Prestashop will be removed from the order information. Be careful, this could falsify the delivery or billing data.', 'SymbolConformity'),
                'name' => 'enabled',
                'is_bool' => true,
                'values' => array(
                    array(
                        'value' => 1,
                    ),
                    array(
                        'value' => 0,
                    )
                ),
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConfiguration()
    {
        return array('enabled' => false);
    }
}
