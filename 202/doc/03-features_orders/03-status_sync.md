---
category: 'Features : Orders (specifications)'
name: 3. Status synchronization
---

The order's statuses synchronization feature is already implemented by the
shoppingfluxexport module. Therefore, to avoid conflicts, this module will
unregister the `postUpdateOrderStatus` hook from the shoppingfluxexport module.

_To discuss : we may want to disable **our** process rather than unhooking the
old module._

When using batch synchronization, only the order's **current** state will be
taken into account to request the update from SF. If the status is not managed
by the API, the order will be removed from the queue (and the table).

**Important note :** the Shipped status is closely tied to the order's tracking
number. As a result, updating the tracking number may also result in a status
update (see [Detecting Changes](/#2-status-synchronization-detecting-changes))

# The ticket system

When requesting a status change on an order, the update isn't processed
immediately. Instead, a ticket number is sent in the response by the SF API.
This ticket number must be saved to monitor the update status; it will be set
in the `shoppingfeed_task_order`, and the `action` will be updated accordingly.  


# The order statuses

The order statuses mapping can be edited in the module's configuration page.
For now, the module is supporting the Shipped, Canceled, and Refunded statuses.

_To be discussed : multiple order statuses may be selected to trigger an update,
because third-party modules may add their own._

Note that the Refunded status can only be used when the order was imported
from some [specific marketplaces](https://developer.shopping-feed.com/order-api/order/v1store-order-operation-refundpost).


# Detecting changes

A order's status may change only when modified from the back-office, since the
orders are imported by the shoppingfluxexport module.

Status changes are detected in the `actionOrderStatusPostUpdate` hook. The order
update is saved as a `ShoppingfeedOrderTask` using the `ActionsHandler` component
from Classlib; whether the update should be queued or immediately sent to the
Shopping Feed API is managed in the abstract Action class.

<i>
To be discussed :
- If using real-time synchronization, and an order is sent to SF by mistake,
should we cancel the update with the API ? Is it even possible ?
</i>

**Important note :** We'll also need to detect changes on the
`order_carrier.tracking_number` field.
* If the order's tracking number changed, then we need to update it as Shipped,
no matter the status. We'll also set `shoppingfeed_order.shipped_sent` to `1`.
* If the order's status has changed to one of Shipped :
  * If `shoppingfeed_order.shipped_sent` is set to `1`, then the status is
    already changing; don't do anything.
  * If we don't have a tracking number, the update will fail and the merchant
    will be notified.

# Processing changes

Updated orders are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

If real-time synchronization is selected, the `ActionsHandler` will execute the
update chain immediately after saving the order. If not, a CRON task managed
with Classlib's `ProcessMonitor` component will process the saved updates using
the same chain of actions.


# Checking the ticket

Another process in the same `ShoppingfeedOrderSyncStatusActions` class will
be used to :
* Check which orders have a ticket,
* Check the ticket status with the SF API,
* Act accordingly :
  * If the task succeeded, delete the order from the SF table.
  * If the task failed, add the order in the error mail.
  * If the task is still going, don't do anything; leave the order in the SF
table.

It's quite similar to the other processes : get data, process data, send data,
process result.

_To be confirmed : checking the tickets will necessarily be done by a CRON
task._