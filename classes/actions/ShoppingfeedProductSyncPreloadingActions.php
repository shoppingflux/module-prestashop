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
        $sfP = new ShoppingfeedPreloading();
        $sql = new DbQuery();
        $sql->select('id_product')
            ->from(Product::$definition['table'] . '_shop')
            ->where(sprintf('id_product NOT IN(select product_id from %s)', _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table']))
            ->where('id_shop = ' . $this->conveyor['id_shop'])
            ->limit(Configuration::get(ShoppingFeed::STOCK_SYNC_MAX_PRODUCTS));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($result as $row) {
            $sfP->saveProduct($row['id_product'], $this->conveyor['id_shop']);
        }
    }

    public static function getLogPrefix($id_shop = '')
    {
        return sprintf(
            Translate::getModuleTranslation('shoppingfeed', '[Preloading shop:%s]', 'ShoppingfeedProductSyncPreloadingActions'),
            $id_shop
        );
    }
}