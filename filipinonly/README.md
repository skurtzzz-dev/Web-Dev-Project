# FilipinOnly — PHP + n8n Data Tables (No MySQL)

A complete academic prototype for the FilipinOnly Filipino-food ordering system. It keeps the existing storefront and adds persistent order storage, a staff dashboard, order-status updates, printable receipts, and optional n8n Data Tables integration. **No MySQL is required.**

## Requirements

- PHP 8.0 or newer (XAMPP includes PHP)
- A modern browser
- Optional: n8n running locally at `http://localhost:5678` for centralized order storage and workflow automation

## Quick start (works without n8n)

1. Extract the ZIP.
2. Put the `filipinonly` folder inside `C:\xampp\htdocs\`.
3. Open XAMPP Control Panel and start **Apache**. MySQL is not needed.
4. Open `http://localhost/filipinonly/`.
5. Place a test order. In the default `local` mode, orders persist in a JSON file in your operating-system temporary folder.

You can also use PHP's built-in server from the project folder:

```powershell
php -S localhost:8000
```

Then visit `http://localhost:8000`.

## Staff dashboard

Open `http://localhost/filipinonly/admin.php`.

Default demo password: `filipinonly-admin-2026`

Change `ADMIN_PASSWORD` in `config.php` before your presentation. Staff can review incoming orders, update the status (Order received → Preparing → On the way → Delivered), and open the receipt page.

## Receipts

Customers can open **View / Print receipt** from their order tracking card. The receipt page is print-friendly; choose **Print → Save as PDF** in the browser to create a PDF. It does not require Composer or a PDF package.

## Connect n8n Data Tables (optional)

The application starts in local JSON mode. To use n8n as the order store:

1. Start n8n at `http://localhost:5678`.
2. Create a Data Table called `FilipinOnlyOrders` using the exact schema in `n8n/DATA_TABLE_SCHEMA.md`.
3. Import all four JSON workflows in `n8n/workflows/`.
4. Ensure every Data Table node points to `FilipinOnlyOrders`.
5. Activate the four workflows. The production webhook URLs must be active.
6. Edit `config.php` and change:

```php
define('ORDER_STORAGE_MODE', 'n8n');
```

The default webhook base is `http://127.0.0.1:5678/webhook`. If you test workflows with n8n's **Listen for test event**, temporarily change it to `http://127.0.0.1:5678/webhook-test`.

Included webhooks:

- `POST /webhook/filipinonly-order-create`
- `POST /webhook/filipinonly-order-get`
- `POST /webhook/filipinonly-order-list`
- `POST /webhook/filipinonly-order-update`

The PHP application calls n8n from the server, so browser CORS settings are not needed.

## Add AI-written notifications (optional)

The included workflows save and retrieve order records and support status updates. To add AI-written notifications, extend the update workflow after its Data Table node with an AI Agent/model node. Pass only the verified `customer_name`, `order_number`, and `order_status` fields. For emails, add an Email Send node and configure SMTP credentials. Keep the factual order status in PHP/n8n; an AI model should only phrase the notification, not decide whether an order is delivered or paid.

## What is simulated / not connected

- GCash and Credit/Debit Card are payment-method selections only. No payment is collected.
- Cash on Delivery is shown as unpaid until collected; this prototype does not record real cash collection.
- Order status is manually updated by staff. There is no live rider GPS.
- Email delivery needs an SMTP credential and an Email node in n8n.
- Customer registration/login is a session-based academic demo, not persistent account authentication.
- Food images and web fonts load from external sources, so those visuals need internet access. The ordering, local storage, dashboard, and receipt flow work offline after initial page assets are available.

## Project structure

```text
filipinonly/
├── index.php
├── api.php
├── admin.php
├── receipt.php
├── config.php
├── lib/order_store.php
├── data/menu.php
├── assets/css/style.css
├── assets/css/admin.css
├── assets/js/app.js
├── assets/js/admin.js
├── n8n/
│   ├── README.md
│   ├── DATA_TABLE_SCHEMA.md
│   └── workflows/
│       ├── 01-create-order.json
│       ├── 02-get-order.json
│       ├── 03-list-orders.json
│       └── 04-update-order-status.json
└── storage/
    └── README.txt
```

## Notes for project defense

This is a local academic prototype. In local mode, PHP persists orders in a JSON file. In n8n mode, n8n Data Tables persist order records and n8n webhooks provide create, retrieve, list, and update operations. n8n Data Tables are suitable for light-to-moderate storage, not a high-volume production food-delivery platform.
