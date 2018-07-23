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
 * @version   release/1.0.1
 */

/**
 * @desc Actions Handler
 */
class ShoppingfeedHandler
{

    /*
    * @desc modelObject
    */
    protected $modelObject;

    /*
    * @desc Values conveyored by the classes
    */
    protected $conveyor = array();

    /*
    * Set an modelObject
    * @param $modelObject
    *
    * @return this
    */
    public function setModelObject($modelObject)
    {
        $this->modelObject = $modelObject;

        return $this;
    }

    /**
    * Set the conveyor
    * @param $conveyor
    *
    * @return this
    */
    public function setConveyor($conveyorData)
    {
        $this->conveyor = $conveyorData;

        return $this;
    }

    /**
    * @desc Return data in this->conveyor
    */
    public function getConveyor()
    {
        return $this->conveyor;
    }

    /**
    * @desc Call sevral actions
    * @param $actions
    */
    public function addActions($actions)
    {
        $this->actions = func_get_args();
        return $this;
    }

    /**
    * @desc process the action call back of cross modules
    * @param $chain name of the actions chain
    *
    * @return boolean
    */
    public function process($chain)
    {
        $className = ucfirst($chain).'Actions';
        $overridePath = _PS_OVERRIDE_DIR_ . 'modules/shoppingfeed/classes/actions/'.$className.'.php';
        if (file_exists($overridePath)) {
            $className .= 'Override';
            include_once($overridePath);
        } else {
            include_once(__DIR__ . '/../../classes/actions/'.ucfirst($chain).'Actions.php');
        }
        if (class_exists($className)) {
            $classAction = new $className;
            $classAction->setModelObject($this->modelObject);
            $classAction->setConveyor($this->conveyor);
            foreach ($this->actions as $action) {
                if (!is_callable(array($classAction, $action), false, $callable_name)) {
                    continue;
                }
                if (!call_user_func_array(array($classAction, $action), array())) {
                    return false;
                }
            }
            $this->setConveyor($classAction->getConveyor());
        } else {
            echo ucfirst($action).'Actions not defined';
            exit;
        }

        return true;
    }

}
