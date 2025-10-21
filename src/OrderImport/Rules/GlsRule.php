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

use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\GLS\Adapter;
use ShoppingfeedAddon\OrderImport\GLS\AdapterInterface;
use ShoppingfeedAddon\OrderImport\GLS\CartCarrierAssociation;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;

class GlsRule extends RuleAbstract implements RuleInterface
{
    protected $gls;

    /** @var AdapterInterface */
    protected $glsAdapter;

    protected $logPrefix = '';

    public function __construct($configuration = [], $id_shop = null)
    {
        parent::__construct($configuration, $id_shop);

        $this->gls = \Module::getInstanceByName('nkmgls');
        $this->glsAdapter = $this->getDefaultGlsAdapter();
    }

    public function isApplicable(OrderResource $apiOrder)
    {
        $this->logPrefix = sprintf(
            $this->l('[Order: %s]', 'GlsRule'),
            $apiOrder->getId()
        );
        $this->logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        if (false == $this->isModuleGlsInstalled()) {
            return false;
        }

        return true;
    }

    public function getDescription()
    {
        return $this->l('Linking GLS pickup point to cart.', 'GlsRule');
    }

    public function getConditions()
    {
        return $this->l('If the order should be delivered by GLS carrier and the module "nkmgls" is installed', 'GlsRule');
    }

    protected function isModuleGlsInstalled()
    {
        return \Validate::isLoadedObject($this->gls) && $this->gls->active;
    }

    public function afterCartCreation($params)
    {
        /** @var \Cart $cart */
        $cart = $params['cart'];

        if (empty($cart->id_carrier)) {
            return;
        }
        $carrier = new \Carrier($cart->id_carrier);
        if ($carrier->external_module_name != $this->gls->name) {
            return;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            $this->l('Rule triggered.', 'GlsRule')
        );

        $cartCarrierAssociation = $this->initCartCarrierAssociation();

        try {
            $result = $cartCarrierAssociation->create($cart, $this->getRelayId($params['apiOrder']));
        } catch (\Exception $e) {
            ProcessLoggerHandler::logError(
                $this->logPrefix .
                sprintf(
                    $this->l('Fail linking GLS pickup point %s to cart %d. Detail message: %s', 'GlsRule'),
                    $this->getRelayId($params['apiOrder']),
                    (int) $cart->id,
                    $e->getMessage()
                ),
                'Cart',
                $cart->id
            );

            return;
        }

        if (false == $result) {
            ProcessLoggerHandler::logInfo(
                $this->logPrefix .
                sprintf(
                    $this->l('Fail linking GLS pickup point %s to cart %d.', 'GlsRule'),
                    $this->getRelayId($params['apiOrder']),
                    (int) $cart->id
                ),
                'Cart',
                $cart->id
            );

            return;
        }

        ProcessLoggerHandler::logInfo(
            $this->logPrefix .
            sprintf(
                $this->l('Linking GLS pickup point %s to cart %s.', 'GlsRule'),
                $this->getRelayId($params['apiOrder']),
                $cart->id
            ),
            'Cart',
            $cart->id
        );
    }

    protected function getRelayId(OrderResource $apiOrder)
    {
        $apiOrderData = $apiOrder->toArray();

        if (empty($apiOrderData['shippingAddress']['relayId']) || empty($apiOrderData['shippingAddress']['country'])) {
            return '';
        }

        return $apiOrderData['shippingAddress']['relayId'];
    }

    protected function getDefaultGlsAdapter()
    {
        return new Adapter();
    }

    protected function initCartCarrierAssociation()
    {
        return new CartCarrierAssociation($this->glsAdapter);
    }

    public function setGlsAdapter(AdapterInterface $adapter)
    {
        $this->glsAdapter = $adapter;

        return $this;
    }
}
