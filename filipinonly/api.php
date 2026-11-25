<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/lib/order_store.php';

$menu = require __DIR__ . '/data/menu.php';
$menuById = [];
foreach ($menu as $item) $menuById[$item['id']] = $item;
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function api_respond(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function api_require_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$token)) api_respond(['ok' => false, 'message' => 'Your session token expired. Refresh the page.'], 419);
}
function api_require_admin(): void {
    if (empty($_SESSION['filipinonly_admin'])) api_respond(['ok' => false, 'message' => 'Please sign in to the staff dashboard.'], 401);
}
function cart_details(array $cart, array $menuById): array {
    $items = []; $subtotal = 0.0;
    foreach ($cart as $id => $entry) {
        if (!isset($menuById[$id])) continue;
        $product = $menuById[$id];
        $qty = max(1, min(20, (int)($entry['qty'] ?? 1)));
        $line = (float)$product['price'] * $qty;
        $subtotal += $line;
        $items[] = [
            'id' => $id, 'name' => $product['name'], 'price' => (float)$product['price'], 'qty' => $qty,
            'image' => $product['image'], 'line_total' => $line,
            'custom' => $entry['custom'] ?? ['removed' => [], 'rice' => 'Garlic rice', 'spice' => 'Regular'],
        ];
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'count' => array_sum(array_column($items, 'qty'))];
}

$action = $_GET['action'] ?? '';
if ($action === 'state') {
    $cart = cart_details($_SESSION['cart'], $menuById);
    $discount = ($_SESSION['voucher'] ?? '') === 'SARAP10' && $cart['subtotal'] >= 299 ? round($cart['subtotal'] * 0.10, 2) : 0;
    api_respond([
        'ok' => true, 'csrf' => $_SESSION['csrf'], 'cart' => $cart,
        'discount' => $discount, 'voucher' => $_SESSION['voucher'] ?? '',
        'total' => max(0, $cart['subtotal'] - $discount),
        'user' => $_SESSION['user'] ?? null,
        'latest_order' => $_SESSION['last_order'] ?? null,
        'storage_mode' => ORDER_STORAGE_MODE,
    ]);
}

api_require_csrf();
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$action = (string)($payload['action'] ?? '');

