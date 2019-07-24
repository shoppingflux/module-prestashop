---
category: 'Features : Orders (specifications)'
name: 1. Orders overview
---

Most of the process is similar to the products synchronization. However,
updating an order's status leads to the creation of a ticket rather than an
immediate confirmation (see [2. Status synchronization](/#2-status-synchronization)).
  
The module allows either real-time synchronization, or batch synchronization.  

Every synchronizable field should have its associated classlib `Actions` class
extending the abstract `ShoppingfeedOrderSyncActions` class.  

The `syncOrderPostImport` controller extending classlib's `CronController` is
responsible for processing update batches.

**Note : this module is not responsible for importing orders from the SF API**
(yet). The module should only synchronize orders imported using the
shoppingfluxexport module.
A new CronController / Action pair will be created (one day, but not today) to
support orders import; most likely named `ShoppingfeedOrdersImportActions` to
separate the two features.

# Synchronization method

When real-time synchronization is selected, the module will send a request to
the Shopping Feed API each time a synchronizable order field is updated; this
may be dangerous for shops with a lot of traffic.

Whenever an order is updated, it will be added as "to synchronize" in the
`shoppingfeed_order` table. From there, 2 possibilities :
* If batch synchronization is selected, a CRON task will later read this table
and send update requests to the Shopping Feed API with multiple orders. The
number of updates to process each time the task is run may be changed in the
module's configuration page.
* If real-time synchronization is selected, the requests will be sent
immediately.

The only difference between real-time and batch synchronization lies in _when_
the `ShoppingfeedOrderSync[Field]Actions` will be executed.


# The abstract ShoppingfeedOrderSyncActions class

No matter which synchronization method is selected, an updated order will
always be saved in the `shoppingfeed_order` table before being processed.

Every synchronizable field should have an associated classlib `Actions` class
extending the `ShoppingfeedOrderSyncActions` class. **The class name is
important**; it should be formatted like `ShoppingfeedOrderSync[Field]Actions`
so that adding the new field will be easy in the `syncOrderPostImport` controller
processing batch synchronizations.

```php
class ShoppingfeedOrderSyncStatusActions extends ShoppingfeedOrderSyncActions
{
    // ...
}
```

Since saving and retrieving updates to process is similar for every
synchronizable fields, the abstract `ShoppingfeedOrderSyncActions` is the one
implementing those features. Every "field process" (e.g.
`ShoppingfeedOrderSyncStatusActions`) should implement the remaining methods.
Note that **when real-time synchronization is selected**, the
`ShoppingfeedOrderSyncActions::saveOrder` method will **automatically
forward** to the `ShoppingfeedOrderSyncActions::getBatch` method to run the
synchronization process.


# Processing the updates with batch synchronization

As previously mentioned, the `syncOrderPostImport` controller will process batch
synchronization; it is responsible for checking which fields should be updated.

```php
class ShoppingfeedSyncOrderPostImportModuleFrontController extends CronController
{
    protected function processCron($data)
    {
        // Every field to synchronize should follow this pattern
        if(Configuration::get(Shoppingfeed::ORDER_STATUS_SYNC_ENABLED)) {
            $actions[ShoppingfeedOrder::ACTION_SYNC_STATUS] = array(
                'actions_suffix' => 'Status'
            );
        }

        // ...

        foreach($actions as $action => $actionData) {
            $this->processAction($action, $actionData['actions_suffix']);
        }

        // ...

    }

    // ...
    
    protected function processAction($action, $actions_suffix)
    {
        // ...

        // The 'actions_suffix' is used to find the Actions class
        $handler->setConveyor(array(
            'id_shop' => $shop['id_shop'],
            // The 'action' is the value saved in a ShoppingfeedOrder
            'order_action' => $action,
        ));
        $processResult = $handler->process('ShoppingfeedOrderSync' . $actions_suffix);

        // ...
    }
}
```