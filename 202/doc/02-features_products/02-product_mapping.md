---
category: 'Features : Products'
name: 2. Mapping to Shopping Feed's catalog
---

The module uses the method `Shoppingfeed::mapReference` to get a product's
reference for the Shopping Feed catalog. By default, this reference is
`{ps_id_product}` (e.g. `1`) or `{ps_id_product}_{ps_id_product_attribute}` if a
declination is updated (e.g. `1_12`).

To specify a different Shopping Feed reference, one may either :
* Override the module's class and rewrite the `mapReference` method,
* Create a module and hook it to the `ShoppingfeedMapProductReference` hook

```php
// The reference is passed... by reference, so that you may tweak it.
Hook::exec(
    'ShoppingfeedMapProductReference', // hook_name
    array(
        'ShoppingFeedProduct' => &$sfProduct,
        'reference' => &$reference
    ) // hook_args
);
```

Note that having the method return `false` (weak comparison) will skip the
update for the product and remove it from the updates list.