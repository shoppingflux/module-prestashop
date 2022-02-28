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

use Carrier;
use ColissimoCartPickupPoint;
use ColissimoPickupPoint;
use ColissimoService;
use ColissimoTools;
use Country;
use Exception;
use Module;
use ShoppingFeed\Sdk\Api\Order\OrderResource;
use ShoppingfeedAddon\OrderImport\RuleAbstract;
use ShoppingfeedAddon\OrderImport\RuleInterface;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use Validate;

abstract class AbstractColissimo extends RuleAbstract implements RuleInterface
{
    const COLISSIMO_MODULE_NAME = 'colissimo';

    protected $colissimo;

    protected $fileName;

    public function __construct($configuration = [])
    {
        parent::__construct($configuration);

        $this->colissimo = Module::getInstanceByName(self::COLISSIMO_MODULE_NAME);
    }

    protected function isModuleColissimoEnabled()
    {
        try {
            return Validate::isLoadedObject($this->colissimo) && $this->colissimo->active;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retrieve and set the Colissimo carrier, and skip SF carrier creation.
     *
     * @throws Exception
     */
    public function onCarrierRetrieval($params)
    {
        if (class_exists(ColissimoService::class) === false || class_exists(ColissimoTools::class) === false) {
            return;
        }

        /** @var OrderResource $apiOrder */
        $apiOrder = $params['apiOrder'];

        $logPrefix = sprintf(
            $this->l('[Order: %s]', $this->getFileName()),
            $apiOrder->getId()
        );
        $logPrefix .= '[' . $apiOrder->getReference() . '] ' . self::class . ' | ';

        ProcessLoggerHandler::logInfo(
            $logPrefix . $this->l('Setting Colissimo carrier.', $this->getFileName()),
            'Order'
        );

        // Retrieve necessary order data
        $productCode = $this->getProductCode($apiOrder);
        $shippingAddress = $apiOrder->getShippingAddress();
        $destinationType = ColissimoTools::getDestinationTypeByIsoCountry($shippingAddress['country']);

        // Retrieve ColissimoService using order data
        $idColissimoService = ColissimoService::getServiceIdByProductCodeDestinationType(
            $productCode,
            $destinationType
        );

        if (!$idColissimoService) {
            throw new Exception($logPrefix . sprintf($this->l('Could not retrieve ColissimoService from productCode %s and destinationType %s.', $this->getFileName()), $productCode, $destinationType));
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Retrieved ColissimoService %s from productCode %s and destinationType %s.', $this->getFileName()),
                $idColissimoService,
                $productCode,
                $destinationType
            ),
            'Order'
        );

        // Retrieve colissimo Carrier from ColissimoService
        $colissimoService = new ColissimoService($idColissimoService);
        $colissimoCarrier = Carrier::getCarrierByReference($colissimoService->id_carrier);

        if (!Validate::isLoadedObject($colissimoCarrier)) {
            throw new Exception($logPrefix . sprintf($this->l('Could not retrieve Carrier with id_reference %s from ColissimoService %s with productCode %s and destinationType %s.', $this->getFileName()), $colissimoService->id_carrier, $colissimoService->id, $productCode, $destinationType));
        }

        if (!$colissimoCarrier->active || $colissimoCarrier->deleted) {
            throw new Exception($logPrefix . sprintf($this->l('Retrieved Carrier with id_reference %s from ColissimoService %s with productCode %s and destinationType %s is inactive or deleted.', $this->getFileName()), $colissimoService->id_carrier, $colissimoService->id, $productCode, $destinationType));
        }

        ProcessLoggerHandler::logInfo(
            $logPrefix .
            sprintf(
                $this->l('Retrieved Colissimo carrier %s from ColissimoService %s.', $this->getFileName()),
                $colissimoService->id_carrier,
                $colissimoService->id
            ),
        );

        // Use retrieved carrier and skip SF carrier creation; Colissimo should decide by itself which carrier should be used
        $params['carrier'] = $colissimoCarrier;
        $params['skipSfCarrierCreation'] = true;

        return true;
    }

    protected function getFileName()
    {
        if (empty($this->fileName)) {
            $this->fileName = pathinfo(__FILE__, PATHINFO_FILENAME);
        }

        return $this->fileName;
    }

    abstract protected function getProductCode(OrderResource $apiOrder);

    abstract protected function getPointId(OrderResource $apiOrder);
}
