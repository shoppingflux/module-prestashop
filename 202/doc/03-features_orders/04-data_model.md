---
category: 'Features : Orders'
name: '4. Data Model'
---

# ShoppingFeedOrder

"Extends" a native PrestaShop order. Each line represents an order added by the
shoppingfluxexport module.

| Column                | Type            | Description                         |
|-----------------------|-----------------|-------------------------------------|
| id_shoppingfeed_order | int             | Primary key                         |
| id_internal_shoppingfeed | string       | The order's id on shoppingfeed      |
| id_order_marketplace  | string          | The order's id on the marketplace   |
| name_marketplace      | string          | The order's marketplace             |
| id_order              | int             | The native PrestaShop order id      |
| payment_method        | string          | The payment_method on marketplace   |
| date_marketplace_creation | string      | Creation date on the marketplace    |
| shipped_sent          | bool, default 0 | Was the tracking number ever sent ? |
| date_add              | date            |                                     |
| date_upd              | date            |                                     |

# ShoppingFeedCarrier

"Map" a native PrestaShop carrier with marketplace carriers.

| Column                     | Type                  | Description                                                     |
|----------------------------|-----------------------|-----------------------------------------------------------------|
| id_shoppingfeed_carrier    | int                   | primary key                                                     |
| name_marketplace           | string (50)           | The name of the marketplace (lowercase by convention)           |
| name_carrier               | string (190)          | The native PrestaShop order id                                  |
| id_carrier_reference       | int                   | PrestaShop carrier reference                                    |
| is_new                     | boolean               | Is a new imported carrier ? Set to false when settings are saved |
| date_add                   | date                  |                                                                 |
| date_upd                   | date                  |                                                                 |

# ShoppingFeedTaskOrder

Represents a task to send to the Shopping Feed API. Very similar to the
`shoppingfeed_product` table.

| Column                     | Type                  | Description                                                     |
|----------------------------|-----------------------|-----------------------------------------------------------------|
| id_shoppingfeed_task_order | int                   | primary key                                                     |
| action                     | string (enum)         | The nature of the task; a constant from `ShoppingFeedTaskOrder` |
| id_order                   | int                   | The native PrestaShop order id                                  |
| ticket_number              | string (default NULL) | A Shopping Feed ticket number                                   |
| batch_id                   | string (default NULL) | A Shopping Feed batch id                                        |
| update_at                  | date                  | The date after which this task should be sent                   |
| date_add                   | date                  |                                                                 |
| date_upd                   | date                  |                                                                 |
