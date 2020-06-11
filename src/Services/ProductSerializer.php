<?php

namespace ShoppingfeedAddon\Services;

use Context;
use Configuration;
use Tools;
use Carrier;
use Shop;
use Db;
use Link;
use Product;
use Country;
use Tax;
use Tag;
use SpecificPrice;
use DateTime;
use ProductCore;

class ProductSerializer
{
    private $product_id;

    public function getProductId()
    {
        return $this->product_id;
    }

    public function setProductId($product_id)
    {
        $this->product_id = $product_id;

        return $this;
    }

    public function serialize()
    {
    }

    public function generateFeed()
    {
        Context::getContext()->employee = new \Employee(1);
        $feed = [];
        $configuration = Configuration::getMultiple(
            array(
                'PS_TAX_ADDRESS_TYPE', 'PS_CARRIER_DEFAULT',
                'PS_COUNTRY_DEFAULT', 'PS_LANG_DEFAULT',
                'PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_HANDLING',
                'PS_SHIPPING_METHOD', 'PS_SHIPPING_FREE_WEIGHT',
                'SHOPPING_FLUX_IMAGE', 'SHOPPING_FLUX_REF'
            )
        );
        $no_breadcrumb = Tools::getValue('no_breadcrumb');
        $lang = Tools::getValue('lang');
        $configuration['PS_LANG_DEFAULT'] = !empty($lang) ? Language::getIdByIso($lang) : $configuration['PS_LANG_DEFAULT'];
        $carrier = Carrier::getCarrierByReference((int)Configuration::get('SHOPPING_FLUX_CARRIER'));

        //manage case PS_CARRIER_DEFAULT is deleted
        $carrier = is_object($carrier) ? $carrier : new Carrier((int)Configuration::get('SHOPPING_FLUX_CARRIER'));
        $products = $this->getSimpleProducts($configuration['PS_LANG_DEFAULT'], false, 0);
        $link = new Link();

        foreach ($products as $productArray) {
            $product = new Product((int)($productArray['id_product']), true, $configuration['PS_LANG_DEFAULT']);
            $row = $this->_getBaseData($product, $configuration, $link, $carrier);
            $row['images'] = $this->_getImages($product, $configuration, $link);
            $row['uri-categories'] = $this->_getUrlCategories($product, $configuration, $link);
            $row['caracteristiques'] = $this->_getFeatures($product, $configuration);
            $row['declinaisons'] = $this->_getCombinaisons($product, $configuration, $link, $carrier);

            if (empty($no_breadcrumb)) {
                $row[$this->_translateField('category_breadcrumb')] = $this->_getFilAriane($product, $configuration);
            }
            $row['manufacturer'] = $product->manufacturer_name;
            $row['supplier'] = $product->supplier_name;

            if (is_array($product->specificPrice)) {
                $fromValue = isset($product->specificPrice['from']) ? $product->specificPrice['from'] : "";
                $toValue = isset($product->specificPrice['to']) ? $product->specificPrice['to'] : "";
                $row['from'] = $fromValue;
                $row['to'] = $toValue;
            } else {
                $row['from'] = '';
                $row['to'] = '';
            }

            $specificPrices = SpecificPrice::getIdsByProductId($product->id);
            $specificPricesInFuture = array();
            foreach ($specificPrices as $idSpecificPrice) {
                $specificPrice = new SpecificPrice($idSpecificPrice['id_specific_price']);

                if (new DateTime($specificPrice->from) > new DateTime()) {
                    $specificPricesInFuture[] = $specificPrice;
                }
            }

            $row['discounts'] = [];
            $priceComputed = $product->getPrice(true, null, 2, null, false, true, 1);
            foreach ($specificPricesInFuture as $currentSpecificPrice) {
                $discount = [];
                // Reduction calculation
                if ($currentSpecificPrice->price == -1) {
                    if ($currentSpecificPrice->reduction_type == 'amount') {
                        $reduction_amount = $currentSpecificPrice->reduction;
                        $reduc = $reduction_amount;
                    } else {
                        $reduc = $priceComputed * $currentSpecificPrice->reduction;
                    }
                    $priceComputed -= $reduc;
                    $priceComputed = round($priceComputed, 2);
                } else {
                    $priceComputed = $currentSpecificPrice->price;
                }

                $discount['from'] = $currentSpecificPrice->from;
                $discount['to'] = $currentSpecificPrice->to;
                $discount['price'] = $priceComputed;
                $row['discounts'][] = $discount;
            }

            $row[$this->_translateField('supplier_link')] = $link->getSupplierLink($product->id_supplier, null, $configuration['PS_LANG_DEFAULT']);
            $row[$this->_translateField('manufacturer_link')] = $link->getManufacturerLink($product->id_manufacturer, null, $configuration['PS_LANG_DEFAULT']);
            $row[$this->_translateField('on_sale')] = (int)$product->on_sale;
            $feed[$this->_translateField('product')] = $row;
        }
    }

