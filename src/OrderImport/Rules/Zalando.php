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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedOrder;
use Tools;

class Zalando extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $data = $apiOrder->toArray();

        if (key_exists('channelId', $data) === false) {
            return false;
        }
        if ($data['channelId'] !== 39452) {
            return false;
        }
        if (preg_match('#^zalando#', Tools::strtolower($apiOrder->getChannel()->getName())) === false) {
            return false;
        }

        return true;
    }

    public function afterOrderCreation($params)
    {
        $sfOrder = ShoppingfeedOrder::getByIdOrder($params['id_order']);
        $apiOrder = $params['apiOrder'];
        $data = $apiOrder->toArray();
        $additionalFields = $data['additionalFields'];
        $items = $data['items'];

        $zalando_products = [];

        foreach ($items as $item) {
            $zalando_products[] = sprintf(
                '%s : %s - %s',
                $item['name'],
                $item['additionalFields']['order_item_id'],
                $item['additionalFields']['article_id']
            );
        }
        $sfOrder->zalando_customer = $additionalFields['customer_number'];
        $sfOrder->zalando_products = Tools::jsonEncode($zalando_products);
        $sfOrder->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If an order came from Zalando and channelId equal to 39452.', 'Zalando');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Addition of Zalando-specific fields in PrestaShop invoices', 'ZalandoColissimo');
    }
}
