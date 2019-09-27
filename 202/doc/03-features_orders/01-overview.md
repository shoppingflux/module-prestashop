---
category: 'Features : Orders'
name: 1. Orders overview
---

Most of the process is similar to the products synchronization. However,
updating an order's status leads to the creation of a ticket rather than an
immediate confirmation (see [2. Status synchronization](#2-status-synchronization)).
  
Orders allow only batch synchronization.  

The `syncOrder` controller extending classlib's `CronController` is
responsible for processing update batches.

**Note : this module is not responsible for importing orders from the SF API**
(yet). The module should only synchronize orders imported using the
shoppingfluxexport module.  
A new CronController / Action pair will be created (one day, but not today) to
support orders import; most likely named `ShoppingfeedOrdersImportActions` to
separate the two features.  

This module will use the `validateOrder` hook to detect orders added by the
shoppingfluxexport module, and will then add a row in the `shoppingfeed_order`
table. Data that couldn't be retrieved in the `validateOrder` hook will be added
once an order synchronization is registered.

<i>To see where the necessary data is stored, check the
`Shoppingfluxexport::hookPostUpdateOrderStatus()` method in the
shoppingfluxexport module.</i>

# Synchronization method

Orders only supports batch synchronization.


# The ShoppingfeedOrderSyncActions class

Updated orders will always be saved in the `shoppingfeed_task_order` table
before being processed.

```php

use ShoppingfeedClasslib\Actions\DefaultActions;

class ShoppingfeedOrderSyncActions extends DefaultActions
{
    // ...
}
```

# Processing the updates with batch synchronization

As previously mentioned, the `syncOrder` controller will process batch
synchronization; it is responsible for checking which fields should be updated.