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
 *
 * @version   release/2.3.1
 */

namespace ShoppingfeedAddon\Actions;

use Hook;
use Module;
use ShoppingfeedClasslib\Actions\ActionsHandler as DefaultActionHandler;
use Tools;

/**
 * Actions Handler extends from default to keep in memory classAction
 * Usefull start a process in the front controller and go on in the
 * validateOrder hook
 */
class ActionsHandler extends DefaultActionHandler
{
    /**
     * Values classAction by the classes
     *
     * @var ShoppingfeedClasslib\Actions\DefaultActions
     */
    protected $classAction;

    /**
     * Return data in conveyor
     *
     * @return array
     */
    public function getConveyor()
    {
        if (empty($this->classAction) === false) {
            return $this->classAction->getConveyor();
        }

        return $this->conveyor;
    }

    /**
     * Process the action call back of cross modules
     *
     * @param string $className Name of the actions chain / Namespaced classname
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function process($className)
    {
        if (!class_exists($className)) {
            $className = Tools::ucfirst($className) . 'Actions';
            if (!preg_match('/^[a-zA-Z]+$/', $className)) {
                throw new \Exception($className . '" class name not valid "');
            }
            include_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/' . $className . '.php';
        }

        $moduleId = Module::getModuleIdByName('shoppingfeed');
        $hookResult = Hook::exec(self::PROCESS_OVERRIDE_HOOK, ['className' => $className], $moduleId, true, false);
        if (!empty($hookResult) && !empty($hookResult['shoppingfeed'])) {
            $className = $hookResult['shoppingfeed'];
        }

        if (class_exists($className) === false && empty($this->classAction) === true) {
            throw new \Exception($className . '" class not defined "');
        }
        if (class_exists($className) === true && empty($this->classAction) === true) {
            /* @var DefaultActions $this->classAction */
            $this->classAction = new $className();
        }
        $this->classAction->setModelObject($this->modelObject);
        $this->classAction->setConveyor($this->conveyor);

        foreach ($this->actions as $action) {
            if (!is_callable([$this->classAction, $action], false, $callableName)) {
                continue;
            }
            if (!call_user_func_array([$this->classAction, $action], [])) {
                $this->setConveyor($this->classAction->getConveyor());

                return false;
            }
        }

        $this->setConveyor($this->classAction->getConveyor());

        return true;
    }
}
