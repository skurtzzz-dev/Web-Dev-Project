# n8n setup for FilipinOnly

1. Start n8n at `http://localhost:5678`.
2. Create the **FilipinOnlyOrders** Data Table using `DATA_TABLE_SCHEMA.md`.
3. Import all JSON files from `workflows/` using n8n's workflow import option.
4. Open each imported workflow and confirm the Data Table node selects `FilipinOnlyOrders` by name. If your n8n version did not resolve the table automatically, re-select it from the Data Table dropdown.
5. Save and activate all four workflows.
6. In `config.php`, set `ORDER_STORAGE_MODE` to `n8n`.
7. Keep `N8N_WEBHOOK_BASE` as `http://127.0.0.1:5678/webhook` when n8n is running on the same Windows PC as XAMPP. If using the test webhook URLs, change the base to `http://127.0.0.1:5678/webhook-test` while you test, then switch back and activate workflows for normal use.

## Included endpoints

- `POST /webhook/filipinonly-order-create` — upsert a complete order by order number.
- `POST /webhook/filipinonly-order-get` — look up one order.
- `POST /webhook/filipinonly-order-list` — return all orders.
- `POST /webhook/filipinonly-order-update` — update the order status and its history.

The PHP application calls these webhooks server-to-server, so browser CORS settings are not needed.

## Notifications and AI

The workflows persist orders and return the data needed by the website. For real email delivery, add an email node (SMTP credential) after the create/update Data Table node. For AI-written messages, add an AI Agent or a local Ollama HTTP Request node after the status update and feed it only the verified status and customer first name. Never let an AI model decide whether an order is paid or delivered.

The demo does not connect to real GCash/card payment services or live rider GPS. The receipt page can be printed or saved as PDF through the browser.
