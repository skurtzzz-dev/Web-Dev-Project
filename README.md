# FilipinOnly — MySQL + n8n Prototype

Complete setup guide for running FilipinOnly on a new Windows device with XAMPP.

FilipinOnly is a local Filipino-food ordering prototype with a PHP storefront, MySQL database, staff dashboard, printable receipts, order tracking, and optional n8n automation.

MySQL is the **source of truth** for users, orders, menu items, branches, vouchers, and staff accounts. PHP connects to MySQL automatically using `config.php`, so customers do not type database credentials on the website.

n8n is optional automation. It can send receipt/status emails, but the app still works if n8n is offline.

---

## 1. Requirements

Install these first:

- **XAMPP for Windows** with Apache, MySQL/MariaDB, and PHP
- **Modern browser** such as Chrome or Edge
- Optional: **n8n** at `http://localhost:5678`

No Composer package installation is required.

---

## 2. Copy the project to the new device

1. Copy the whole `filipinonly` folder to XAMPP's `htdocs` folder:

   ```text
   C:\xampp\htdocs\filipinonly
   ```

2. Keep the folder name exactly `filipinonly`.

   The localhost links in this README assume this folder name.

3. Make sure these files/folders exist:

   ```text
   C:\xampp\htdocs\filipinonly\index.php
   C:\xampp\htdocs\filipinonly\api.php
   C:\xampp\htdocs\filipinonly\admin.php
   C:\xampp\htdocs\filipinonly\config.php
   C:\xampp\htdocs\filipinonly\database\schema.sql
   C:\xampp\htdocs\filipinonly\database\migrate.php
   C:\xampp\htdocs\filipinonly\lib\db.php
   C:\xampp\htdocs\filipinonly\lib\mysql_store.php
   C:\xampp\htdocs\filipinonly\lib\user_store.php
   C:\xampp\htdocs\filipinonly\lib\order_store.php
   ```

---

## 3. Start XAMPP

1. Open **XAMPP Control Panel**.
2. Start **Apache**.
3. Start **MySQL**.
4. Confirm both show as running.

If Apache or MySQL fails to start:

- Another app may be using port `80` or `3306`.
- Stop the conflicting app, then start Apache/MySQL again.
- In XAMPP Control Panel, click **Config → Net and Ports** to inspect ports.

---

## 4. Set the MySQL password in `config.php`

Open:

```text
C:\xampp\htdocs\filipinonly\config.php
```

