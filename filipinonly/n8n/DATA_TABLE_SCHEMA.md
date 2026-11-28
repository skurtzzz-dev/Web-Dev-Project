# MySQL Schema

n8n Data Tables are no longer used as storage. Use `database/schema.sql` and `database/migrate.php` instead.

## Tables

- `users` — accounts, hashed passwords, profile fields, address fields, Google subject ID, timestamps.
- `orders` — receipt number, linked user, customer snapshot, address and branch snapshot, delivery details, payment details, status, totals, timestamps.
- `order_items` — item snapshot, quantity, serving, line total, customization JSON, image.
- `order_status_history` — status transitions per order.
- `menu_items` — seeded from `data/menu.php`.
- `branches` — seeded from `data/branches.php`.
- `vouchers` — seeded with `SARAP10`.
- `admin_users` — staff login with password hash.

Run:

```bash
D:/xampp/php/php.exe database/migrate.php
```
