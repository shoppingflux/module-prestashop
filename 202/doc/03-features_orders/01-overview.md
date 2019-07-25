---
category: 'Features : Orders (specifications)'
name: 1. Orders overview
---

Most of the process is similar to the products synchronization. However,
updating an order's status leads to the creation of a ticket rather than an
immediate confirmation (see [2. Status synchronization](#2-status-synchronization)).
  
Orders allow only batch synchronization.  

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
This module will use the `validateOrder` hook to detect orders added by the
shoppingfluxexport module, and will then fill the `shoppingfeed_order` table.

<i>To see where the necessary data is stored, check the
`Shoppingfluxexport::hookPostUpdateOrderStatus()` method in the
shoppingfluxexport module.</i>

# Synchronization method

Orders only supports batch synchronization.


# The abstract ShoppingfeedOrderSyncActions class

Updated orders will always be saved in the `shoppingfeed_task_order` table
before being processed.

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