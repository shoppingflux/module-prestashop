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
 * @desc Design pattern Registry
 */
class ShoppingfeedRegistry {

    private static $_registry = null;

    /**
     * @desc Design pattern Registry
     */
    public static function getInstance() {
        if (self::$_registry === null) {
            self::$_registry = new ShoppingfeedRegistry;
        }
        return self::$_registry;
    }

    /**
     * @desc get a variable in the Registry
     *
     * @return string
     */

    public static function get($index)    {
        $instance = self::getInstance();
        if (!$instance->offsetExists($index)) {
            return false;
        }
        return $instance->values[$index];
    }

    /**
     * @desc set a variable in the Registry
     * @param string $index
     * @param string $value
     */
    public static function set($index, $value) {
        $instance = self::getInstance();
        $instance->values[$index]= $value;
    }

    /**
     * @desc check if var exist in the registry
     * @param string $index
     *
     * @return boolean
     */

    public static function isRegistered($index)   {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->offsetExists($index);
    }

    /**
     * @desc check if offsetExists
     * @param string $index
     *
     * @return boolean
     */

    public function offsetExists($index) {
        if (false === isset($this->values)) {
            return false;
        }
        return array_key_exists($index, $this->values);
    }

    /**
     * @desc increment a counter in the Registry
     * @param string $index
     * @param string $value
     */
    public static function increment($index, $value=1) {
        $instance = self::getInstance();
        if (self::isRegistered($index)) {
            $instance->values[$index] += $value;
        } else {
            $instance->values[$index] = $value;
        }
    }

    /**
     * @desc decrement a counter in the Registry
     * @param string $index
     * @param string $value
     */
    public static function decrement($index, $value=1) {
        $instance = self::getInstance();
        if (self::isRegistered($index)) {
            $instance->values[$index] -= $value;
        } else {
            $instance->values[$index] = $value * (-1);
        }
    }
}
