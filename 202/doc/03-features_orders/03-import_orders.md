---
category: 'Features : Orders'
name: 3.1 Import orders
---


The import of new order uses the same CRON task as the order status update.


There is 11 steps to import an order:
* Carrier retrieved
* Order verified
* Customer created
* Customer retrieved from billing address
* Billing address created / updated
* Shipping address created / updated
* Products quantities updated / validated
* Cart created
* Products added to cart
* Order validated
* Order acknowledged with SF API
* Order prices updated

Each step can be intercept by a specipic import rules call back event. [See also](/#2-specific-import-order-rules).


This module supply 10 specific rules documented on the backoffice of the module, menu: "Specific import order rules".
It manage all specific behaviours of a marketplace or carrier.
