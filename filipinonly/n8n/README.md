# n8n setup for FilipinOnly

MySQL is the source of truth for users, orders, menu items, branches, vouchers, and admin accounts. n8n is used only for automation such as receipt and status messages.

1. Set `DB_USER` and `DB_PASS` in `config.php`.
2. Run `php database/migrate.php` from XAMPP PHP. This creates the `filipinonly` database, tables, seed rows, and imports existing JSON users/orders.
3. Keep `ORDER_STORAGE_MODE` set to `mysql` in `config.php`.
4. Start n8n at `http://localhost:5678`.
5. Import all JSON files from `workflows/`.
6. Activate `01-create-order.json` and `04-update-order-status.json`.
7. Leave `02-get-order.json` and `03-list-orders.json` inactive; they are legacy notice workflows because MySQL now serves reads.
8. Keep `N8N_WEBHOOK_BASE` as `http://127.0.0.1:5678/webhook` when n8n runs on the same Windows PC as XAMPP.
9. Add an Email Send node after **Prepare Receipt Email** in `01-create-order.json` and after **Prepare Status Email** in `04-update-order-status.json`. Configure SMTP credentials in n8n and send to `email.to`, using `email.subject` and `email.html`.

## Included endpoints

- `POST /webhook/filipinonly-order-create` — receives the verified MySQL order and prepares a receipt email payload.
- `POST /webhook/filipinonly-order-update` — receives the verified MySQL status update and prepares a status email payload.
- `POST /webhook/filipinonly-order-get` — legacy notice only; do not activate for MySQL mode.
- `POST /webhook/filipinonly-order-list` — legacy notice only; do not activate for MySQL mode.

The PHP application calls webhooks server-to-server, so browser CORS settings are not needed. Webhook failures do not roll back MySQL writes.

## Tracking with MySQL

- PHP reads and writes `orders`, `order_items`, and `order_status_history` in MySQL.
- The storefront **Track an order** form calls `track_order` in PHP, which reads the order directly from MySQL.
- The staff dashboard calls `admin_update_status` in PHP, which writes `orders` and `order_status_history` in MySQL before notifying n8n.
- Allowed statuses are `Order received`, `Preparing`, `On the way`, `Delivered`, and `Cancelled`; the storefront history groups them as `IN PROGRESS`, `DELIVERED`, and `CANCELLED`.
- For AI-written messages, add an AI Agent or local Ollama HTTP Request node after the Prepare nodes and feed it only the verified status, order number, and customer first name. Never let an AI model decide whether an order is paid or delivered.

The demo does not connect to real GCash/card payment services or live rider GPS. The receipt page can be printed or saved as PDF through the browser.
