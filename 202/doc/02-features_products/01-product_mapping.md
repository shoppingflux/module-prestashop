---
category: 'Features : Products'
name: 1. Mapping to Shopping Feed's catalog
---

The module uses the method `Shoppingfeed::mapReference` to get a product's
reference for the Shopping Feed catalog. By default, this reference is
`{ps_id_product}` (e.g. `1`) or `{ps_id_product}_{ps_id_product_attribute}` if the update
is for a declination (e.g. `1_12`).

To specify a different Shopping Feed reference, one may override the module's
main class and rewrite the `mapReference` method.

Also note that having the method return `false` (weak comparison) will skip the
update for the product.