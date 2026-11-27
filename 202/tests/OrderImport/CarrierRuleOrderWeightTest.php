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

namespace Tests\OrderImport;

use PHPUnit\Framework\TestCase;
use ShoppingfeedAddon\CarrierRule\OrderWeightCalculator;

/**
 * Carrier selection rules: order weight (#34133)
 */
class CarrierRuleOrderWeightTest extends TestCase
{
    /** @var int */
    private $idCombination;

    /** @var array original weights to restore: [table => [where => weight]] */
    private $originalWeights = [];

    protected function setUp(): void
    {
        $this->idCombination = (int) \Product::getDefaultAttribute(1);
        $this->setWeight('product', 'id_product = 1', 1.5);
        $this->setWeight('product', 'id_product = 8', 2);
        $this->setWeight('product_attribute', 'id_product_attribute = ' . $this->idCombination, 0.25);
        $this->setWeight('product_attribute_shop', 'id_product_attribute = ' . $this->idCombination, 0.25);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalWeights as $table => $weights) {
            foreach ($weights as $where => $weight) {
                \Db::getInstance()->update($table, ['weight' => $weight], $where);
            }
        }
    }

    public function testSimpleProductTimesQuantity(): void
    {
        $weight = (new OrderWeightCalculator())->calculate(
            [$this->item('8', 3)],
            ['8' => $this->product(8, null)]
        );

        $this->assertEqualsWithDelta(6.0, $weight, 0.0001);
    }

    public function testCombinationWeightIsAnImpact(): void
    {
        $this->assertGreaterThan(0, $this->idCombination);

        $weight = (new OrderWeightCalculator())->calculate(
            [$this->item('1_' . $this->idCombination, 2)],
            ['1_' . $this->idCombination => $this->product(1, $this->idCombination)]
        );

        $this->assertEqualsWithDelta(3.5, $weight, 0.0001);
    }

    public function testUnmappedItemCountsZero(): void
    {
        $weight = (new OrderWeightCalculator())->calculate(
            [$this->item('8', 1), $this->item('unknown', 5)],
            ['8' => $this->product(8, null)]
        );

        $this->assertEqualsWithDelta(2.0, $weight, 0.0001);
    }

    private function item($reference, $quantity)
    {
        $item = new \stdClass();
        $item->reference = $reference;
        $item->quantity = $quantity;

        return $item;
    }

    private function product($idProduct, $idProductAttribute)
    {
        $product = new \Product($idProduct, true, null, 1);
        $product->id_product_attribute = $idProductAttribute;

        return $product;
    }

    private function setWeight($table, $where, $weight)
    {
        $this->originalWeights[$table][$where] = \Db::getInstance()->getValue(
            'SELECT weight FROM ' . _DB_PREFIX_ . $table . ' WHERE ' . $where
        );
        \Db::getInstance()->update($table, ['weight' => $weight], $where);
    }
}
