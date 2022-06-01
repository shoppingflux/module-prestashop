<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace Tests\OrderSync;

use DateTime;
use Db;
use DbQuery;
use PHPUnit\Framework\TestCase;
use ShoppingfeedAddon\Services\TaskOrderCleaner;
use ShoppingfeedTaskOrder;

class TaskOrderCleanerTest extends TestCase
{
    public function testTaskOrderCleaner()
    {
        $sql = (new DbQuery())->select('count(*)')->from(ShoppingfeedTaskOrder::$definition['table']);
        $countTaskOrder = Db::getInstance()->getValue($sql);
        $taskOrderCleaner = new TaskOrderCleaner();

        $shoppingfeedTaskOrder = new ShoppingfeedTaskOrder();
        $shoppingfeedTaskOrder->action = ShoppingfeedTaskOrder::ACTION_SYNC_STATUS;
        $shoppingfeedTaskOrder->id_order = 1;
        $shoppingfeedTaskOrder->save();
        $taskOrderCleaner->clean();
        $this->assertEquals(Db::getInstance()->getValue($sql), $countTaskOrder + 1);

        $shoppingfeedTaskOrder->date_add = (new DateTime())->modify('-8 day')->format('Y-m-d H:i:s');
        $shoppingfeedTaskOrder->save();
        $taskOrderCleaner->clean();

        $this->assertEquals(Db::getInstance()->getValue($sql), $countTaskOrder);
    }
}
