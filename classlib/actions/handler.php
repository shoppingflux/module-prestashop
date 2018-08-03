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
 * @version   release/1.2.0
 */

/**
 * Actions Handler
 */
class ShoppingfeedHandler
{
    /**
     * @var ObjectModel $modelObject
     */
    protected $modelObject;

    /**
     * Values conveyored by the classes
     *
     * @var array $conveyor
     */
    protected $conveyor = array();

    /**
     * List of actions
     *
     * @var array $actions
     */
    protected $actions;

    /**
     * Set an modelObject
     *
     * @param ObjectModel $modelObject
     * @return $this
     */
    public function setModelObject($modelObject)
    {
        $this->modelObject = $modelObject;

        return $this;
    }

    /**
     * Set the conveyor
     *
     * @param array $conveyorData
     * @return $this
     */
    public function setConveyor($conveyorData)
    {
        $this->conveyor = $conveyorData;

        return $this;
    }

    /**
     * Return data in conveyor
     *
     * @return array
     */
    public function getConveyor()
    {
        return $this->conveyor;
    }

    /**
     * Call sevral actions
     *
     * @param mixed $actions
     * @return $this
     */
    public function addActions($actions)
    {
        $this->actions = func_get_args();
        return $this;
    }

    /**
     * Process the action call back of cross modules
     *
     * @param string $chain Name of the actions chain
     * @return bool
     */
    public function process($chain)
    {
        include_once _PS_MODULE_DIR_.'shoppingfeed/classes/actions/'.Tools::ucfirst($chain).'Actions.php';
        $className = Tools::ucfirst($chain).'Actions';
        $overridePath = _PS_OVERRIDE_DIR_.'modules/shoppingfeed/classes/actions/'.$className.'.php';
        if (file_exists($overridePath)) {
            $className .= 'Override';
            include_once $overridePath;
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
            echo Tools::ucfirst($chain).'Actions not defined';
            exit;
        }

        return true;
    }
}