try {
    switch ($action) {
        case 'add_to_cart':
            $id = (string)($payload['id'] ?? '');
            if (!isset($menuById[$id])) api_respond(['ok' => false, 'message' => 'That dish is not on the menu.'], 404);
            $qty = max(1, min(20, (int)($payload['qty'] ?? 1)));
            $custom = $payload['custom'] ?? [];
            $_SESSION['cart'][$id] = [
                'qty' => min(20, (int)($_SESSION['cart'][$id]['qty'] ?? 0) + $qty),
                'custom' => [
                    'removed' => array_values(array_filter((array)($custom['removed'] ?? []), 'is_string')),
                    'rice' => in_array(($custom['rice'] ?? 'Garlic rice'), ['Garlic rice', 'White rice', 'Brown rice', 'No rice'], true) ? $custom['rice'] : 'Garlic rice',
                    'spice' => in_array(($custom['spice'] ?? 'Regular'), ['Mild', 'Regular', 'Spicy'], true) ? $custom['spice'] : 'Regular',
                ],
            ];
            unset($_SESSION['voucher']);
            api_respond(['ok' => true, 'message' => $menuById[$id]['name'] . ' added to your bag.']);

        case 'set_quantity':
            $id = (string)($payload['id'] ?? '');
            if (isset($_SESSION['cart'][$id])) {
                $qty = (int)($payload['qty'] ?? 1);
                if ($qty <= 0) unset($_SESSION['cart'][$id]);
                else $_SESSION['cart'][$id]['qty'] = min(20, $qty);
            }
            api_respond(['ok' => true]);

        case 'remove_from_cart':
            unset($_SESSION['cart'][(string)($payload['id'] ?? '')]);
            api_respond(['ok' => true]);

        case 'apply_voucher':
            $code = strtoupper(trim((string)($payload['code'] ?? '')));
            $cart = cart_details($_SESSION['cart'], $menuById);
            if ($code !== 'SARAP10') api_respond(['ok' => false, 'message' => 'That code is not recognized. Try SARAP10.'], 422);
            if ($cart['subtotal'] < 299) api_respond(['ok' => false, 'message' => 'Add at least ₱299.00 to use this voucher.'], 422);
            $_SESSION['voucher'] = $code;
            api_respond(['ok' => true, 'message' => 'SARAP10 applied — enjoy 10% off!']);

        case 'checkout':
            $cart = cart_details($_SESSION['cart'], $menuById);
            if (!$cart['items']) api_respond(['ok' => false, 'message' => 'Your bag is empty. Add a dish before checking out.'], 422);
            $name = trim((string)($payload['name'] ?? ''));
            $phone = trim((string)($payload['phone'] ?? ''));
            $email = trim((string)($payload['email'] ?? ''));
            $address = trim((string)($payload['address'] ?? ''));
            $landmark = trim((string)($payload['landmark'] ?? ''));
            $delivery = (string)($payload['delivery'] ?? 'ASAP');
            $schedule = trim((string)($payload['schedule'] ?? ''));
            $payment = (string)($payload['payment'] ?? 'Cash on Delivery');
            if ($name === '' || $phone === '' || $address === '') api_respond(['ok' => false, 'message' => 'Please complete your name, phone number, and delivery address.'], 422);
            if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) api_respond(['ok' => false, 'message' => 'Please enter a valid contact number.'], 422);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) api_respond(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
            if (!in_array($delivery, ['ASAP', 'Scheduled'], true)) $delivery = 'ASAP';
            if ($delivery === 'Scheduled' && $schedule === '') api_respond(['ok' => false, 'message' => 'Choose a delivery date and time.'], 422);
            if (!in_array($payment, ['GCash', 'Cash on Delivery', 'Credit/Debit Card'], true)) api_respond(['ok' => false, 'message' => 'Choose a valid payment method.'], 422);

            $discount = ($_SESSION['voucher'] ?? '') === 'SARAP10' && $cart['subtotal'] >= 299 ? round($cart['subtotal'] * 0.10, 2) : 0;
            $deliveryFee = $cart['subtotal'] >= 499 ? 0 : 39;
            $total = max(0, $cart['subtotal'] - $discount + $deliveryFee);
            $orderId = 'FO-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $created = date(DATE_ATOM);
            $order = [
                'id' => $orderId,
                'receipt_number' => 'RCP-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                'created_at' => $created, 'updated_at' => $created,
                'name' => $name, 'phone' => $phone, 'email' => $email,
                'address' => $address, 'landmark' => $landmark,
                'delivery' => $delivery, 'schedule' => $schedule, 'payment' => $payment,
                'payment_status' => $payment === 'Cash on Delivery' ? 'Unpaid - cash on delivery' : 'Not processed - demonstration only',
                'items' => $cart['items'], 'subtotal' => $cart['subtotal'], 'discount' => $discount,
                'delivery_fee' => $deliveryFee, 'total' => $total,
                'status' => 'Order received',
                'status_history' => [['status' => 'Order received', 'updated_at' => $created]],
            ];
            $saved = save_order($order);
            if (!$saved['ok']) api_respond(['ok' => false, 'message' => $saved['message']], 502);
            $_SESSION['last_order'] = $order;
            $_SESSION['cart'] = []; unset($_SESSION['voucher']);
            if (!empty($_SESSION['user'])) {
                $_SESSION['user']['name'] = $name; $_SESSION['user']['phone'] = $phone;
                $_SESSION['user']['email'] = $email ?: ($_SESSION['user']['email'] ?? '');
                $_SESSION['user']['address'] = $address;
            }
            api_respond(['ok' => true, 'message' => 'Order placed successfully!', 'order' => $order, 'storage_mode' => ORDER_STORAGE_MODE]);

        case 'register':
        case 'login':
            $name = trim((string)($payload['name'] ?? ''));
            $email = trim((string)($payload['email'] ?? ''));
            $phone = trim((string)($payload['phone'] ?? ''));
            $password = (string)($payload['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) api_respond(['ok' => false, 'message' => 'Enter a valid email and a password with at least 6 characters.'], 422);
            if ($action === 'register' && $name === '') api_respond(['ok' => false, 'message' => 'Please enter your full name.'], 422);
            // Demo sign-in only. Persistent customer accounts are outside this no-MySQL prototype.
            session_regenerate_id(true);
            $_SESSION['user'] = ['name' => $name ?: explode('@', $email)[0], 'email' => $email, 'phone' => $phone, 'address' => ''];
            api_respond(['ok' => true, 'message' => $action === 'register' ? 'Demo account created for this session.' : 'You are signed in for this session.']);

        case 'update_profile':
            if (empty($_SESSION['user'])) api_respond(['ok' => false, 'message' => 'Sign in to edit your profile.'], 401);
            $name = trim((string)($payload['name'] ?? ''));
            if ($name === '') api_respond(['ok' => false, 'message' => 'Your name cannot be empty.'], 422);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = trim((string)($payload['phone'] ?? ''));
            $_SESSION['user']['address'] = trim((string)($payload['address'] ?? ''));
            api_respond(['ok' => true, 'message' => 'Profile updated for this session.']);

        case 'logout':
            unset($_SESSION['user']);
            api_respond(['ok' => true, 'message' => 'You have been signed out.']);

        case 'track_order':
            $id = strtoupper(trim((string)($payload['id'] ?? '')));
            $order = find_order($id);
            if (!$order) api_respond(['ok' => false, 'message' => 'We could not find that order number. Check it and try again.'], 404);
            api_respond(['ok' => true, 'order' => $order]);

        case 'admin_list_orders':
            api_require_admin();
            api_respond(['ok' => true, 'orders' => all_orders()]);

        case 'admin_update_status':
            api_require_admin();
            $id = strtoupper(trim((string)($payload['id'] ?? '')));
            $status = trim((string)($payload['status'] ?? ''));
            $result = update_order_status($id, $status);
            api_respond($result, $result['ok'] ? 200 : 422);

        default:
            api_respond(['ok' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    api_respond(['ok' => false, 'message' => $e->getMessage()], 502);
}
