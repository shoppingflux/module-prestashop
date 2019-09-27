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
| id_order_marketplace  | string          | The order's id on the marketplace   |
| name_marketplace      | string          | The order's marketplace             |
| id_order              | int             | The native PrestaShop order id      |
| shipped_sent          | bool, default 0 | Was the tracking number ever sent ? |
| date_add              | date            |                                     |
| date_upd              | date            |                                     |

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
