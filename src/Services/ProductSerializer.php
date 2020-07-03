<?php

namespace ShoppingfeedAddon\Services;

require_once(_PS_MODULE_DIR_ . 'shoppingfeed/classes/ShoppingfeedProduct.php');

use Context;
use Configuration;
use ShoppingfeedProduct;
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
use Validate;
use DateTime;
use ProductCore;
use Shoppingfeed;

class ProductSerializer
{
    private $product;
    private $configurations;
    private $link;
    private $sfModule;

    public function __construct($id_product, $id_lang, $id_shop)
    {
        $this->sfModule = \Module::getInstanceByName('shoppingfeed');
        $this->product = new Product($id_product, true, $id_lang, $id_shop);
        if (Validate::isLoadedObject($this->product) === false) {

            throw new \Exception('product must be a valid product');
        }
        $this->link = new Link();
        $this->configurations = Configuration::getMultiple(
            [
                'PS_SHOP_DEFAULT',
                'PS_TAX_ADDRESS_TYPE',
                'PS_COUNTRY_DEFAULT',
                'PS_LANG_DEFAULT',
                'PS_SHIPPING_FREE_PRICE',
                'PS_SHIPPING_HANDLING',
                'PS_SHIPPING_METHOD',
                'PS_SHIPPING_FREE_WEIGHT',
                'SHOPPING_FLUX_IMAGE',
                Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY,
                Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT,
                Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE,
                Shoppingfeed::PRODUCT_FEED_SYNC_PACK,
                Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS,
            ]
        );
    }

    private function getCarrier()
    {
        $carrier = Carrier::getCarrierByReference((int)$this->configurations[Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE]);
        $carrier = is_object($carrier) ? $carrier : new Carrier((int)$this->configurations[Shoppingfeed::PRODUCT_FEED_CARRIER_REFERENCE]);

        return $carrier;
    }

    public function serialize()
    {
        $sfp = new ShoppingfeedProduct();
        $sfp->id_product = $this->product->id;
        $link = new Link();
        $carrier = $this->getCarrier();
        $productLink = $link->getProductLink($this->product, null, null, null, $this->configurations['PS_LANG_DEFAULT']);

        $content = [
            'reference' => $this->sfModule->mapReference($sfp),
            'gtin' => $this->product->ean13,
            'name' => $this->product->name,
            'link' => $productLink,
            'category' =>[
                'name' => ($this->configurations[\Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY] === 'default_category')? $this->_getCategory(): $this->_getFilAriane(),
                'link' => $link->getCategoryLink($this->product->id_category_default, null, $this->configurations['PS_LANG_DEFAULT']),
            ],
            'description' => [
                'full' => $this->product->description,
                'short' => $this->product->description_short,
            ],
            'images' => $this->getImages(),
            'attributes' => $this->getAttributes(),
            'variations' => $this->getVariations($carrier, $productLink),
        ];
        if ((int) $this->product->id_manufacturer !== 0) {
            $content['brand'] = [
                'name' => $this->product->manufacturer_name,
                'link' => $link->getManufacturerLink($this->product->id_manufacturer, null, $this->configurations['PS_LANG_DEFAULT']),
            ];
        }
        $content = $this->serializePrice($content);
        $content = $this->serializeStock($content);

        return $content;
    }

    public function serializePrice($content)
    {
        $contentUpdate = $content;
        $carrier = $this->getCarrier();
        $sfp = new ShoppingfeedProduct();
        $sfp->id_product = $this->product->id;
        $priceWithoutReduction = $this->sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT']);
        $priceWithReduction = $this->sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT'], ['price_with_reduction' => true]);
        $contentUpdate['price'] = $priceWithoutReduction;
        $contentUpdate['discounts'] = $this->getDiscounts();
        if ($priceWithoutReduction > $priceWithReduction) {
            $contentUpdate['discounts'][] = $priceWithoutReduction;
        }
        $contentUpdate['shipping'] = [
            'amount' => $this->_getShipping($carrier, $priceWithReduction),
            'label' => $carrier->delay[$this->configurations['PS_LANG_DEFAULT']],
        ];

        return $contentUpdate;
    }

