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
function upgrade_module_1_8_0($module)
{
    $installer = new \ShoppingfeedClasslib\Install\ModuleInstaller($module);
    $installer->installObjectModel(ShoppingfeedPreloading::class);
    $installer->installObjectModel(ShoppingfeedOrder::class);

    try {
        $installer->registerHooks();
    } catch (Exception $e) { //for php version < 7.0
        Configuration::updateGlobalValue(Shoppingfeed::NEED_UPDATE_HOOK, 1);
    } catch (Throwable $e) {
        Configuration::updateGlobalValue(Shoppingfeed::NEED_UPDATE_HOOK, 1);
    }

    $sql = 'UPDATE `' . _DB_PREFIX_ . ShoppingfeedPreloading::$definition['table'] . '` SET etag = md5(CONCAT(CURRENT_DATE, content))';
    Db::getInstance()->execute($sql);

    return true;
}
