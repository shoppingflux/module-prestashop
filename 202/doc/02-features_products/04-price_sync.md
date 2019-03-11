---
category: 'Features : Products'
name: 4. Price synchronization
---

# Detecting changes

A product's price may change only when modified from the back-office. Since
there is no hook specific to price changes, the
`actionObjectProductUpdateBefore` and `actionObjectCombinationUpdateBefore` are
used to detect price changes.

Only products whose prices were updated are added to the updates list. However,
a combination's price may change due to the "original" product's price changing.  
Therefore, when a product's price change is detected in the
`actionObjectProductUpdateBefore` hook, an `id_product` is saved in the
classlib `Registry` so that the `actionObjectCombinationUpdateBefore` hook can
check whether an updated combination should be added to the updates list.

_Note : if a product or combination is updated, but does not pass Prestashop's
validation, it might still be added to the list if its price has changed. But
this shouldn't happen often, and since it will probably be re-updated with
correct values right away, it shouldn't be a problem._

The updated product is saved as a `ShoppingfeedProduct` using the
`ActionsHandler` component from Classlib; whether the update should be queued or
immediately sent to the Shopping Feed API is managed in the abstract Actions
class.

# Processing changes

Updated products are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

If real-time synchronization is selected, the `ActionsHandler` will execute the
update chain immediately after saving the product. If not, a CRON task managed
with Classlib's `ProcessMonitor` component will process the saved updates using
the same chain of actions.

