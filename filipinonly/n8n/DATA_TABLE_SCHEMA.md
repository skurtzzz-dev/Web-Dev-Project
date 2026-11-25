# Create the n8n Data Table

The workflows in this folder expect one Data Table named **FilipinOnlyOrders**.

In n8n, open **Overview → Data tables → Create Data table → From scratch** and create the table with these exact column names and types. Do not add a custom `id` column; n8n automatically creates its own row ID.

| Column | Type |
|---|---|
| `order_number` | `string` |
| `created_at` | `string` |
| `updated_at` | `string` |
| `customer_name` | `string` |
| `customer_email` | `string` |
| `customer_phone` | `string` |
| `delivery_address` | `string` |
| `landmark` | `string` |
| `delivery_type` | `string` |
| `scheduled_at` | `string` |
| `payment_method` | `string` |
| `payment_status` | `string` |
| `order_status` | `string` |
| `items_json` | `string` |
| `subtotal` | `number` |
| `discount` | `number` |
| `delivery_fee` | `number` |
| `total` | `number` |
| `receipt_number` | `string` |
| `status_history_json` | `string` |


Keep the table and imported workflows in the same n8n project. The included Data Table nodes identify the table by its name (`FilipinOnlyOrders`).

The `items_json` and `status_history_json` columns store JSON text. The PHP application converts them back into arrays when it reads an order.

n8n Data Tables are intended for light-to-moderate storage; the default total data-table storage allowance is 200 MiB per n8n instance. For a larger production ordering platform, use a dedicated database.
