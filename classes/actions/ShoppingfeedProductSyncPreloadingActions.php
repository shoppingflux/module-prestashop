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

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingfeedClasslib\Actions\DefaultActions;

class ShoppingfeedProductSyncPreloadingActions extends DefaultActions
{
    public function getBatch()
    {
        $stockSyncMax = Configuration::get(ShoppingFeed::STOCK_SYNC_MAX_PRODUCTS);
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sfP = new ShoppingfeedPreloading();
        $sql = new DbQuery();
        $sql->select('id_product')
            ->from(Product::$definition['table'] . '_shop')
            ->where('id_shop = ' . $this->conveyor['id_shop'])
            ->where('active = 1')
        ;
        $db->delete(
            ShoppingfeedPreloading::$definition['table'],
            sprintf('product_id NOT IN(%s) AND shop_id = %d', (string)$sql, $this->conveyor['id_shop']),
            $stockSyncMax
        );
        $stockSyncMax -= $db->Affected_Rows();
        if ($stockSyncMax < 1) {

            return true;
        }
        $sql->where(
            sprintf(
                'id_product NOT IN(SELECT product_id FROM %s WHERE shop_id = %d)',
                _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table'],
                $this->conveyor['id_shop'])
            )
            ->limit(Configuration::get(ShoppingFeed::STOCK_SYNC_MAX_PRODUCTS));
        $result = $db->executeS($sql);
        foreach ($result as $row) {
            $sfP->saveProduct($row['id_product'], $this->conveyor['id_shop']);
        }

        return true;
    }

    public static function getLogPrefix($id_shop = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Preloading shop:%s]', 'ShoppingfeedProductSyncPreloadingActions'),
            $id_shop
        );
    }
}