<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommerce is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommerce
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommerce est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 * @version   feature/34626_diagnostic
 */

namespace ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Model;

class ModuleConfigModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $stubs = [];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ModuleConfigModel
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getStubs()
    {
        return $this->stubs;
    }

    /**
     * @param array $stubs
     * @return ModuleConfigModel
     */
    public function setStubs($stubs)
    {
        $this->stubs = $stubs;
        return $this;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     * @return ModuleConfigModel
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
        return $this;
    }
}
