---
category: 'Features : Orders'
name: 2. Mapping to Shopping Feed's catalog
---

Orders are mapped using the `name_marketplace` and `id_order_marketplace`
fields. It should be noted that an `id_order_marketplace` is unique only on the
associated `name_marketplace`, so we need both values to identify an order.