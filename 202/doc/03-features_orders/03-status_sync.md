---
category: 'Features : Orders'
name: 3. Status synchronization
---

The order's statuses synchronization feature is already implemented by the
shoppingfluxexport module. To avoid conflicts, this module will not run any
order synchronization while the shoppingfluxexport module is configured to run
order synchronization.
(see [The order statuses](#3-status-synchronization-the-order-statuses))

Only the order's **current** state will be taken into account to request the
update from SF. If the order's current state is not managed by the API, the
order will be removed from the queue (i.e. the `shoppingfeed_task_order` table).

**Important note :** order updates must be delayed when a Shipped status change
is triggered, since the API requires a tracking number to register it, and it
may not have been set by the merchant when the status changes
(see [Detecting changes](#3-status-synchronization-detecting-changes)).  
The exact delay can be configured from the back-office (default is 5 minutes).


# The ticket system

When requesting a status change on an order, the update isn't processed
immediately. Instead, a ticket number is returned by the SF API.
This ticket number must be saved to monitor the update status; it will be set
in the `shoppingfeed_task_order`, and the `action` will be updated accordingly.  


# The order statuses

The order statuses mapping can be edited in the module's configuration page.
For now, the module supports the Shipped, Canceled, and Refunded statuses.

To avoid conflicts with the shopppingfluxexport module :
* As long as the shoppingfluxexport module is not installed, the merchant won't
be able to activate orders synchronization. If it's already activated, an error
message will be logged by the CRON task.
* As long as the shoppingfluxexport module is configured to synchronize orders,
the merchant won't be able to activate orders synchronization. If it's already
activated, an error message will be logged by the CRON task.

Multiple native PrestaShop order statuses may be selected to trigger an update,
because third-party modules may add their own.

Note that the Refunded status update may only work when the order was imported
from some [specific marketplaces](https://developer.shopping-feed.com/order-api/order/v1store-order-operation-refundpost).


# Detecting changes

To avoid conflicts with the shopppingfluxexport module :
* If the shoppingfluxexport module is not installed or if it's still configured
to synchronize orders, our process will not be executed and an error will be
logged.

Status changes are detected in the `actionOrderStatusPostUpdate` hook. The order
update is saved as a `ShoppingfeedTaskOrder` using the `ActionsHandler` component
from Classlib.

When a Shipped status change is detected, the order update processing must be
delayed to give the merchant some time to fill the order's tracking number.
The exact delay can be configured from the back-office (default is 5 minutes).

<i>The `update_at` field must be filled accordingly : `time() + delay_in_secondes`</i>

# Processing changes

To avoid conflicts with the shopppingfluxexport module :
* If the shoppingfluxexport module is not installed or is still configured
to synchronize orders, our process will not be executed and an error will be
logged.

Orders updates are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every synchronization
process.

A CRON task managed with Classlib's `ProcessMonitor` component will process the
saved updates using the chain of actions.

Only the _current_ status of an order will be checked. No need to save
the order status in the update.

When requesting an order status update, the SF API will return a ticket number
and a batch id. The ticket number must be saved to check it later; as we're not
sure of the batch id's purpose, it will be saved as well.


# Checking the ticket

Another process in the same `ShoppingfeedOrderSyncActions` class will
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