    public function serializeStock($content)
    {
        $contentUpdate = $content;
        $contentUpdate['quantity'] = $this->product->quantity;

        return $contentUpdate;
    }

    private function getDiscounts()
    {
        return array_column($this->_getDiscounts(), 'price');
    }

    private function getImages()
    {
        $imagesFromDb = $this->getImagesFromDb($this->product->id, $this->configurations['PS_LANG_DEFAULT']);
        $images = [
            'main' => null,
            'additional' => [],
        ];
        $first = true;
        if ($imagesFromDb != false) {
            foreach ($imagesFromDb as $image) {
                $ids = $this->product->id . '-' . $image['id_image'];
                $img_url = $this->link->getImageLink($this->product->link_rewrite, $ids, $this->configurations[Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT]);
                if (!substr_count($img_url, Tools::getCurrentUrlProtocolPrefix())) {
                    $img_url = Tools::getCurrentUrlProtocolPrefix() . $img_url;
                }
                if ($first) {
                    $images['main'] = $img_url;
                    $first = false;
                } else {
                    $images['additional'][] = $img_url;
                }
            }
        }

        return $images;
    }

    protected function _getDiscounts()
    {
        $discounts = [];
        $specificPricesInFuture = [];
        $specificPrices = SpecificPrice::getIdsByProductId($this->product->id);
        $priceComputed = $this->product->getPrice(true, null, 2, null, false, true, 1);

        foreach ($specificPrices as $idSpecificPrice) {
            $specificPrice = new SpecificPrice($idSpecificPrice['id_specific_price']);
            if (new DateTime($specificPrice->from) > new DateTime()) {
                $specificPricesInFuture[] = $specificPrice;
            }
        }
        foreach ($specificPricesInFuture as $currentSpecificPrice) {
            $discount = [];
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
            $discounts[] = $discount;
        }

        return $discounts;
    }

    private function getStringTags()
    {
        $tabTags = Tag::getProductTags($this->product->id);
        if (empty($tabTags[$this->configurations['PS_LANG_DEFAULT']])) {
            return '';
        } else {
            return implode("|", $tabTags[$this->configurations['PS_LANG_DEFAULT']]);
        }
    }

    protected function getAttributes()
    {
        $combination = $this->product->getAttributeCombinations($this->configurations['PS_LANG_DEFAULT']);

        $attributes = [
            'meta_title' => $this->product->meta_title,
            'meta_description' => $this->product->meta_description,
            'meta_keywords' => $this->product->meta_keywords,
            'tages' => $this->getStringTags(),
            'width' => $this->product->width,
            'depth' => $this->product->depth,
            'height' => $this->product->height,
            'state' => $this->product->condition,
            'available_for_order' => $this->product->available_for_order,
            'out_of_stock' => $this->product->out_of_stock,
            'weight' => $this->product->weight,
            'ecotax' => $this->product->ecotax,
            'vat' => $this->product->tax_rate,
            'mpn' => $this->product->reference,
            'supplier_reference' => $this->product->supplier_reference,
            'upc' => $this->product->upc,
            'wholesale-price' => $this->product->wholesale_price,
            'on_sale' => (int)$this->product->on_sale,
            'hierararchy' => count($combination) > 0? 'parent' : 'child',
        ];
        $supplier = $this->product->supplier_name;
        $supplier_link = $this->link->getSupplierLink($this->product->id_supplier, null, $this->configurations['PS_LANG_DEFAULT']);
        if (empty($supplier) === false && empty($supplier_link) === false) {
            $attributes['supplier'] = $supplier;
            $attributes['supplier_link'] = $supplier_link;
        }

        if (empty($this->configurations[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS]) === false) {
            $fields = Tools::jsonDecode($this->configurations[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS], true);
            foreach ($this->getOverrideFields() as $fieldname) {
                if (array_key_exists($fieldname, $fields)) {
                    $attributes[$fieldname] = $this->product->$fieldname;
                }
            }
        }
        foreach ($this->product->getFrontFeatures($this->configurations['PS_LANG_DEFAULT']) as $feature) {
            $feature['name'] = $this->_clean($feature['name']);
            if (empty($feature['name']) === false) {
                $attributes[$feature['name']] = $feature['value'];
            }
        }

        return $attributes;
    }

