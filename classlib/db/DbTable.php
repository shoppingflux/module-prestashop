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
 * @version   develop
 */

class ShoppingfeedDbTable
{
    /**
     * Key identifiers.
     *
     * @var int
     */
    const PRIMARY  = 1;
    const FOREIGN  = 2;
    const UNIQUE   = 3;
    const FULLTEXT = 4;

    /** @var Db */
    protected $db;
    /** @var string */
    protected $name;
    /** @var string */
    protected $engine;
    /** @var string */
    protected $charset;
    /** @var string */
    protected $collation;
    /** @var array */
    protected $columns;
    /** @var array */
    protected $schema;
    /** @var array */
    protected $keys;

    /**
     * Register Db
     * @param Db $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hydrate properties
     * @param ShoppingfeedDbSchema $schema
     * @return ShoppingfeedDbTable
     */
    public function hydrate($schema)
    {
        $this->schema = $schema;

        return $schema->map($this);
    }

    /**
     * Create table
     * @return bool
     */
    public function create()
    {
        $tableExists = $this->db->executeS("SHOW TABLES LIKE '$this->name'");
        if ($tableExists == false) {
            return $this->db->execute("CREATE TABLE IF NOT EXISTS `$this->name` (".
                implode(', ', array_merge($this->columns, $this->keys)).
            ") ENGINE=$this->engine CHARSET=$this->charset COLLATE=$this->collation;");
        }

        // table exists
        $alter = $this->alterFields();
        // @ToDo drop index only if he exists
        // $alterKeys = $this->alterKeys();
        // foreach ($alterKeys as $typeKey => $alterKey) {
        //     if (!empty($alterKey)) {
        //         if ($typeKey == 'KEY') {
        //             foreach ($alterKey as $key) {
        //                 $alter .= "DROP INDEX ".$key." ON `$this->name`;";
        //             }
        //         }

        //         $alter .= "ALTER TABLE `$this->name` ADD ".$typeKey." (".implode(', ', $alterKey).");";
        //     }
        // }

        if (!empty($alter)) {
            return $this->db->execute($alter);
        }
        return true;
    }

    /**
     * Alter table fields
     * @return string
     */
    private function alterFields()
    {
        $describe = $this->db->executeS("SHOW COLUMNS FROM `$this->name`");

        foreach ($describe as $key => $col) {
            $describe[$key]['modelDef'] = '`'.$col['Field'].'` '.strtoupper($col['Type']).' ';
            if ('NO' === $col['Null']) {
                $describe[$key]['modelDef'] .= 'NOT NULL ';
            }
            if (false === empty($col['Extra'])) {
                $describe[$key]['modelDef'] .= strtoupper($col['Extra']);
            }
        }

        $alterToSkip = array();
        $alterToExecute = array();
        $alters = array();
        foreach ($this->columns as $key => $column) {
            foreach ($describe as $col) {
                if (trim($column) === trim($col['modelDef'])) {
                    $alterToSkip[$key] = true;
                } elseif (false !== strpos($column, '`' . $col['Field'] . '`')) {
                    $alterToExecute[$key] = 'MODIFY';
                    $alters[$key] = "ALTER TABLE `$this->name` MODIFY $column;";
                }
            }
            if (empty($alterToExecute[$key]) && empty($alterToSkip[$key])) {
                $alterToExecute[$key]['action'] = 'ADD '.$column;
                $alters[$key] =  "ALTER TABLE `$this->name` ADD $column;";
            }
        }

        return implode("\r\n", $alters);
    }

    /**
     * Alter table keys
     * @return string
     */
    private function alterKeys()
    {
        $describe = $this->db->executeS("SHOW KEYS FROM `$this->name`");

        $altersArray = array();
        foreach ($this->keys as $modelDef) {
            $keyInfo = explode(' ', $modelDef);
            preg_match('#\((.*?)\)#', $modelDef, $info);

            $keys = explode(', ', $info[1]);
            foreach ($keys as $key) {
                $altersArray[$keyInfo[0]][] = trim(str_replace('`', '', $key));
            }
        }

        foreach ($altersArray as $key => $alter) {
            foreach ($alter as $k => $al) {
                foreach ($describe as $col) {
                    $TypeOfKey = 'UNIQUE';
                    if (empty($col['Non_unique']) && $col['Key_name'] === 'PRIMARY') {
                        $TypeOfKey = 'PRIMARY';
                    }

                    if ($al == $col['Column_name'] && $k == $TypeOfKey) {
                        unset($altersArray[$key][$k]);
                    }
                }
            }
        }

        return $altersArray;
    }

    /**
     * Drop table
     * @return bool
     */
    public function drop()
    {
        return $this->db->execute("DROP TABLE IF EXISTS `$this->name`;");
    }

    /**
     * @param string $name
     * @return ShoppingfeedDbTable
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $engine
     * @return ShoppingfeedDbTable
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @param string $charset
     * @return ShoppingfeedDbTable
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @param string $collation
     * @return ShoppingfeedDbTable
     */
    public function setCollation($collation)
    {
        $this->collation = $collation;

        return $this;
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setKeyPrimary($columns)
    {
        return $this->setKey($columns, static::PRIMARY);
    }

    /**
     * @param array $keys
     * @return ShoppingfeedDbTable
     */
    public function setKeysForeign($keys)
    {
        foreach ($keys as $columns) {
            $this->setKeyForeign($columns);
        }

        return $this;
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setKeyForeign($columns)
    {
        return $this->setKey($columns, static::FOREIGN);
    }

    /**
     * @param array $keys
     * @return ShoppingfeedDbTable
     */
    public function setKeysUnique($keys)
    {
        return $this->setKeyUnique(array_keys($keys));
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setKeyUnique($columns)
    {
        return $this->setKey($columns, static::UNIQUE);
    }

    /**
     * @param array $keys
     * @return ShoppingfeedDbTable
     */
    public function setKeysFulltext($keys)
    {
        return $this->setKeyFulltext(array_keys($keys));
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setKeyFulltext($columns)
    {
        return $this->setKey($columns, static::FULLTEXT);
    }

    /**
     * @param array $keys
     * @return ShoppingfeedDbTable
     */
    public function setKeysSimple($keys)
    {
        return $this->setKeySimple(array_keys($keys));
    }

    /**
     * @param array $columns
     * @return ShoppingfeedDbTable
     */
    public function setKeySimple($columns)
    {
        return $this->setKey($columns);
    }

    /**
     * @param array    $columns
     * @param int|null $type
     * @return ShoppingfeedDbTable
     */
    protected function setKey($columns, $type = null)
    {
        // Empty columns may be returned by `array_filter`s.
        if (empty($columns)) {
            return $this;
        }

        $name = implode('_', $columns);
        $columns = implode('`, `', $columns);
        switch ($type) {
            case static::PRIMARY:
                $this->keys[] = "PRIMARY KEY (`$columns`)";
                break;
            case static::FOREIGN:
                list($table, $columns) = explode('.', $name);
                $this->keys[] = "FOREIGN KEY (`$columns`) REFERENCES $table (`$columns`) ON UPDATE CASCADE ON DELETE CASCADE";
                break;
            case static::UNIQUE:
                $this->keys[] = "UNIQUE KEY (`$columns`)";
                break;
            case static::FULLTEXT:
                $this->keys[] = "FULLTEXT KEY (`$columns`)";
                break;
            default:
                $this->keys[] = "KEY (`$columns`)";
                break;
        }

        return $this;
    }
}
