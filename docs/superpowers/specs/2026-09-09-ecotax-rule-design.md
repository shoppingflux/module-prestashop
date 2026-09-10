# EcoTaxRule — Design Spec

Date: 2026-09-09
Module: shoppingfeed
Branch: feature/57991-tax-feature

## Purpose

Marketplace orders imported by the shoppingfeed module currently never populate eco-tax on
`order_detail` rows: `ShoppingfeedOrderImportActions::recalculateOrderPrices()` hardcodes
`'ecotax' => 0` for every line, regardless of the product's configured eco-tax. This spec adds a
new order-import rule, **EcoTaxRule**, that lets a merchant opt in to having the eco-tax
configured on the product record carried through onto imported orders and invoices.

The feature is off by default — existing behavior is unchanged unless the merchant explicitly
enables it in the module's order-import-rules configuration.

## Non-goals

- No change to the outbound product feed eco-tax handling (`ProductSerializer`, etc.) — that path
  already works and is untouched.
- No support for eco-tax values sent by the marketplace itself — eco-tax is always sourced from
  the PrestaShop product record ("according to PrestaShop configurations" per the requirement),
  never from marketplace order/product payload fields.

## Rule class: `ShoppingfeedAddon\OrderImport\Rules\EcoTaxRule`

New file `src/OrderImport/Rules/EcoTaxRule.php`, extending `RuleAbstract` and implementing
`RuleInterface`, following the existing `TaxForBusiness` / `OrderDiscountRule` pattern.

- **`isApplicable(OrderResource $apiOrder)`** — returns `(bool) $this->configuration['enabled']`.
  This is a pure merchant-wide display toggle, not conditioned on order data.
- **`getConfigurationSubform()`** — a single `switch` field:
  - `name`: `enabled`
  - `label` (EN): "Eco-tax display" / (FR): "Affichage de l'écotaxe"
  - `desc` (EN): "By enabling this option, the eco-tax specified on product records will be
    displayed on your orders and invoices" / (FR): "En activant cette option, l'écotaxe
    renseignée sur les fiches produits sera affichée sur vos commandes et vos factures"
  - `is_bool`: true, `values`: the standard `[{id: ok, value: 1}, {id: ko, value: 0}]` pair used
    by every other switch field in this module.
