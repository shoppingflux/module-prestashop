---
category: 'Features : Products'
name: 3. Stock synchronization
---

# Detecting changes

A product's stock may change either when modified from the back-office, or
during an order process.

Stock changes are detected through the `actionUpdateQuantity` hook. The updated
product is saved as a `ShoppingfeedProduct` using the `ActionsHandler` component
from Classlib; whether the update should be queued or immediately sent to the
Shopping Feed API is managed in the abstract Action class.

# Processing changes

Updated products are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

If real-time synchronization is selected, the `ActionsHandler` will execute the
update chain immediately after saving the product. If not, a CRON task managed
with Classlib's `ProcessMonitor` component will process the saved updates using
the same chain of actions.
