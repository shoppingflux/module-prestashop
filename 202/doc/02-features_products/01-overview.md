---
category: 'Features : Products'
name: 1. Products overview
---
  
The module allows either real-time synchronization, or batch synchronization.  

Every synchronizable field should have its associated classlib `Actions` class
extending the abstract `ShoppingfeedProductSyncActions` class.  

The `syncProduct` controller extending classlib's `CronController` is
responsible for processing update batches.

# Synchronization method

When real-time synchronization is selected, the module will send a request to
the Shopping Feed API each time a synchronizable product field is updated; this
may be dangerous for shops with a lot of traffic.

Whenever a product is updated, it will be added as "to synchronize" in the
`shoppingfeed_product` table. From there, 2 possibilities :
* If batch synchronization is selected, a CRON task will later read this table
and send update requests to the Shopping Feed API with multiple products. The
number of updates to process each time the task is run may be changed in the
module's configuration page.
* If real-time synchronization is selected, the requests will be sent
immediately.

The only difference between real-time and batch synchronization lies in _when_
the `ShoppingfeedProductSync[Field]Actions` will be executed.

# The abstract ShoppingfeedProductSyncActions class

No matter which synchronization method is selected, an updated product will
always be saved in the `shoppingfeed_product` table before being processed.

Every synchronizable field should have an associated classlib `Actions` class
extending the `ShoppingfeedProductSyncActions` class. **The class name is
important**; it should be formatted like `ShoppingfeedProductSync[Field]Actions`
so that adding the new field will be easy in the `syncProduct` controller
processing batch synchronizations.

```php
class ShoppingfeedProductSyncStockActions extends ShoppingfeedProductSyncActions
{
    // ...
}
```

Since saving and retrieving updates to process is similar for every
synchronizable fields, the abstract `ShoppingfeedProductSyncActions` is the one
implementing those features. Every "field process" (e.g.
`ShoppingfeedProductSyncStockActions`) should implement the remaining methods.
Note that **when real-time synchronization is selected**, the
`ShoppingfeedProductSyncActions::saveProduct` method will **automatically
forward** to the `ShoppingfeedProductSyncActions::getBatch` method to run the
synchronization process.

# Processing the updates with batch synchronization

As previously mentioned, the `syncProduct` controller will process batch
synchronization; it is responsible for checking which fields should be updated.

```php
class ShoppingfeedSyncProductModuleFrontController extends CronController
{
    protected function processCron($data)
    {
        // Every field to synchronize should follow this pattern
        if(Configuration::get(Shoppingfeed::STOCK_SYNC_ENABLED)) {
            $actions[ShoppingfeedProduct::ACTION_SYNC_STOCK] = array(
                'actions_suffix' => 'Stock'
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
            // The 'action' is the value saved in a ShoppingfeedProduct
            'product_action' => $action,
        ));
        $processResult = $handler->process('shoppingfeedProductSync' . $actions_suffix);

        // ...
    }
}
```