Find this section:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'filipinonly');
define('DB_USER', 'root');
define('DB_PASS', 'filipinonly-db-2026');
define('DB_CHARSET', 'utf8mb4');
```

Set `DB_USER` and `DB_PASS` to the MySQL login that works on the new device.

### Common cases

**Fresh XAMPP install:**

XAMPP usually uses:

```text
Username: root
Password: blank
```

So use:

```php
define('DB_USER', 'root');
define('DB_PASS', '');
```

**Current development machine:**

The existing setup uses:

```text
Username: root
Password: filipinonly-db-2026
```

So use:

```php
define('DB_USER', 'root');
define('DB_PASS', 'filipinonly-db-2026');
```

Do not run the migration until this login is correct.

---

## 5. Make phpMyAdmin use the same MySQL login

Open:

```text
C:\xampp\phpMyAdmin\config.inc.php
```

Find these lines:

```php
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = '';
```

Set the password to match `DB_PASS` in `config.php`.

For fresh XAMPP:

```php
$cfg['Servers'][$i]['password'] = '';
```

For the current development machine:

```php
$cfg['Servers'][$i]['password'] = 'filipinonly-db-2026';
```

Save the file, then open:

```text
http://localhost/phpmyadmin/index.php
```

It should open without showing a login form.

If it still shows a login form, use the same `DB_USER` and `DB_PASS` values.

---

## 6. Run the database migration

Open PowerShell:

```powershell
cd C:\xampp\htdocs\filipinonly
C:\xampp\php\php.exe database\migrate.php
```

Expected output:

```text
schema: OK
menu_items: 20
branches: 17
users: 2
orders: 5
order_items: 10
order_status_history: 16
vouchers: 1
admin_users: 1
```

This script:

1. Creates the `filipinonly` database if it does not exist.
2. Creates all tables from `database/schema.sql`.
3. Seeds 20 menu items from `data/menu.php`.
4. Seeds 17 branch rows from `data/branches.php`.
5. Seeds the `SARAP10` voucher.
6. Seeds the admin account.
7. Migrates existing JSON users/orders if those files still exist.

You can run it again safely; it does not intentionally duplicate seed rows.

---

## 7. Open the website

Open:

```text
http://localhost/filipinonly/index.php
```

Main links to remember:

```text
Storefront:  http://localhost/filipinonly/index.php
Admin:       http://localhost/filipinonly/admin.php
Database:    http://localhost/phpmyadmin/index.php
```

Other useful links:

```text
Receipt:     http://localhost/filipinonly/receipt.php?id=FO-KT2601
API state:   http://localhost/filipinonly/api.php?action=state
n8n:         http://localhost:5678
```

Open the API state link. It should show:

```json
"storage": "mysql"
```

---

## 8. Check the database in phpMyAdmin

1. Open:

   ```text
   http://localhost/phpmyadmin/index.php
   ```

2. Select the `filipinonly` database in the left sidebar.

3. Confirm these tables exist:

   ```text
   users
   orders
   order_items
   order_status_history
   menu_items
   branches
   vouchers
   admin_users
   ```

4. Click **Browse** on a table to view rows.

---

## 9. Useful SQL checks

In phpMyAdmin, select `filipinonly`, click the **SQL** tab, and run:

```sql
SELECT COUNT(*) AS users FROM users;
SELECT COUNT(*) AS orders FROM orders;
SELECT COUNT(*) AS items FROM order_items;
SELECT COUNT(*) AS statuses FROM order_status_history;
```

Check sample orders:

```sql
SELECT id, customer_name, customer_email, order_status, total, branch_city, created_at
FROM orders
ORDER BY created_at DESC;
```

Check one order's items:

```sql
SELECT name, price, qty, serving, line_total
FROM order_items
WHERE order_id = 'FO-KT2601';
```

Check one order's status history:

```sql
SELECT status, updated_at
FROM order_status_history
WHERE order_id = 'FO-KT2601'
ORDER BY id ASC;
```

Check stored accounts:

```sql
SELECT id, name, email, phone, city, created_at
FROM users;
```

Passwords are stored as `password_hash`, never as plain text.

---

## 10. Test the full ordering flow

1. Open:

   ```text
   http://localhost/filipinonly/index.php
   ```

2. Register or sign in.
3. Add a dish to the bag.
4. Check out using a valid NCR city such as `Manila` or `Quezon City`.
5. Copy the generated order number, for example `FO-XXXXXXXX`.
6. Open phpMyAdmin → `filipinonly` → `orders` → **Browse**.
7. Confirm the new order has:
   - correct `customer_email`
   - correct `delivery_address`
   - correct `branch_city`
   - `order_status = Order received`
   - correct `total`
8. Open `order_items` and confirm the ordered dishes are listed.
9. Open `order_status_history` and confirm the `Order received` entry exists.

---

## 11. Test order tracking

1. Open:

   ```text
   http://localhost/filipinonly/index.php
   ```

2. Open **Track an order**.
3. Enter one of these sample order numbers:

   ```text
   FO-KT2601
   FO-KT2602
   FO-KT2603
   FO-KT2604
   FO-KT2605
   ```

4. `FO-KT2604` should show an in-progress state.
5. `FO-KT2605` should show a cancelled state.
6. Tracking reads directly from MySQL.

---

## 12. Test the staff dashboard

1. Open:

   ```text
   http://localhost/filipinonly/admin.php
   ```

2. Staff password:

   ```text
   filipinonly-admin-2026
   ```

3. Confirm the dashboard shows:

   ```text
   Storage: mysql
   ```

4. Change an order status to `Preparing`, `On the way`, `Delivered`, or `Cancelled`.
5. In phpMyAdmin, run:

   ```sql
   SELECT id, order_status, updated_at, delivered_at
   FROM orders
   WHERE id = 'FO-KT2601';
   ```

   ```sql
   SELECT status, updated_at
   FROM order_status_history
   WHERE order_id = 'FO-KT2601'
   ORDER BY id ASC;
   ```

6. The new status should appear in both `orders` and `order_status_history`.

---

## 13. Optional: n8n receipt email integration

FilipinOnly works without n8n. MySQL writes are not rolled back if n8n is offline.

To enable receipt automation:

1. Start n8n:

   ```text
   http://localhost:5678
   ```

2. Import these workflow files from `n8n/workflows/`:

   ```text
   01-create-order.json
   02-get-order.json
   03-list-orders.json
   04-update-order-status.json
   ```

3. Activate only these workflows:

   ```text
   FilipinOnly - 01 Order Created Notification
   FilipinOnly - 04 Order Status Notification
   ```

4. Leave workflows 02 and 03 inactive. They are legacy notice workflows because MySQL now handles reads.

5. In workflow 01, add an **Email Send** node after **Prepare Receipt Email**.

6. Configure the Email Send node:

   ```text
   To:      {{ $json.email.to }}
   Subject: {{ $json.email.subject }}
   HTML:    {{ $json.email.html }}
   ```

7. Configure SMTP credentials in n8n.

8. Connect the Email Send node to **Return Notification Payload**.

9. Place a test order and confirm the receipt email is sent.

Included webhooks:

```text
POST /webhook/filipinonly-order-create
POST /webhook/filipinonly-order-update
POST /webhook/filipinonly-order-get       legacy only
POST /webhook/filipinonly-order-list      legacy only
```

The default webhook base in `config.php` is:

```php
define('N8N_WEBHOOK_BASE', 'http://127.0.0.1:5678/webhook');
```

This works when n8n runs on the same PC as XAMPP.

---

## 14. Optional: bring existing database data to the new device

The migration recreates the sample data automatically.

If you want to copy the exact current database contents:

1. On the old device, open:

   ```text
   http://localhost/phpmyadmin/index.php
   ```

2. Select `filipinonly`.
3. Click **Export**.
4. Choose **Quick** or **Custom**.
5. Export as SQL.
6. On the new device, run the migration first.
7. Open phpMyAdmin on the new device.
8. Select `filipinonly`.
9. Click **Import**.
10. Upload the exported SQL file.

Do not import into a different database name unless you also update `DB_NAME` in `config.php`.

---

## 15. Troubleshooting

### phpMyAdmin says `Cannot connect: invalid settings`

Cause: phpMyAdmin's saved password does not match MySQL.

Fix:

1. Open `C:\xampp\phpMyAdmin\config.inc.php`.
2. Set `$cfg['Servers'][$i]['password']` to the correct MySQL password.
3. Refresh with **Ctrl+F5**.

### phpMyAdmin says `Access denied ... using password: NO`

Cause: MySQL has a password, but phpMyAdmin is sending a blank password.

Fix:

- Set phpMyAdmin config password to the correct password, or
- Use the login form with `root` and the correct password.

### Website says `Database connection failed`

Cause: MySQL is not running or `config.php` has the wrong login.

Fix:

1. Start MySQL in XAMPP.
2. Check `DB_USER` and `DB_PASS` in `config.php`.
3. Rerun:

   ```powershell
   C:\xampp\php\php.exe database\migrate.php
   ```

### API state does not show `storage: mysql`

Fix:

1. Open `config.php`.
2. Confirm:

   ```php
   define('ORDER_STORAGE_MODE', 'mysql');
   ```

### n8n webhook does not respond

Fix:

1. Start n8n.
2. Import workflows.
3. Activate workflows 01 and 04.
4. Confirm `N8N_WEBHOOK_BASE` in `config.php`.

### Receipt links only work on localhost

Localhost links only work on the machine running XAMPP. For real recipients, host the project publicly or have n8n generate/send a PDF or inline HTML receipt email.

---

## 16. Project structure

```text
filipinonly/
├── index.php
├── api.php
├── admin.php
├── receipt.php
├── config.php
├── README.md
├── database/
│   ├── schema.sql
│   └── migrate.php
├── lib/
│   ├── db.php
│   ├── mysql_store.php
│   ├── user_store.php
│   └── order_store.php
├── data/
│   ├── menu.php
│   └── branches.php
├── n8n/
│   ├── README.md
│   ├── DATA_TABLE_SCHEMA.md
│   └── workflows/
│       ├── 01-create-order.json
│       ├── 02-get-order.json
│       ├── 03-list-orders.json
│       └── 04-update-order-status.json
└── assets/
    ├── css/
    └── js/
```

---

## 17. Notes for defense

- MySQL is the source of truth.
- PHP reads/writes MySQL first, then optionally notifies n8n.
- n8n does not store orders anymore.
- Customer passwords and staff passwords are hashed.
- GCash, Maya, and card selections are simulated; no real payment is collected.
- Email delivery requires SMTP credentials in n8n.
- Gmail sign-in requires a Google Client ID in `config.php`.
- Food images and fonts need internet access, but ordering and MySQL storage work locally.
