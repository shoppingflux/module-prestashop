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

use ShoppingfeedAddon\Services\SfTools;
use ShoppingfeedClasslib\Install\ModuleInstaller;

function upgrade_module_1_11_0($module)
{
    /**
     * @var Shoppingfeed $module
     */
    $installer = new ModuleInstaller($module);
    $installer->installObjectModel(ShoppingfeedToken::class);

    $tokens = Db::getInstance()->executeS((new DbQuery())->from(ShoppingfeedToken::$definition['table']));

    if ($tokens) {
        foreach ($tokens as $tokenData) {
            $sft = new ShoppingfeedToken();
            $sft->hydrate($tokenData);

            if (empty($sft->feed_key)) {
                $sft->feed_key = (new SfTools())->hash(uniqid());
                $sft->save();
            }
        }
    }

    return true;
}
