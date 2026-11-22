# FilipinOnly — Filipino Food Ordering Website (Frontend Prototype)

A responsive web-development project based on the FilipinOnly proposal. It includes a storefront, meal-time filters, dish search/sorting, ingredient customization, allergen information, a session cart, checkout, profile/login demo, and order tracking UI.

## Requirements
- PHP 8.0+ (PHP 8.4 tested)
- A modern browser

## Run locally
1. Extract the project folder.
2. Open a terminal in the `filipinonly` folder.
3. Run:

   ```bash
   php -S localhost:8000
   ```

4. Visit **http://localhost:8000**.

## Included files
- `index.php` — page layout and PHP-rendered menu cards
- `api.php` — PHP session API for cart, demo account, checkout, and tracking
- `data/menu.php` — sample Filipino-food menu data
- `assets/css/style.css` — responsive styling
- `assets/js/app.js` — filtering, modal, cart, checkout, profile, and tracking interactions

## Demo behavior and limitations
- Cart, sample account, and orders are stored in the PHP session. They are not saved to a database and may reset when the session ends.
- Payment method selection is a UI demonstration only. No GCash, card, or cash transaction is processed.
- Order tracking shows a sample progress interface. Live rider GPS, rider contact, and email receipts/notifications are not connected.
- Gmail sign-in is not connected.
- Menu photos load from Unsplash, so an internet connection is needed for those images and Google Fonts.
- The sample voucher is `SARAP10` for 10% off when the food subtotal is at least ₱299. Delivery is free at a food subtotal of ₱499; otherwise the demo checkout adds ₱39.

## Suggested next step for your group
For a complete academic submission, connect the PHP API to MySQL and add tables for users, addresses, products, cart/order items, orders, payments, and delivery updates. Use password hashing, server-side validation, protected admin access, and a real payment provider only after proper setup.
