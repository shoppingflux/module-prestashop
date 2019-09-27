---
category: 'Features : Orders'
name: '5. Testing'
---

Testing this feature is a bit tricky. There are 2 ways to create an order on
Shopping Feed's platform :
* Through the platform itself : https://app.shopping-feed.com/v3/fr/orders
* Through the testing API : https://developer.shopping-feed.com/order-api/order/v1store-orderpost
  * The `Authorization` field should be filled with : `Bearer [your_shop_token]`
  * The token and the `storeId` can be found here : https://app.shopping-feed.com/v3/fr/api 

Tests have determined that :
* Orders created using the platform could successfully be imported by the
shoppingfluxexport module, but could not be updated using the new module.  
* Orders created through the API could not be imported by the shoppingfluxexport
module, but could successfully be updated using the new module.

This means that the "hook on import" part of the process cannot be tested using
the same orders as the "update order status" part.

To test the "update order status" process, one must manually add order data in
the `shoppingfeed_task_order` table to map orders from PrestaShop with orders
from Shopping Feed.