    protected function getVariations($carrier, $productLink)
    {
        $variations = [];
        $combinations = [];
        $sfModule = \Module::getInstanceByName('shoppingfeed');
        $sfp = new ShoppingfeedProduct();
        $sfp->id_product = $this->product->id;

        foreach ($this->product->getAttributeCombinations($this->configurations['PS_LANG_DEFAULT']) as $combinaison) {
            $combinations[$combinaison['id_product_attribute']]['attributes'][$combinaison['group_name']] = $combinaison['attribute_name'];
            $combinations[$combinaison['id_product_attribute']]['ean13'] = $combinaison['ean13'];
            $combinations[$combinaison['id_product_attribute']]['upc'] = $combinaison['upc'];
            $combinations[$combinaison['id_product_attribute']]['quantity'] = $combinaison['quantity'];
            $combinations[$combinaison['id_product_attribute']]['weight'] = $combinaison['weight'] + $this->product->weight;
            $combinations[$combinaison['id_product_attribute']]['reference'] = $combinaison['reference'];
            $combinations[$combinaison['id_product_attribute']]['wholesale_price'] = $combinaison['wholesale_price'];
        }

        foreach ($combinations as $id => $combination) {
            set_time_limit(60);
            asort($combination['attributes']);
            $image_child = true;
            $sfp->id_product_attribute = $id;
            $priceWithoutReduction = $sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT']);
            $priceWithReduction = $this->sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT'], ['price_with_reduction' => true]);
            $variation = [
                'reference' => $sfModule->mapReference($sfp),
                'gtin' => $combination['ean13'],
                'quantity' => $combination['quantity'],
                'link' => $productLink . $this->product->getAnchor($id, true),
                'price' => $priceWithoutReduction,
                'shipping' => [
                    'amount' => $this->_getShipping($carrier, $priceWithReduction, $combination['weight']),
                    'label' => $carrier->delay[$this->configurations['PS_LANG_DEFAULT']],
                ],
                'images' => [],
                'attributes' => [
                    'upc' => $combination['upc'],
                    'weight' => $combination['weight'],
                    'wholesale-price' => $combination['wholesale_price'],
                    'mpn' => $combination['reference'],
                ],
                'discounts' => []
            ];
            if ($priceWithoutReduction > $priceWithReduction) {
                $variation['discounts'][] = $priceWithReduction;
            }
            foreach ($this->_getAttributeImageAssociations($id) as $image) {
                if (empty($image)) {
                    $image_child = false;
                    break;
                }
                $variation['images'][] = Tools::getCurrentUrlProtocolPrefix(). $this->link->getImageLink($this->product->link_rewrite, $this->product->id.'-'.$image, $this->configurations['SHOPPING_FLUX_IMAGE']);
            }
            if ($image_child === false) {
                foreach ($this->product->getImages($this->configurations['PS_LANG_DEFAULT']) as $images) {
                    $ids = $this->product->id.'-'.$images['id_image'];
                    $variation['images'][] = Tools::getCurrentUrlProtocolPrefix().$this->link->getImageLink($this->product->link_rewrite, $ids, $this->configurations['SHOPPING_FLUX_IMAGE']);
                }
            }
            foreach ($combination['attributes'] as $attributeName => $attributeValue) {
                $attributeName = $this->_clean($attributeName);
                if (empty($attributeName) === false) {
                    $variation['attributes'][$attributeName] = $attributeValue;
                }
            }

            $variations[] = $variation;
        }

        return $variations;
    }

