<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\Services;

use Context;
use Configuration;
use DbQuery;
use ShoppingfeedProduct;
use Tools;
use Carrier;
use Shop;
use Db;
use Link;
use Product;
use Country;
use Cart;
use Tax;
use Tag;
use SpecificPrice;
use Validate;
use DateTime;
use ProductCore;
use Shoppingfeed;
use StockAvailable;

if (version_compare(_PS_VERSION_, '1.7.7', '<')) {
    require_once _PS_MODULE_DIR_ . 'shoppingfeed/classes/Compatibility/SpecificPriceFormatter.php';
}

class ProductSerializer
{
    private $product;
    private $configurations;
    private $link;
    private $sfModule;
    private $id_shop;
    private $id_lang;
    private $id_currency;

    public function __construct($id_product, $id_lang, $id_shop, $id_currency)
    {
        $this->sfModule = \Module::getInstanceByName('shoppingfeed');
        $this->product = new Product($id_product, true, $id_lang, $id_shop);
        $this->id_shop = $id_shop;
        $this->id_lang = $id_lang;
        $this->id_currency = $id_currency;
        if (Validate::isLoadedObject($this->product) === false) {

            throw new \Exception('product must be a valid product');
        }
        $this->link = new Link();
        $this->id_lang = $id_lang;
        $this->configurations = Configuration::getMultiple(
            [
                'PS_SHOP_DEFAULT',
                'PS_TAX_ADDRESS_TYPE',
                'PS_COUNTRY_DEFAULT',
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
        $productLink = $link->getProductLink($this->product, null, null, null, $this->id_lang);

        $content = [
            'reference' => $this->sfModule->mapReference($sfp),
            'gtin' => $this->product->ean13,
            'name' => $this->product->name,
            'link' => $productLink,
            'description' => [
                'full' => $this->product->description,
                'short' => $this->product->description_short,
            ],
            'images' => $this->getImages(),
            'attributes' => $this->getAttributes(),
            'variations' => $this->getVariations($carrier, $productLink),
        ];
        if (empty($this->product->manufacturer_name) === false) {
            $manufacturerLink = $this->link->getManufacturerLink($this->product->id_manufacturer, null, $this->id_lang);
            $content['brand'] = [
                'name' => $this->product->manufacturer_name,
                'link' => $manufacturerLink,
            ];
        }

        $content = $this->serializePrice($content);
        $content = $this->serializeStock($content);
        $content = $this->serializeCategory($content);

        \Hook::exec('shoppingfeedSerialize', [
            'id_shop' => $this->id_shop,
            'id_lang' => $this->id_lang,
            'product' => $this->product,
            'content' => &$content,
        ]);

        return $content;
    }

    public function serializePrice($content)
    {
        $id_group = \Group::getCurrent()->id;
        $contentUpdate = $content;
        $carrier = $this->getCarrier();
        $sfp = new ShoppingfeedProduct();
        $sfp->id_product = $this->product->id;
        $address = \Address::initialize(null, true);
        $tax_manager = \TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct((int) $this->product->id, Context::getContext()));
        $product_tax_calculator = $tax_manager->getTaxCalculator();
        $priceWithoutReduction = $this->sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT']);
        $contentUpdate['price'] = $priceWithoutReduction;
        $contentUpdate['specificPrices'] = $this->getSpecificPrices($address, $product_tax_calculator, $id_group, $priceWithoutReduction);

        if (isset($contentUpdate['variations']) && false === empty($contentUpdate['variations'])) {
            foreach ($contentUpdate['variations'] as $idProductAttribute => $variation) {
                $sfp->id_product_attribute = (int)$idProductAttribute;
                $variationPrice = $this->sfModule->mapProductPrice($sfp, $this->configurations['PS_SHOP_DEFAULT']);
                $contentUpdate['variations'][$idProductAttribute]['price'] = $variationPrice;
                $contentUpdate['variations'][$idProductAttribute]['specificPrices'] = $this->getSpecificPrices($address, $product_tax_calculator, $id_group,  $variationPrice, (int)$idProductAttribute);

                if (isset($contentUpdate['variations'][$idProductAttribute]['shipping'])) {
                    $weight = @$contentUpdate['variations'][$idProductAttribute]['attributes']['weight'];
                    $contentUpdate['variations'][$idProductAttribute]['shipping']['amount'] = $this->getShippingCost($carrier, $address, $idProductAttribute);
                }
            }
        }

        $contentUpdate['shipping'] = [
            'amount' => $this->getShippingCost($carrier, $address, null),
            'label' => $carrier->delay[$this->id_lang],
        ];

        \Hook::exec('shoppingfeedSerializePrice', [
            'id_shop' => $this->id_shop,
            'id_lang' => $this->id_lang,
            'product' => $this->product,
            'content' => &$contentUpdate,
        ]);

        return $contentUpdate;
    }

    public function serializeStock($content)
    {
        $contentUpdate = $content;
        $contentUpdate['quantity'] = StockAvailable::getQuantityAvailableByProduct($this->product->id);
        foreach ($contentUpdate['variations'] as $id_product_attribute => &$variation) {
            $variation['quantity'] = StockAvailable::getQuantityAvailableByProduct($this->product->id, $id_product_attribute);
        }

        \Hook::exec('shoppingfeedSerializeStock', [
            'id_shop' => $this->id_shop,
            'id_lang' => $this->id_lang,
            'product' => $this->product,
            'content' => &$contentUpdate,
        ]);

        return $contentUpdate;
    }

    public function serializeCategory($content)
    {
        if ((int) $this->product->id_category_default === 0) {

            return $content;
        }
        $cat = $this->_getCategory();
        if ($cat === false) {

            return $content;
        }
        $link = new Link();
        $contentUpdate = $content;
        $contentUpdate['category'] = [
            'name' => ($this->configurations[\Shoppingfeed::PRODUCT_FEED_CATEGORY_DISPLAY] === 'default_category')? $cat: $this->_getFilAriane(),
            'link' => $link->getCategoryLink($this->product->id_category_default, null, $this->id_lang),
        ];

        return $contentUpdate;
    }

    private function getDiscounts()
    {
        return array_column($this->_getDiscounts(), 'price');
    }

    private function getImages()
    {
        $imagesFromDb = $this->getImagesFromDb($this->product->id, $this->id_lang);
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
        if (empty($tabTags[$this->id_lang])) {
            return '';
        } else {
            return implode("|", $tabTags[$this->id_lang]);
        }
    }

    protected function getAttributes()
    {
        $combination = $this->product->getAttributeCombinations($this->id_lang);

        $attributes = [
            'state' => $this->product->condition,
            'available_for_order' => $this->product->available_for_order,
            'out_of_stock' => $this->product->out_of_stock,
            'ecotax' => $this->product->ecotax,
            'vat' => $this->product->tax_rate,
            'on_sale' => (int)$this->product->on_sale,
            'hierararchy' => count($combination) > 0 ? 'parent' : 'child',
        ];
        if (empty($this->product->meta_title) === false) {
            $attributes['meta_title'] = $this->product->meta_title;
        }
        if (empty($this->product->meta_description) === false) {
            $attributes['meta_description'] = $this->product->meta_description;
        }
        if (empty($this->getStringTags()) === false) {
            $attributes['tages'] = $this->getStringTags();
        }
        if (empty($this->product->meta_keywords) === false) {
            $attributes['meta_keywords'] = $this->product->meta_keywords;
        }
        if (empty($this->product->width) === false && $this->product->width != 0) {
            $attributes['width'] = $this->product->width;
        }
        if (empty($this->product->depth) === false && $this->product->depth != 0) {
            $attributes['depth'] = $this->product->depth;
        }
        if (empty($this->product->height) === false && $this->product->height != 0) {
            $attributes['height'] = $this->product->height;
        }
        if (empty($this->product->weight) === false && $this->product->weight != 0) {
            $attributes['weight'] = $this->product->weight;
        }
        if (empty($this->product->reference) === false) {
            $attributes['mpn'] = $this->product->reference;
        }
        if (empty($this->product->upc) === false) {
            $attributes['upc'] = $this->product->upc;
        }
        if (empty($this->product->wholesale_price) === false && $this->product->wholesale_price != 0) {
            $attributes['wholesale_price'] = $this->product->wholesale_price;
        }
        if (empty($this->product->supplier_reference) === false) {
            $attributes['supplier_reference'] = $this->product->supplier_reference;
        }
        $supplier = $this->product->supplier_name;
        $supplier_link = $this->link->getSupplierLink($this->product->id_supplier, null, $this->id_lang);
        if (empty($supplier) === false && empty($supplier_link) === false) {
            $attributes['supplier'] = $supplier;
            $attributes['supplier_link'] = $supplier_link;
        }

        if (empty($this->configurations[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS]) === false) {
            $fields = Tools::jsonDecode($this->configurations[Shoppingfeed::PRODUCT_FEED_CUSTOM_FIELDS], true);
            foreach ($this->getOverrideFields() as $fieldname) {
                if (in_array($fieldname, $fields)) {
                    $attributes[$fieldname] = $this->product->$fieldname;
                }
            }
        }
        foreach ($this->product->getFrontFeatures($this->id_lang) as $feature) {
            $feature['name'] = $this->_clean($feature['name']);
            if (empty($feature['name']) === false) {
                $attributes[$feature['name']] = $feature['value'];
            }
        }
        $fileNumber = 0;
        foreach ($this->product->getAttachments($this->id_lang) as $attachment) {
            $link = Context::getContext()->link->getPageLink('attachment', true, null, 'id_attachment=' . $attachment['id_attachment']);
            $attributes['file-' . ++$fileNumber] = $link;
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

        foreach ($this->product->getAttributeCombinations($this->id_lang) as $combinaison) {
            $combinations[$combinaison['id_product_attribute']]['attributes'][$combinaison['group_name']] = $combinaison['attribute_name'];
            $combinations[$combinaison['id_product_attribute']]['ean13'] = $combinaison['ean13'];
            $combinations[$combinaison['id_product_attribute']]['upc'] = $combinaison['upc'];
            $combinations[$combinaison['id_product_attribute']]['quantity'] = $combinaison['quantity'];
            $combinations[$combinaison['id_product_attribute']]['weight'] = $combinaison['weight'];
            $combinations[$combinaison['id_product_attribute']]['reference'] = $combinaison['reference'];
            $combinations[$combinaison['id_product_attribute']]['wholesale_price'] = $combinaison['wholesale_price'];
        }

        foreach ($combinations as $id => $combination) {
            set_time_limit(600);
            asort($combination['attributes']);
            $image_child = true;
            $sfp->id_product_attribute = $id;

            $variation = [
                'reference' => $sfModule->mapReference($sfp),
                'images' => [],
                'shipping' => [
                    'label' => $carrier->delay[$this->id_lang],
                ]
            ];

            if (empty($combination['ean13']) === false) {
                $variation['gtin'] = $combination['ean13'];
            }
            if (empty($combination['upc']) === false) {
                $variation['attributes']['upc'] = $combination['upc'];
            }
            if (empty($combination['weight']) === false && $combination['weight'] != 0) {
                $variation['attributes']['weight'] = $combination['weight'];
                $variation['attributes']['weight'] = (float)$this->product->weight + (float)$combination['weight'];

            }
            if (empty($combination['wholesale_price']) === false && $combination['wholesale_price'] != 0) {
                $variation['attributes']['wholesale_price'] = $combination['wholesale_price'];
            }
            if (empty($combination['reference']) === false) {
                $variation['attributes']['ref-constructeur'] = $combination['reference'];
            }

            $variation['attributes']['link-variation'] = Context::getContext()->link->getProductLink($this->product, null, null, null, null, null, (int)$id, false, false, true);

            foreach ($this->_getAttributeImageAssociations($id) as $image) {
                if (empty($image)) {
                    $image_child = false;
                    break;
                }

                $variation['images'][] = Tools::getCurrentUrlProtocolPrefix(). $this->link->getImageLink($this->product->link_rewrite, $this->product->id.'-'.$image, $this->configurations[Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT]);
            }
            if ($image_child === false) {
                foreach ($this->product->getImages($this->id_lang) as $images) {
                    $ids = $this->product->id.'-'.$images['id_image'];
                    $variation['images'][] = Tools::getCurrentUrlProtocolPrefix().$this->link->getImageLink($this->product->link_rewrite, $ids, $this->configurations[Shoppingfeed::PRODUCT_FEED_IMAGE_FORMAT]);
                }
            }
            foreach ($combination['attributes'] as $attributeName => $attributeValue) {
                $attributeName = $this->_clean($attributeName);
                if ( empty($attributeName) === false && ( empty($attributeValue) === false || $attributeValue === '0' ) ) {
                    $variation['attributes'][$attributeName] = $attributeValue;
                }
            }
            $variations[$id] = $variation;
        }

        return $variations;
    }

    protected function _getFilAriane()
    {
        $categories = [];
        $sqlAppend = 'FROM ' . _DB_PREFIX_ . 'category c
			JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (c.id_category = cl.id_category
            AND `id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('cl', $this->id_shop) . ')';
        $category_default = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT c.nleft, c.nright ' . $sqlAppend . ' WHERE c.id_category = ' . (int) $this->product->id_category_default
        );

        if (empty($category_default)) {
            return '';
        }
        $categories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            sprintf(
                'SELECT cl.name %s WHERE c.nleft <= %d AND c.nright >= %d ORDER BY c.nleft ASC',
                $sqlAppend,
                (int) $category_default['nleft'],
                (int) $category_default['nright']
            )
        );
        $categoryTree = array_column($categories, 'name');

        return implode(' > ', $categoryTree);
    }

    protected function getShippingCost($carrier, $address, $id_product_attribute)
    {
        $cart = new Cart();
        $country = new Country($address->id_country);
        $product = [
            'id_address_delivery' => $address->id,
            'id_product' => $this->product->id,
            'id_product_attribute' => $id_product_attribute,
            'cart_quantity' => 1,
            'id_shop' => $this->id_shop,
            'id_customization' => 0,
            'is_virtual' => $this->product->is_virtual,
            'weight' => $this->product->weight,
            'additional_shipping_cost' => $this->product->additional_shipping_cost,
        ];

        return $cart->getPackageShippingCost(
            (int)$carrier->id,
            true,
            $country,
            [$product],
            $country->id_zone
        );
    }

    protected function _getCategory()
    {
        $category = new \Category($this->product->id_category_default, $this->id_lang, $this->id_shop);
        return $category->name;
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


	protected static function _getScoreQuery($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_customer)
	{
		$select = '(';

		$priority = SpecificPrice::getPriority($id_product);
		foreach (array_reverse($priority) as $k => $field) {
			if (!empty($field)) {
				$select .= ' IF (`'.bqSQL($field).'` = '.(int)$$field.', '.pow(2, $k + 1).', 0) + ';
            }
        }

		return rtrim($select, ' +').') AS `score`';
	}

    /**
     * Can not use SpecificPrice::getByProductId() because that method does not take into
     * account specific price for all product (id_product=0)
     *
     * @return array
     */
    protected function getSpecificPriceInfo($id_group, $id_country, $id_product_attribute = false)
    {
        $query = (new DbQuery())
            ->select('*, ' . self::_getScoreQuery($this->product->id, $this->id_shop, $this->id_currency, $id_country, $id_group, null))
            ->from('specific_price')
            ->where(sprintf('id_product IN (%d, 0)', (int)$this->product->id))
            ->where(sprintf('id_shop IN (%d, 0)', (int)$this->id_shop))
            ->where(sprintf('id_currency IN (%d, 0)', (int)$this->id_currency))
            ->where(sprintf('id_country IN (%d, 0)', (int)$id_country))
            ->where(sprintf('id_group IN (%d, 0)', (int)$id_group))
            ->where(sprintf('IF(from_quantity > 1, from_quantity, 0) <= %d', 1))
            ->orderBy('`id_product_attribute` DESC')
            ->orderBy('`id_cart` DESC')
            ->orderBy('`from_quantity` DESC')
            ->orderBy('`id_specific_price_rule` ASC')
            ->orderBy('`score` DESC')
            ->orderBy('`to` DESC')
            ->orderBy('`from` DESC');

        if ($id_product_attribute) {
            $query->where('id_product_attribute = ' . (int)$id_product_attribute);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * @param int $idProductAttribute
     *
     * @return array
     */
    protected function getSpecificPrices($address, $product_tax_calculator, $id_group, $priceWithoutReduction, $id_product_attribute = null)
    {
        $return = [];

        if (Validate::isLoadedObject($this->product) === false) {
            return $return;
        }
        $specificPrices = $this->getSpecificPriceInfo($id_group, $address->id_country);

        if (empty($specificPrices)) {
            return $return;
        }

        foreach ($specificPrices as $specificPrice) {
            $skip = false;

            if ((int)$specificPrice['id_product_attribute'] !== 0) {
                if ((int)$id_product_attribute !== (int)$specificPrice['id_product_attribute']) {
                    $skip = true;
                }
            }

            // Apply a specific price of a default variation for a product
            if ((int)$id_product_attribute === 0 && $skip) {
                if ($this->product->getDefaultIdProductAttribute() === (int)$specificPrice['id_product_attribute']) {
                    $skip = false;
                }
            }

            if ($skip) {
                continue;
            }

            if ($specificPrice['reduction_type'] == 'amount') {
                $reduction_amount = $specificPrice['reduction'];
                if (!$specificPrice['id_currency']) {
                    $reduction_amount = Tools::convertPrice($reduction_amount, $this->id_currency);
                }
                $specific_price_reduction = $reduction_amount;
                if (!$specificPrice['reduction_tax']) {
                    $specific_price_reduction = $product_tax_calculator->addTaxes($specific_price_reduction);
                }
            } else {
                $specific_price_reduction = $priceWithoutReduction * $specificPrice['reduction'];
            }
            $price = $priceWithoutReduction - $specific_price_reduction;
            $price = Tools::ps_round($price, 2);
            if ($price < 0) {
                $price = 0;
            }
            $specificPrice['discount'] = $price;
            $return[] = $specificPrice;
        }

        return $return;
    }
}