- **`getDefaultConfiguration()`** — `['enabled' => false]`.
- **`getConditions()`** — short EN/FR string describing when the rule fires (e.g. "If the option
  'Eco-tax display' is enabled"), shown in the read-only rules list.
- **`getDescription()`** — short EN/FR string (e.g. "Display product eco-tax on imported orders
  and invoices"), shown in the read-only rules list.
- **`beforeRecalculateOrderPrices($params)`** — sets `$params['isUseEcotax'] =
  (bool) $this->configuration['enabled'];`. This mirrors exactly how `TaxForBusiness` sets
  `$params['isUseSfTax']`/`$params['skipTax']` by reference on the same hook.

## Registration

Add `ShoppingfeedAddon\OrderImport\Rules\EcoTaxRule::class` to the `$defaultRulesClassNames` array
inside `hookActionShoppingfeedOrderImportRegisterSpecificRules()` in `shoppingfeed.php`. No other
registration/autoload step is needed (PSR-4 resolves the class from `src/OrderImport/Rules/`).

## Changes to `ShoppingfeedOrderImportActions::recalculateOrderPrices()`

1. Initialize `$isUseEcotax = false;` alongside the existing `$isAmountTaxIncl`, `$skipTax`,
   `$isUseSfTax`, `$discounts` locals, and add `'isUseEcotax' => &$isUseEcotax,` to the
   `$this->specificRulesManager->applyRules('beforeRecalculateOrderPrices', [...])` params array.

2. Inside the per-line loop, when `$isUseEcotax` is `false` (default), behavior is byte-for-byte
   identical to today: `'ecotax' => 0` in `$updateOrderDetail`, no other math touched.

3. When `$isUseEcotax` is `true`, for each order line:
   - **Resolve the eco-tax base value** the same way PrestaShop core resolves it for a cart line
     (`Cart::getProducts()` convention): if the line has a `product_attribute_id` and that
     combination's `product_attribute_shop.ecotax` is set and `> 0`, use it; otherwise fall back
     to the product's own `product_shop.ecotax` (i.e. `$psProduct->ecotax`).
   - **Resolve the eco-tax rate** via `Tax::getProductEcotaxRate()` (driven by the
     `PS_ECOTAX_TAX_RULES_GROUP_ID` configuration) — this is what "according to PrestaShop
     configurations" refers to concretely.
   - **Set `order_detail.ecotax`** (per-unit, tax-excluded — matching the convention in
     `OrderDetail::setProductTax()`) and **`order_detail.ecotax_tax_rate`** from the values above,
     replacing the hardcoded `0`.
   - **Leave `total_price_tax_incl` / `unit_price_tax_incl` exactly as computed today** — the
     tax-included total a customer paid, as sent by the marketplace, does not change.
   - **Adjust `total_price_tax_excl` / `unit_price_tax_excl` downward** to net out the eco-tax's
     own tax-inclusive contribution from the (unchanged) tax-incl total. Concretely, with
     `$ecotaxExcl` / `$ecotaxRate` as resolved above, `$ecotaxIncl = $ecotaxExcl * (1 +
     $ecotaxRate / 100)`, and `$tax_rate` the line's existing product tax rate:

     ```
     unit_price_tax_excl = (($apiProduct->unitPrice - $ecotaxIncl) / (1 + $tax_rate / 100)) + $ecotaxExcl
     total_price_tax_excl = unit_price_tax_excl * $apiProduct->quantity
     ```

     i.e. the fixed tax-incl unit price is split into a pure-product portion
     (`unitPrice - ecotaxIncl`, de-taxed at the product's own rate) plus the eco-tax's own
     tax-excl base — which is exactly how `order_detail.ecotax` is defined as a component
     separate from the product's own tax-excl price elsewhere in PrestaShop core. This will be
     verified against a real order + invoice during implementation.

4. Order/invoice-level aggregates (`total_products`, `total_paid_tax_excl`, `order_invoice`
   totals) are expected to fall out correctly since they're built by summing the per-line
   `total_price_tax_excl`/`total_price_tax_incl` values already computed in the loop. This will be
   confirmed against the actual aggregation code during implementation; if any aggregate is
   computed independently of the per-line sum, it must be adjusted to stay consistent with the
   per-line values above (tax-excl total moves, tax-incl total does not).

## Translations

Add `EcoTaxRule` domain entries to `translations/fr.php` for every new `$this->l(..., 'EcoTaxRule')`
string (label, help text, conditions, description), keyed
`<{shoppingfeed}prestashop>ecotaxrule_<md5(english_source_string)>`, matching the existing
per-rule translation convention (see `orderdiscountrule_*` / `taxforbusiness_*` entries).

## Testing

Manual verification (no automated test suite exists for these rule classes in this module):

1. **Disabled (default):** import/recalculate an order with an eco-taxed product — confirm
   `order_detail.ecotax` is `0` and all totals are identical to current behavior.
2. **Enabled, simple product:** enable the rule, recalculate an order containing a product with a
   product-level eco-tax configured — confirm `order_detail.ecotax`/`ecotax_tax_rate` are
   populated, `total_price_tax_excl`/`unit_price_tax_excl` decrease accordingly, and
   `total_price_tax_incl`/`unit_price_tax_incl`/order `total_paid` are unchanged.
   Check the eco-tax appears correctly on the generated invoice.
3. **Enabled, product with combination-level eco-tax override:** confirm the combination's
   eco-tax is used instead of the base product's.
4. **Toggle round-trip:** verify the admin config page for order import rules shows the new
   "Eco-tax display" switch with the correct EN/FR label and help text, saves correctly, and
   defaults to off for merchants who haven't touched it.
