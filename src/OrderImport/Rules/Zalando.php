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

use Db;
use DbQuery;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use Tools;

class Zalando extends RuleAbstract implements RuleInterface
{
    public function isApplicable(OrderResource $apiOrder)
    {
        $data = $apiOrder->toArray();

        if (key_exists('channelId', $data) === false) {
            return false;
        }
        if (preg_match('#^zalando#', Tools::strtolower($apiOrder->getChannel()->getName())) === false) {
            return false;
        }

        return true;
    }

    public function afterOrderCreation($params)
    {
        $apiOrder = $params['apiOrder'];
        $data = $apiOrder->toArray();
        foreach ($data['items'] as &$apiProduct) {
            $psProduct = $params['prestashopProducts'][$apiProduct['reference']];
            $query = new DbQuery();
            $query->select('od.id_order_detail')
                ->from('order_detail', 'od')
                ->where('od.id_order = ' . (int) $params['id_order'])
                ->where('od.product_id = ' . (int) $psProduct->id)
            ;
            if ($psProduct->id_product_attribute) {
                $query->where('product_attribute_id = ' . (int) $psProduct->id_product_attribute);
            }
            $productOrderDetail = Db::getInstance()->getRow($query);
            $product_name = sprintf(
                '%s : %s - %s',
                substr($apiProduct['name'], 0, 188), // fix size name product to 250 maximum
                $apiProduct['additionalFields']['order_item_id'],
                $apiProduct['additionalFields']['article_id']
            );

            Db::getInstance()->update(
                'order_detail',
                ['product_name' => $product_name],
                '`id_order_detail` = ' . (int) $productOrderDetail['id_order_detail']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        return $this->l('If an order came from Zalando.', 'Zalando');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->l('Addition of Zalando-specific fields in PrestaShop invoices', 'Zalando');
    }
}
