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
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence
 * est expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

/**
 * @desc: parameters for this module. Please complete also config_prod.php
 */

/**
 * @desc: class Loader
 */
if (!class_exists('TotLoader')) {
    class TotLoader
    {
        /**
        * @desc get classname with module name
        * @params string $namespace
        *
        * @return true
        */
        private static function getClassname($namespace) {
            $moduleName = substr($namespace, 0, strpos($namespace, '\\'));
            $pathExplode = explode('\\', $namespace);
            $classname = ucfirst($moduleName) . ucfirst(array_pop($pathExplode));

            return $classname;
        }

        /**
        * @desc require_one a php class file
        * @params string $namespace
        *
        * @return string
        */
        public static function import($namespace) {
            $path = _PS_ROOT_DIR_  . '/' . basename(_MODULE_DIR_) . '/' . str_replace('\\', '/', $namespace) . '.php';
            if (!file_exists($path)) {
                throw new Exception('Loader error : File ' . $path . ' doesn\'t exist');
            }

            require_once($path);

            return self::getClassname($namespace);
        }

        /**
        * @desc: instantiate a classname
        */
        public static function getInstance($namespace) {
            self::import($namespace);
            $classname = self::getClassname($namespace);

            return new $classname;
        }
    }
}

