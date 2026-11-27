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

use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;

/**
 * Helpers shared by the carrier selection rules tests (#34133)
 */
trait CarrierRuleFixturesTrait
{
    /**
     * Each group is a list of [criterion, operator, value] triples
     */
    private function createRule($idToken, $position, $idCarrierReference, array ...$groups)
    {
        $rule = new \ShoppingfeedCarrierRule();
        $rule->id_shoppingfeed_token = $idToken;
        $rule->position = $position;
        $rule->id_carrier_reference = $idCarrierReference;
        $rule->setConditions(['groups' => array_map(
            function ($group) {
                return ['conditions' => array_map(
                    function ($condition) {
                        return ['criterion' => $condition[0], 'operator' => $condition[1], 'value' => $condition[2]];
                    },
                    $group
                )];
            },
            $groups
        )]);
        $this->assertTrue((bool) $rule->add());

        return $rule;
    }

    /**
     * An order id never imported before, so the order is not skipped as "already present"
     */
    private function uniqueOrderId()
    {
        do {
            $id = 7000000000 + mt_rand(1, 999999999);
        } while (\ShoppingfeedOrder::existsInternalId($id));

        return $id;
    }

    private function createHandler($apiOrder, array $actions)
    {
        $handler = new ActionsHandler();
        $handler->addActions(...$actions);
        $handler->setConveyor([
            'id_shop' => 1,
            'id_token' => 1,
            'id_lang' => 1,
            'apiOrder' => $apiOrder,
        ]);
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        return $handler;
    }

    private function setProductWeight($weight)
    {
        \Db::getInstance()->update('product', ['weight' => $weight], 'id_product = 8');
        // Product objects are cached for the whole PHPUnit process
        \Cache::clean('objectmodel_Product_*');
    }

    /**
     * A carrier owned by a carrier module (e.g. dpdfrance), so the tests don't
     * depend on another test creating one. Delete it in tearDown().
     */
    private function createModuleCarrier($moduleName)
    {
        $carrier = new \Carrier();
        $carrier->name = 'TEST-34133 ' . $moduleName;
        $carrier->is_module = true;
        $carrier->external_module_name = $moduleName;
        $carrier->active = true;
        $carrier->delay = [1 => 'test'];
        $this->assertTrue((bool) $carrier->add());

        // Carrier::add() sets id_reference in the database only: reload it
        return new \Carrier($carrier->id);
    }
}