    protected function _getFilAriane()
    {
        $category = '';

        foreach ($this->_getProductFilAriane($this->product->id, $this->configurations['PS_LANG_DEFAULT']) as $categories) {
            $category .= $categories . ' > ';
        }

        return Tools::substr($category, 0, -3);
    }

    protected function _getShipping($carrier, $priceWithReduction, $attribute_weight = null)
    {
        $default_country = new Country($this->configurations['PS_COUNTRY_DEFAULT'], $this->configurations['PS_LANG_DEFAULT']);
        $id_zone = (int)$default_country->id_zone;
        $carrier_tax = Tax::getCarrierTaxRate((int)$carrier->id);
        $shipping = 0;
        $shipping_free_price = $this->configurations['PS_SHIPPING_FREE_PRICE'];
        $shipping_free_weight = isset($this->configurations['PS_SHIPPING_FREE_WEIGHT']) ? $this->configurations['PS_SHIPPING_FREE_WEIGHT'] : 0;

        if (!(((float)$shipping_free_price > 0) && ($priceWithReduction >= (float)$shipping_free_price)) &&
            !(((float)$shipping_free_weight > 0) && ($this->product->weight + $attribute_weight >= (float)$shipping_free_weight))) {
            if (isset($this->configurations['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
                $shipping = (float)($this->configurations['PS_SHIPPING_HANDLING']);
            }

            if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping += $carrier->getDeliveryPriceByWeight($this->product->weight, $id_zone);
            } else {
                $shipping += $carrier->getDeliveryPriceByPrice($priceWithReduction, $id_zone);
            }

            $shipping *= 1 + ($carrier_tax / 100);
            $shipping = (float)(Tools::ps_round((float)($shipping), 2));
        }

        return (float)$shipping + (float)$this->product->additional_shipping_cost;
    }

    protected function _getCategory()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT cl.`name`
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (product_shop.`id_category_default` = cl.`id_category`)
            WHERE p.`id_product` = '.(int)$this->product->id.'
            AND cl.`id_lang` = '.(int)$this->configurations['PS_LANG_DEFAULT']);
    }

    protected function getImagesFromDb($id_lang)
    {
        return Db::getInstance()->ExecuteS('
            SELECT i.`cover`, i.`id_image`, il.`legend`, i.`position`
            FROM `'._DB_PREFIX_.'image` i
            LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)($id_lang).')
            WHERE i.`id_product` = '.(int) $this->product->id .'
            ORDER BY i.cover DESC, i.`position` ASC ');
    }

    protected function _clean($string)
    {
        $regexStr = preg_replace('/[^A-Za-z0-9]/', '', $string);
        return preg_replace("/^(\d+)/i", "car-$1", $regexStr);
    }

    protected function getOverrideFields()
    {
        $newFields = [];
        $coreFields = [];
        $overrideProductFields = Product::$definition['fields'];
        $this->productCoreFields = ProductCore::$definition['fields'];

        foreach ($this->productCoreFields as $key => $value) {
            $coreFields[] = $key;
        }
        foreach ($overrideProductFields as $key => $value) {
            if (!in_array($key, $coreFields)) {
                $newFields[] = $key;
            }
        }

        return $newFields;
    }

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
        $ret = [];
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
                $this->productCategory = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            			SELECT DISTINCT c.`id_category`, cl.`name`, 
                            c.`id_parent` FROM `'._DB_PREFIX_.'category_product` cp
            			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`)
            			LEFT JOIN `'._DB_PREFIX_.'category` c ON (cp.`id_category` = c.`id_category`)
            			WHERE cp.`id_product` = '.(int)$id_product.'
			            AND cp.`id_category` NOT IN ('.$id_category.')
            			AND cl.`id_lang` = '.(int)$id_lang.'
			            ORDER BY level_depth DESC');

                if (! sizeof($this->productCategory)) {
                    return array();
                }

                return $this->_getProductFilAriane($id_product, $id_lang, $this->productCategory[0]['id_category'], $this->productCategory[0]['id_parent'], $this->productCategory[0]['name']);
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