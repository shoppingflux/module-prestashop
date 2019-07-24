---
category: 'Features : Orders (specifications)'
name: 2. Status synchronization
---

_To be confirmed : when using batch synchronization, only the order's **current**
state will be taken into account to request the update from SF. If the status is
not managed by the API, the order will be removed from the queue (and the
table)._

The order's statuses synchronization feature is already implemented by the
shoppingfluxexport module. Therefore, to avoid conflicts, this module will
unregister the `postUpdateOrderStatus` hook from the shoppingfluxexport module.

_To discuss : should the module also register the hook again when it's
deactivated ?_


# The ticket system

When requesting a status change on an order, the update isn't processed
immediately. Instead, a ticket number is sent in the response by the SF API.
This ticket number must be saved to monitor the update status.  

<i>To be defined : In another table, maybe `shoppingfeed_order_status_ticket` ?
or simply in the same table in a new `ticket_number` column ?</i>


# The order statuses

The order statuses mapping can be edited in the module's configuration page.
For now, the module is supporting the Shipped, Canceled, and Refunded statuses.

Note that the Refunded status can only be used when the order was imported
from some [specific marketplaces](https://developer.shopping-feed.com/order-api/order/v1store-order-operation-refundpost).


# Detecting changes

A order's status may change only when modified from the back-office, since the
orders are imported by the shoppingfluxexport module.

Status changes are detected in the `actionOrderStatusPostUpdate` hook. The updated
order is saved as a `ShoppingfeedOrder` using the `ActionsHandler` component
from Classlib; whether the update should be queued or immediately sent to the
Shopping Feed API is managed in the abstract Action class.

<i>
To be discussed :
- If using real-time synchronization, and an order is sent to SF by mistake,
should we cancel the update with the API ? Is it even possible ?
- Should **every** update to an eligible status trigger
and update ? If I update an order to "Shipped" then "Canceled", would we save
both changes, or only the last one ?
</i>

# Processing changes

Updated orders are _always_ saved in the database before being sent. The
`ProcessLogger` component from Classlib is used to track every update process.

<i>To see where the necessary data is stored, check the
`Shoppingfluxexport::hookPostUpdateOrderStatus()` method in the
shoppingfluxexport module.</i>

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
  * If the task failed, add the order in the update queue again, and add the
order in the error mail.
  * If the task is still going, don't do anything; leave the order in the SF
table.

It's quite similar to the other processes : get data, process data, send data,
process result.

_To be confirmed : checking the tickets will necessarily be done by a CRON
task._