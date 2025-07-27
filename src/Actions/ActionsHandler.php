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

namespace ShoppingfeedAddon\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Hook;
use ShoppingfeedClasslib\Actions\ActionsHandler as DefaultActionHandler;

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
            $className = \Tools::ucfirst($className) . 'Actions';
            if (!preg_match('/^[a-zA-Z]+$/', $className)) {
                throw new \Exception($className . '" class name not valid "');
            }
            include_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/actions/' . $className . '.php';
        }

        $moduleId = \Module::getModuleIdByName('shoppingfeed');
        $hookResult = \Hook::exec(self::PROCESS_OVERRIDE_HOOK, ['className' => $className], $moduleId, true, false);
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
