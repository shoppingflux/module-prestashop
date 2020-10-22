---
category: 'Improve this addon'
name: '1. Internal hooks'
---

We recommand to not use override classes but hooks thinking for developpers.

### Products

* ShoppingfeedMapProductReference:
    * Manage your own refecence of products ID. By default ID sent to shopping feed are `id_product`_`id_attribute` (ex: 125_61)
    * Returns the product's Shopping Feed reference. The developer can skip products to sync by returning false.
* ShoppingfeedMapProduct (reverse of the previous method):
    * Returns the Prestashop product matching the Shopping Feed reference.
    * The developer can skip specific products during order import by overriding this method and have it return false.
* ShoppingfeedMapProductPrice:
    * Returns the product's price sent to the Shopping Feed API.
    * The developer can skip products to sync by overriding this method and have it return false.
    * Note that the comparison with the return value is strict to allow "0" as a valid price.

### Order

* actionShoppingfeedOrderImportRegisterSpecificRules:
    * Adds the order import specific rules class to the manager.
    * Add, remove or extend an order import rule ! Use this hook to declare your own behaviour.
* actionShoppingfeedTracking
    * Manage specific tracking url (shippment) on order feed (for instance URL with name of reciepient like "Relais colis")
* actionShoppingfeedOrderImportCriteria
    * Manage criteria to retrienve new order
        * Skip unacknowledge order
        * Import available Shoppingfeed status: created, waiting_store_acceptance (default), refused, waiting_shipment, shipped, cancelled, refunded, partially_refunded, partially_shipped

### Handler Actions class

This module use a design pattern call "Chain of responsability" on classes/actions direcory.

* actionShoppingfeedActionsHandler:
    * Replace or override any class set on classes/actions
    * You can use obviously namespaces
