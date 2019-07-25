---
category: 'Features : Orders (specifications)'
name: 3. Status synchronization
---

The order's statuses synchronization feature is already implemented by the
shoppingfluxexport module. To avoid conflicts, this module will unregister the
`postUpdateOrderStatus` hook from the shoppingfluxexport module (see [The order statuses](#3-status-synchronization-the-order-statuses))

Only the order's **current** state will be taken into account to request the
update from SF. If the status is not managed by the API, the order will be
removed from the queue (and the table).

**Important note :** the Shipped status is closely tied to the order's tracking
number. As a result, updating the tracking number may also result in a status
update (see [Detecting Changes](#2-status-synchronization-detecting-changes))


# The ticket system

When requesting a status change on an order, the update isn't processed
immediately. Instead, a ticket number is sent in the response by the SF API.
This ticket number must be saved to monitor the update status; it will be set
in the `shoppingfeed_task_order`, and the `action` will be updated accordingly.  


# The order statuses

The order statuses mapping can be edited in the module's configuration page.
For now, the module is supporting the Shipped, Canceled, and Refunded statuses.

To avoid conflicts with the shopppingfluxexport module :
* As long as the shoppingfluxexport module is not installed, the merchant won't
be able to activate orders synchronization.
* The module will unhook the shoppingfluxexport module once the merchant has
activated the orders synchronization.

Multiple native PrestaShop order statuses may be selected to trigger an update,
because third-party modules may add their own.

Note that the Refunded status can only be used when the order was imported
from some [specific marketplaces](https://developer.shopping-feed.com/order-api/order/v1store-order-operation-refundpost).


# Detecting changes

To avoid conflicts with the shopppingfluxexport module :
* If the shoppingfluxexport module is not installed or if it's still hooked to the
`postUpdateOrderStatus`, our process will not be executed and an error will be
logged.

Status changes are detected in the `actionOrderStatusPostUpdate` hook. The order
update is saved as a `ShoppingfeedOrderTask` using the `ActionsHandler` component
from Classlib; whether the update should be queued or immediately sent to the
Shopping Feed API is managed in the abstract Action class.

**Important note :** We'll also need to detect changes on the
`order_carrier.tracking_number` field.
* If the order's tracking number changed, then we need to update it as Shipped,
no matter the status. We'll also set `shoppingfeed_order.shipped_sent` to `1`.
* If the order's status has changed to one of Shipped :
  * If `shoppingfeed_order.shipped_sent` is set to `1`, then the status is
    already changing; don't do anything.
  * If we don't have a tracking number, the update will fail and the merchant
    will be notified.

To detect changes on the `tracking_number` field, we must use the
`actionObjectOrderCarrierUpdateBefore` hook, as there's no process tied to this
field. This is very similar to detecting
[products price changes](#4-price-synchronization-detecting-changes).


# Processing changes

To avoid conflicts with the shopppingfluxexport module :
* If the shoppingfluxexport module is not installed or if it's still hooked to the
`postUpdateOrderStatus`, our process will not be executed and an error will be
logged.

Updated orders are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

A CRON task managed with Classlib's `ProcessMonitor` component will process the
saved updates using the same chain of actions.

Only the _current_ status of an order will be checked. No need to save
the order status in the update.


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
process result. This process uses the same CRON task as the order status update.