    protected function getSimpleProducts($id_lang, $limit_from, $limit_to)
    {
        $packClause = '';
        if (Configuration::get('SHOPPING_FLUX_PACKS') !== 'checked') {
            $packClause = ' AND p.`cache_is_pack` = 0 ';
        }

        if (version_compare(_PS_VERSION_, '1.5', '>')) {
            $context = Context::getContext();

            $front = true;

            $fixedIdProduct = Tools::getValue('id');
            $fixedIdProductClause = '';
            if ($fixedIdProduct) {
                $fixedIdProductClause = "AND p.`id_product` = '".$fixedIdProduct."'";
            }

            $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND product_shop.`active`= 1 
                AND product_shop.`available_for_order`= 1 
                ' . $packClause . '
                '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
                '.$fixedIdProductClause.'
                ORDER BY pl.`name`';

            if ($limit_from !== false) {
                $sql .= ' LIMIT '.(int)$limit_from.', '.(int)$limit_to;
            }
        } else {
            $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`)
                WHERE pl.`id_lang` = '.(int)($id_lang).' 
                AND p.`active`= 1 AND p.`available_for_order`= 1
                ' . $packClause . '
                ORDER BY pl.`name`';
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    protected function _translateField($field)
    {
        $translations = array(
            'FR' => array(
                'product' => 'produit',
                'supplier_link' => 'url-fournisseur',
                'manufacturer_link' => 'url-fabricant',
                'on_sale' => 'solde',
                'name' => 'nom',
                'link' => 'url',
                'short_description' => 'description-courte',
                'price' => 'prix',
                'old_price' => 'prix-barre',
                'shipping_cost' => 'frais-de-port',
                'shipping_delay' => 'delai-livraison',
                'brand' => 'marque',
                'category' => 'rayon',
                'quantity' => 'quantite',
                'weight' => 'poids',
                'ecotax' => 'ecotaxe',
                'vat' => 'tva',
                'mpn' => 'ref-constructeur',
                'supplier_reference' => 'ref-fournisseur',
                'category_breadcrumb' => 'fil-ariane',
                'combination_link' => 'url-declinaison',
            )
        );

        $iso_code = 'fr';

        if (isset($translations[$iso_code][$field])) {
            return $translations[$iso_code][$field];
        }

        return $field;
    }

    protected function _getBaseData($product, $configuration, $link, $carrier)
    {
        $ret = [];

        $titles = array(
            0 => 'id',
            1 => $this->_translateField('name'),
            2 => $this->_translateField('link'),
            4 => 'description',
            5 => $this->_translateField('short_description'),
            6 => $this->_translateField('price'),
            7 => $this->_translateField('old_price'),
            8 => $this->_translateField('shipping_cost'),
            9 => $this->_translateField('shipping_delay'),
            10 => $this->_translateField('brand'),
            11 => $this->_translateField('category'),
            13 => $this->_translateField('quantity'),
            14 => 'ean',
            15 => $this->_translateField('weight'),
            16 => $this->_translateField('ecotax'),
            17 => $this->_translateField('vat'),
            18 => $this->_translateField('mpn'),
            19 => $this->_translateField('supplier_reference'),
            20 => 'upc',
            21 => 'wholesale-price'
        );

        $data = array();
        $data[0] = ($configuration['SHOPPING_FLUX_REF'] != 'true') ? $product->id : $product->reference;
        $data[1] = $product->name;
        $data[2] = $link->getProductLink($product, null, null, null, $configuration['PS_LANG_DEFAULT']);
        $data[4] = $product->description;
        $data[5] = $product->description_short;

        $context = Context::getContext();
        $id_currency = Tools::getValue('currency');
        if ($id_currency) {
            $context->currency  = new Currency(Currency::getIdByIsoCode(Tools::getValue('currency')));
        }

        $data[6] = $product->getPrice(true, null, 2, null, false, true, 1);
        $data[7] = $product->getPrice(true, null, 2, null, false, false, 1);
        $data[8] = $this->_getShipping($product, $configuration, $carrier);
        $data[9] = $carrier->delay[$configuration['PS_LANG_DEFAULT']];
        $data[10] = $product->manufacturer_name;
        $data[11] = $this->_getCategories($product, $configuration);
        $data[13] = $product->quantity;
        $data[14] = $product->ean13;
        $data[15] = $product->weight;
        $data[16] = $product->ecotax;
        $data[17] = $product->tax_rate;
        $data[18] = $product->reference;
        $data[19] = $product->supplier_reference;
        $data[20] = $product->upc;
        $data[21] = $product->wholesale_price;

        foreach ($titles as $key => $balise) {
            $ret[$balise] = htmlentities($data[$key], ENT_QUOTES, 'UTF-8');

        }

        return $ret;
    }

    protected function _getImages($product, $configuration, $link)
    {
        $images = $this->getImages($product->id, $configuration['PS_LANG_DEFAULT']);
        $ret = [];

        if ($images != false) {
            foreach ($images as $image) {
                $ids = $product->id.'-'.$image['id_image'];
                $img_url = $link->getImageLink($product->link_rewrite, $ids, $configuration['SHOPPING_FLUX_IMAGE']);
                if (!substr_count($img_url, Tools::getCurrentUrlProtocolPrefix())) {
                    // make sure url has http or https
                    $img_url = Tools::getCurrentUrlProtocolPrefix() . $img_url;
                }
                $ret[] =  $img_url;
            }
        }

        return $ret;
    }

    protected function _getUrlCategories($product, $configuration, $link)
    {
        $ret = [];

        foreach ($this->_getProductCategoriesFull($product->id, $configuration['PS_LANG_DEFAULT']) as $key => $categories) {
            $ret[]['uri'] = $link->getCategoryLink($key, null, $configuration['PS_LANG_DEFAULT']);
        }

         return $ret;
    }

    protected function _getFeatures($product, $configuration)
    {
        $ret = [];
        foreach ($product->getFrontFeatures($configuration['PS_LANG_DEFAULT']) as $feature) {
            $feature['name'] = $this->_clean($feature['name']);

            if (!empty($feature['name'])) {
                $ret[$feature['name']] = $feature['name'];
            }
        }

        $ret['meta_title'] = $product->meta_title;
        $ret['meta_description'] = $product->meta_description;
        $ret['meta_keywords'] = $product->meta_keywords;

        $tabTags = Tag::getProductTags($product->id);

        if (empty($tabTags[$configuration['PS_LANG_DEFAULT']])) {
            $ret['tags'] = '<tags></tags>';
        } else {
            $ret['tags'] = implode("|", $tabTags[$configuration['PS_LANG_DEFAULT']]);
        }

        $ret['width'] = $product->width;
        $ret['depth'] = $product->depth;
        $ret['height'] = $product->height;

        $ret['state'] = $product->condition;
        $ret['available_for_order'] = $product->available_for_order;
        $ret['out_of_stock'] = $product->out_of_stock;

        $fields = $this->getOverrideFields();
        foreach ($fields as $fieldname) {
            if (Configuration::get('SHOPPING_FLUX_CUSTOM_'.$fieldname) == 1) {
                $ret[$fieldname] = $product->$fieldname;
            }
        }

        $combination = $product->getAttributeCombinations($configuration['PS_LANG_DEFAULT']);
        if (count($combination) > 0) {
            $ret['hierararchy'] = 'parent';
        } else {
            $ret['hierararchy'] = 'hierararchy';
        }

        return $ret;
    }

    protected function _getCombinaisons($product, $configuration, $link, $carrier, $fileToWrite = 0)
    {
        $combinations = array();
        $ret = [];

        if ($fileToWrite) {
            fwrite($fileToWrite, $ret);
        }

        foreach ($product->getAttributeCombinations($configuration['PS_LANG_DEFAULT']) as $combinaison) {
            $combinations[$combinaison['id_product_attribute']]['attributes'][$combinaison['group_name']] = $combinaison['attribute_name'];
            $combinations[$combinaison['id_product_attribute']]['ean13'] = $combinaison['ean13'];
            $combinations[$combinaison['id_product_attribute']]['upc'] = $combinaison['upc'];
            $combinations[$combinaison['id_product_attribute']]['quantity'] = $combinaison['quantity'];
            $combinations[$combinaison['id_product_attribute']]['weight'] = $combinaison['weight'] + $product->weight;
            $combinations[$combinaison['id_product_attribute']]['reference'] = $combinaison['reference'];
            $combinations[$combinaison['id_product_attribute']]['wholesale_price'] = $combinaison['wholesale_price'];
        }

        $j = 0;
        foreach ($combinations as $id => $combination) {
            // Add time limit to php execution in case of multiple combination
            set_time_limit(60);
            $j++;

            if ($fileToWrite) {
                $ret = '';
            }

            if ($configuration['SHOPPING_FLUX_REF'] != 'true') {
                $ref = $id;
            } else {
                $ref = $combination['reference'];
            }

            $declinaison = [];
            $declinaison['id'] = $ref;
            $declinaison['ean'] = $combination['ean13'];
            $declinaison['upc'] = $combination['upc'];


            $declinaison[$this->_translateField('quantity')] = $combination['quantity'];
            $declinaison[$this->_translateField('weight')] = $combination['weight'];
            $declinaison[$this->_translateField('price')] = $product->getPrice(true, $id, 2, null, false, true, 1);
            $declinaison[$this->_translateField('old_price')] = $product->getPrice(true, $id, 2, null, false, false, 1);
            $declinaison[$this->_translateField('shipping_cost')] = $this->_getShipping($product, $configuration, $carrier, $id, $combination['weight']);
            $declinaison['wholesale-price'] = $combination['wholesale_price'];

            $declinaison['images'] = [];
            $image_child = true;

            foreach ($this->_getAttributeImageAssociations($id) as $image) {
                if (empty($image)) {
                    $image_child = false;
                    break;
                }
                $declinaison['images'][] = Tools::getCurrentUrlProtocolPrefix().$link->getImageLink($product->link_rewrite, $product->id.'-'.$image, $configuration['SHOPPING_FLUX_IMAGE']);
            }

            if (!$image_child) {
                foreach ($product->getImages($configuration['PS_LANG_DEFAULT']) as $images) {
                    $ids = $product->id.'-'.$images['id_image'];
                    $declinaison['images'][] = Tools::getCurrentUrlProtocolPrefix().$link->getImageLink($product->link_rewrite, $ids, $configuration['SHOPPING_FLUX_IMAGE']);
                }
            }

            $declinaison['attributs'] = ['hierararchy' => 'child'];


            asort($combination['attributes']);
            foreach ($combination['attributes'] as $attributeName => $attributeValue) {
                $attributeName = $this->_clean($attributeName);

                if (!empty($attributeName)) {
                    $declinaison['attributs'][$attributeName] = $attributeValue;
                }
            }
            $productLink = $link->getProductLink($product, null, null, null, $configuration['PS_LANG_DEFAULT']);
            $declinaison[$this->_translateField('mpn')][$attributeName] = $combination['reference'];
            $declinaison[$this->_translateField('combination_link')][$attributeName] = $productLink.$product->getAnchor($id, true);
            $ret[] = $declinaison;
        }

        return $ret;
    }

    protected function _getFilAriane($product, $configuration)
    {
        $category = '';
        $ret[$this->_translateField('category_breadcrumb')] = '<'.$this->_translateField('category_breadcrumb').'>';

        foreach ($this->_getProductFilAriane($product->id, $configuration['PS_LANG_DEFAULT']) as $categories) {
            $category .= $categories.' > ';
        }
        $ret[$this->_translateField('category_breadcrumb')] = Tools::substr($category, 0, -3);

        return $ret;
    }

    protected function _getShipping($product, $configuration, $carrier, $attribute_id = null, $attribute_weight = null)
    {
        $default_country = new Country($configuration['PS_COUNTRY_DEFAULT'], $configuration['PS_LANG_DEFAULT']);
        $id_zone = (int)$default_country->id_zone;
        $this->id_address_delivery = 0;
        $carrier_tax = Tax::getCarrierTaxRate((int)$carrier->id, (int)$this->{$configuration['PS_TAX_ADDRESS_TYPE']});

        $shipping = 0;

        $product_price = $product->getPrice(true, $attribute_id, 2, null, false, true, 1);
        $shipping_free_price = $configuration['PS_SHIPPING_FREE_PRICE'];
        $shipping_free_weight = isset($configuration['PS_SHIPPING_FREE_WEIGHT']) ? $configuration['PS_SHIPPING_FREE_WEIGHT'] : 0;

        if (!(((float)$shipping_free_price > 0) && ($product_price >= (float)$shipping_free_price)) &&
            !(((float)$shipping_free_weight > 0) && ($product->weight + $attribute_weight >= (float)$shipping_free_weight))) {
            if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
                $shipping = (float)($configuration['PS_SHIPPING_HANDLING']);
            }

            if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping += $carrier->getDeliveryPriceByWeight($product->weight, $id_zone);
            } else {
                $shipping += $carrier->getDeliveryPriceByPrice($product_price, $id_zone);
            }

            $shipping *= 1 + ($carrier_tax / 100);
            $shipping = (float)(Tools::ps_round((float)($shipping), 2));
        }

        return (float)$shipping + (float)$product->additional_shipping_cost;
    }

    protected function _getCategories($product, $configuration)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT cl.`name`
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (product_shop.`id_category_default` = cl.`id_category`)
            WHERE p.`id_product` = '.(int)$product->id.'
            AND cl.`id_lang` = '.(int)$configuration['PS_LANG_DEFAULT']);
    }

    protected function getImages($id_product, $id_lang)
    {
        return Db::getInstance()->ExecuteS('
            SELECT i.`cover`, i.`id_image`, il.`legend`, i.`position`
            FROM `'._DB_PREFIX_.'image` i
            LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)($id_lang).')
            WHERE i.`id_product` = '.(int)($id_product).'
            ORDER BY i.cover DESC, i.`position` ASC ');
    }

    protected function _getProductCategoriesFull($id_product, $id_lang)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT cp.`id_category`, cl.`name`, cl.`link_rewrite` FROM `'._DB_PREFIX_.'category_product` cp
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`)
            WHERE cp.`id_product` = '.(int)$id_product.'
            AND cl.`id_lang` = '.(int)$id_lang.'
            ORDER BY cp.`position` DESC');

        $ret = array();

        foreach ($row as $val) {
            $ret[$val['id_category']] = $val;
        }

        return $ret;
    }

