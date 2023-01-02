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
            if (empty($apiProduct['additionalFields']) === true) {
                continue;
            }
            $psProduct = $params['prestashopProducts'][$apiProduct['reference']];
            $query = new DbQuery();
            $query->select('od.id_order_detail, od.product_name')
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
                substr($productOrderDetail['product_name'], 0, 188), // fix size name product to 250 maximum
                $apiProduct['additionalFields']['order_item_id'],
                $apiProduct['additionalFields']['article_id']
            );

            Db::getInstance()->update(
                'order_detail',
                ['product_name' => pSQL($product_name)],
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
