---
category: 'Features : Products'
name: Stock synchronization
---

# Overview

A product's stock may change either when modified from the back-office, or
during an order process. The module allows either real-time synchronization, or
batch synchronization.

When real-time synchronization is selected, the module will send a request to
the Shopping Feed API each time a product's stock is updated; this may be
dangerous for shops with a lot of traffic.

When batch synchronization is selected, updated products will be marked as "to
synchronize" in the database. A CRON task will then read this table and send
update requests to the Shopping Feed API with multiple products. The number of
products updated each time the task is run may be changed in the module's
configuration page.

# Detecting changes

Stock changes are detected through the `actionUpdateQuantity` hook. The updated
product is saved as a `ShoppingfeedProduct` using the `ActionsHandler` component
from Classlib; whether the update should be queued or immediately sent to the
Shopping Feed API is managed in the Action.

# Processing changes

Updated products are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

If real-time synchronization is selected, the `ActionsHandler` will execute the
update chain immediately after saving the product. If not, a CRON task managed
with Classlib's `ProcessMonitor` component will start a full chain of action.