    protected function _clean($string)
    {
        $regexStr = preg_replace('/[^A-Za-z0-9]/', '', $string);
        return preg_replace("/^(\d+)/i", "car-$1", $regexStr);
    }

    protected function getOverrideFields()
    {
        static $definition;
        // Load override Product info
        $overrideProductFields = Product::$definition['fields'];

        $newFields = array();

        $productCoreFields = ProductCore::$definition['fields'];
        $coreFields = array();

        foreach ($productCoreFields as $key => $value) {
            $coreFields[] = $key;
        }

        foreach ($overrideProductFields as $key => $value) {
            if (!in_array($key, $coreFields)) {
                $newFields[] = $key;
            }
        }

        return $newFields;
    }

    /* Product attributes */
    protected function _getAttributeImageAssociations($id_product_attribute)
    {
        $combinationImages = array();
        $data = Db::getInstance()->ExecuteS('
            SELECT pai.`id_image`
            FROM `'._DB_PREFIX_.'product_attribute_image` pai
            LEFT JOIN `'._DB_PREFIX_.'image` i ON pai.id_image = i.id_image
            WHERE pai.`id_product_attribute` = '.(int)($id_product_attribute).'
            ORDER BY i.cover DESC, i.position ASC
        ');

        foreach ($data as $row) {
            $combinationImages[] = (int)($row['id_image']);
        }

        return $combinationImages;
    }

    protected function _getProductFilAriane($id_product, $id_lang, $id_category = 0, $id_parent = 0, $name = 0)
    {
        $ret = array();
        $id_parent = '';

        if ($id_category) {
            $ret[$id_category] = $name;
            $id_parent = $id_parent;
            $id_category = $id_category;
        } else {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT cl.`name`, p.`id_category_default` as id_category, c.`id_parent` FROM `'._DB_PREFIX_.'product` p
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (p.`id_category_default` = cl.`id_category`)
			LEFT JOIN `'._DB_PREFIX_.'category` c ON (p.`id_category_default` = c.`id_category`)
			WHERE p.`id_product` = '.(int)$id_product.'
			AND cl.`id_lang` = '.(int)$id_lang);

            foreach ($row as $val) {
                $ret[$val['id_category']] = $val['name'];
                $id_parent = $val['id_parent'];
                $id_category = $val['id_category'];
            }
        }

        while ($id_parent != 0 && $id_category != $id_parent) {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
				SELECT cl.`name`, c.`id_category`, c.`id_parent` FROM `'._DB_PREFIX_.'category_lang` cl
				LEFT JOIN `'._DB_PREFIX_.'category` c ON (c.`id_category` = '.(int)$id_parent.')
				WHERE cl.`id_category` = '.(int)$id_parent.'
				AND cl.`id_lang` = '.(int)$id_lang);

            if (! sizeof($row)) {
                // There is a problem with the category parent, let's try another category
                $productCategory = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            			SELECT DISTINCT c.`id_category`, cl.`name`, 
                            c.`id_parent` FROM `'._DB_PREFIX_.'category_product` cp
            			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`)
            			LEFT JOIN `'._DB_PREFIX_.'category` c ON (cp.`id_category` = c.`id_category`)
            			WHERE cp.`id_product` = '.(int)$id_product.'
			            AND cp.`id_category` NOT IN ('.$id_category.')
            			AND cl.`id_lang` = '.(int)$id_lang.'
			            ORDER BY level_depth DESC');

                if (! sizeof($productCategory)) {
                    return array();
                }

                return $this->_getProductFilAriane($id_product, $id_lang, $productCategory[0]['id_category'], $productCategory[0]['id_parent'], $productCategory[0]['name']);
            }

            foreach ($row as $val) {
                $ret[$val['id_category']] = $val['name'];
                $id_parent = $val['id_parent'];
                $id_category = $val['id_category'];
            }
        }
        $ret = array_reverse($ret);

        return $ret;
    }
}