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

use Db;
use Module;
use ShoppingfeedAddon\Actions\ActionsHandler;
use ShoppingfeedClasslib\Registry;

/**
 * Order Rules CDiscount Test
 */
class OrderImportManoManoDpdRelaisWithDoubleTaxOnProductTest extends AbstractOrdeTestCase
{
    public function testAddDpdCarrierAssociation()
    {
        $dpdFrance = Module::getInstanceByName('dpdfrance');
        $dpdFrance->createCarrier($dpdFrance->config_carrier_world, 'world');
        $contextData = [
            'carrierReference' => 0,
        ];
        //Find dpd carrier
        $query = (new \DbQuery())
            ->from('carrier')
            ->where('active = 1')
            ->where('deleted = 0')
            ->where('external_module_name = "dpdfrance"')
            ->select('id_reference');

        $carrierReference = (int) Db::getInstance()->getValue($query);
        $this->assertTrue($carrierReference > 0);

        if (empty($carrierReference)) {
            return $contextData;
        }

        $contextData['carrierReference'] = $carrierReference;
        $associationData = [
            'name_marketplace' => 'Monechelle',
            'name_carrier' => 'DPD Pickup - Livraison en point relais',
            'id_carrier_reference' => $carrierReference,
            'is_new' => 0,
            'date_add' => '2022-02-17 00:00:00',
            'date_upd' => '2022-02-17 00:00:00',
        ];
        //Add an association
        $isAdded = Db::getInstance()->insert('shoppingfeed_carrier', $associationData, false, true, Db::REPLACE);
        $this->assertTrue($isAdded);

        return $contextData;
    }

    /**
     * @depends testAddDpdCarrierAssociation
     *
     * @return mixed
     */
    public function testImport($contextData)
    {
        $apiOrder = $this->getOrderRessourceFromDataset('order-manomano.json');

        $handler = new ActionsHandler();
        $handler->addActions(
            'registerSpecificRules',
            'verifyOrder',
            'createOrderCart',
            'validateOrder',
            'postProcess'
        );

        $handler->setConveyor(
            [
                'id_shop' => 1,
                'id_token' => 1,
                'id_lang' => 1,
                'apiOrder' => $apiOrder,
            ]
        );
        Registry::set('shoppingfeedOrderImportHandler', $handler);

        $processResult = $handler->process('shoppingfeedOrderImport');

        $this->assertTrue($processResult);

        $contextData['conveyor'] = $handler->getConveyor();

        return $contextData;
    }

    /**
     * @depends testImport
     *
     * @return mixed
     */
    public function testCustomer($contextData)
    {
        $customer = $contextData['conveyor']['customer'];
        $this->assertEquals($customer->lastname, 'lafont');
        $this->assertEquals($customer->firstname, 'patrice');

        return $contextData;
    }

    /**
     * @depends testCustomer
     *
     * @return mixed
     */
    public function testOrderDetails($contextData)
    {
        $sfOrder = $contextData['conveyor']['sfOrder'];
        $this->assertEquals($sfOrder->name_marketplace, 'Monechelle');
        $this->assertEquals($sfOrder->id_order_marketplace, 'MANOMANO-TEST-123');

        $psOrder = new \Order((int) $contextData['conveyor']['id_order']);

        $this->assertEquals($psOrder->current_state, _PS_OS_PAYMENT_);
        $this->assertEquals($psOrder->payment, 'monechelle');
        $this->assertEquals($psOrder->module, 'sfpayment');
        $this->assertEquals($psOrder->total_discounts, 0.000000);
        $this->assertEquals($psOrder->total_paid, 14.600000);
        $this->assertEquals($psOrder->total_paid_tax_incl, 14.600000);
        $this->assertEquals($psOrder->total_paid_real, 14.600000);
        $this->assertEquals($psOrder->total_products_wt, 9.800000);
        $this->assertEquals($psOrder->total_shipping, 4.800000);
        $this->assertEquals($psOrder->total_wrapping, 0.000000);

        $invoices = $psOrder->getInvoicesCollection();
        $this->assertEquals(count($invoices), 1);

        foreach ($invoices as $invoice) {
            $this->assertEquals($invoice->total_discount_tax_incl, 0.000000);
            $this->assertEquals($invoice->total_paid_tax_incl, 14.600000);
            $this->assertEquals($invoice->total_products_wt, 9.800000);
            $this->assertEquals($invoice->total_shipping_tax_incl, 4.800000);
            $this->assertEquals($invoice->total_wrapping_tax_incl, 0.000000);
        }

        //refs#34500
        $detail = $psOrder->getProductsDetail()[0];
        $detailTaxes = \OrderDetail::getTaxListStatic($detail['id_order_detail']);
        $this->assertEquals($detailTaxes[0]['total_amount'], 1.620000);
        $this->assertEquals($detailTaxes[0]['id_tax'], 1);
        $this->assertEquals($detailTaxes[1]['total_amount'], 0.010000);
        $this->assertEquals($detailTaxes[1]['id_tax'], 40);

        return $contextData;
    }

    /**
     * @depends testOrderDetails
     *
     * @return void
     */
    public function testCarrier($contextData)
    {
        $psOrder = new \Order((int) $contextData['conveyor']['id_order']);
        $carrier = new \Carrier($psOrder->id_carrier);
        $this->assertEquals($carrier->id_reference, $contextData['carrierReference']);

        $dpdShippingService = \Dpdfrance::getService($psOrder, false);
        $expectedValue = 'REL';
        $this->assertEquals($expectedValue, $dpdShippingService);
    }
}
