<?php
/**
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

namespace ShoppingfeedClasslib\Db;

class DbSchema
{
    /**
     * @var ShoppingfeedClasslib\Db\ObjectModelDefinition
     */
    protected $def;

    /**
     * Table (internal) ID
     *
     * @var string
     */
    protected $id;

    /**
     * Register ShoppingfeedClasslib\Db\ObjectModelDefinition and table (internal) ID
     * @param ShoppingfeedClasslib\Db\ObjectModelDefinition $def
     * @param string                $id
     */
    public function __construct($def, $id)
    {
        $this->def = $def;
        $this->id  = $id;
    }

    /**
     * Map table properties
     * @param ShoppingfeedClasslib\Db\DbTable $table
     * @return ShoppingfeedClasslib\Db\DbTable
     */
    public function map($table)
    {
        return $table
            ->setName($this->def->getName($this->id))
            ->setEngine($this->def->getEngine($this->id))
            ->setCharset($this->def->getCharset($this->id))
            ->setCollation($this->def->getCollation($this->id))
            ->setColumns($this->def->getColumns($this->id))
            ->setKeyPrimary($this->def->getKeyPrimary($this->id))
            ->setKeysSimple($this->def->getKeysSimple($this->id))
            ->setKeysUnique($this->def->getKeysUnique($this->id))
            ->setKeysFulltext($this->def->getKeysFulltext($this->id))
            ->setKeysForeign($this->def->getKeysForeign($this->id))
            ;
    }
}
