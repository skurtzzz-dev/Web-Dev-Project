<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/lib/order_store.php';
require_once __DIR__ . '/lib/user_store.php';

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
            'serving' => (string)($product['serving'] ?? '1 serving'), 'image' => $product['image'], 'line_total' => $line,
            'custom' => $entry['custom'] ?? ['removed' => [], 'rice' => 'Garlic rice', 'spice' => 'Regular'],
        ];
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'count' => array_sum(array_column($items, 'qty'))];
}

function normalize_user_email(string $email): string { return strtolower(trim($email)); }
function compose_address(array $parts): string {
    $clean = array_filter(array_map('trim', $parts), fn($value) => $value !== '');
    return implode(', ', $clean);
}
function public_user(array $user): array {
    return [
        'name' => (string)($user['name'] ?? ''),
        'email' => normalize_user_email((string)($user['email'] ?? '')),
        'phone' => (string)($user['phone'] ?? ''),
        'address' => (string)($user['address'] ?? ''),
        'address_line' => (string)($user['address_line'] ?? ''),
        'city' => (string)($user['city'] ?? ''),
        'province' => (string)($user['province'] ?? ''),
        'zip' => (string)($user['zip'] ?? ''),
        'landmark' => (string)($user['landmark'] ?? ''),
        'avatar' => (string)($user['avatar'] ?? ''),
        'created_at' => (string)($user['created_at'] ?? ''),
        'updated_at' => (string)($user['updated_at'] ?? ''),
    ];
}
function normalize_avatar(string $avatar): string|false {
    $avatar = trim($avatar);
    if ($avatar === '') return '';
    if (preg_match('/^https:\/\/\S{1,500}$/', $avatar)) return $avatar;
    if (!preg_match('/^data:image\/(png|jpeg|webp|gif);base64,([A-Za-z0-9+\/=\s]+)$/', $avatar, $matches)) return false;
    $binary = base64_decode(preg_replace('/\s+/', '', $matches[2]), true);
    if ($binary === false || strlen($binary) > 1048576) return false;
    return 'data:image/' . $matches[1] . ';base64,' . base64_encode($binary);
}
function fetch_google_profile(string $token): ?array {
    $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false) return null;
    } else {
        $context = stream_context_create(['http' => [
            'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n",
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
$action = $_GET['action'] ?? '';
if ($action === 'state') {
    if (!empty($_SESSION['user'])) {
        $storedUser = find_user_by_email((string)($_SESSION['user']['email'] ?? ''));
        if ($storedUser) $_SESSION['user'] = public_user($storedUser);
        else unset($_SESSION['user']);
    }
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
            $custom = is_array($payload['custom'] ?? null) ? $payload['custom'] : [];
            $rice = (string)($custom['rice'] ?? 'Garlic rice');
            $spice = (string)($custom['spice'] ?? 'Regular');
            $_SESSION['cart'][$id] = [
                'qty' => min(20, (int)($_SESSION['cart'][$id]['qty'] ?? 0) + $qty),
                'custom' => [
                    'removed' => array_values(array_filter((array)($custom['removed'] ?? []), 'is_string')),
                    'rice' => in_array($rice, ['Garlic rice', 'White rice', 'Brown rice', 'No rice'], true) ? $rice : 'Garlic rice',
                    'spice' => in_array($spice, ['Mild', 'Regular', 'Spicy'], true) ? $spice : 'Regular',
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
            $addressLine = trim((string)($payload['address_line'] ?? ''));
            $city = trim((string)($payload['city'] ?? ''));
            $province = trim((string)($payload['province'] ?? ''));
            $zip = trim((string)($payload['zip'] ?? ''));
            $address = trim((string)($payload['address'] ?? ''));
            if ($address === '') $address = compose_address([$addressLine, $city, $province, $zip]);
            $landmark = trim((string)($payload['landmark'] ?? ''));
            $delivery = (string)($payload['delivery'] ?? 'ASAP');
            $schedule = trim((string)($payload['schedule'] ?? ''));
            $payment = (string)($payload['payment'] ?? 'Cash on Delivery');
            if ($name === '' || $phone === '' || $address === '') api_respond(['ok' => false, 'message' => 'Please complete your name, phone number, and delivery address.'], 422);
            $phoneDigits = preg_replace('/\D+/', '', $phone);
            if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 13) api_respond(['ok' => false, 'message' => 'Please enter a valid contact number.'], 422);
            if (!preg_match('/^\d{4,5}$/', $zip)) api_respond(['ok' => false, 'message' => 'Please enter a valid 4 to 5 digit ZIP code.'], 422);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) api_respond(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
            if (!in_array($delivery, ['ASAP', 'Scheduled'], true)) $delivery = 'ASAP';
            if ($delivery === 'Scheduled' && $schedule === '') api_respond(['ok' => false, 'message' => 'Choose a delivery date and time.'], 422);
            if (!in_array($payment, ['GCash', 'Maya', 'Cash on Delivery', 'Credit/Debit Card'], true)) api_respond(['ok' => false, 'message' => 'Choose a valid payment method.'], 422);

            $discount = ($_SESSION['voucher'] ?? '') === 'SARAP10' && $cart['subtotal'] >= 299 ? round($cart['subtotal'] * 0.10, 2) : 0;
            $deliveryFee = $cart['subtotal'] >= 499 ? 0 : 39;
            $total = max(0, $cart['subtotal'] - $discount + $deliveryFee);
            $orderId = 'FO-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $created = date(DATE_ATOM);
            $branch = branch_for_city($city);
            $order = [
                'id' => $orderId,
                'receipt_number' => 'RCP-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                'created_at' => $created, 'updated_at' => $created, 'delivered_at' => '',
                'name' => $name, 'phone' => $phone, 'email' => $email,
                'address' => $address, 'address_line' => $addressLine, 'city' => $city, 'province' => $province, 'zip' => $zip, 'landmark' => $landmark,
                'branch_city' => (string)($branch['city'] ?? ''), 'branch_area' => (string)($branch['area'] ?? ''), 'branch_address' => (string)($branch['address'] ?? ''),
                'delivery' => $delivery, 'schedule' => $schedule, 'eta' => $delivery === 'Scheduled' ? $schedule : '30–45 minutes', 'payment' => $payment,
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
                $accountEmail = normalize_user_email((string)($_SESSION['user']['email'] ?? ''));
                $updatedUser = update_stored_user($accountEmail, $name, $phone, $address, ['address_line' => $addressLine, 'city' => $city, 'province' => $province, 'zip' => $zip, 'landmark' => $landmark]);
                if ($updatedUser) {
                    $_SESSION['user'] = public_user($updatedUser);
                } else {
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['phone'] = $phone;
                    $_SESSION['user']['address'] = $address;
                    $_SESSION['user']['address_line'] = $addressLine;
                    $_SESSION['user']['city'] = $city;
                    $_SESSION['user']['province'] = $province;
                    $_SESSION['user']['zip'] = $zip;
                    $_SESSION['user']['landmark'] = $landmark;
                }
            }
            api_respond(['ok' => true, 'message' => 'Order placed successfully!', 'order' => $order, 'storage_mode' => ORDER_STORAGE_MODE]);

        case 'email_check':
            $email = normalize_user_email(trim((string)($payload['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) api_respond(['ok' => true, 'available' => false, 'message' => 'Enter a valid email address.']);
            api_respond(['ok' => true, 'available' => find_user_by_email($email) === null]);

        case 'register':
            $name = trim((string)($payload['name'] ?? ''));
            $email = normalize_user_email(trim((string)($payload['email'] ?? '')));
            $phone = trim((string)($payload['phone'] ?? ''));
            $password = (string)($payload['password'] ?? '');
            $phoneDigits = preg_replace('/\D+/', '', $phone);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) api_respond(['ok' => false, 'message' => 'Enter a valid email and a password with at least 6 characters, one letter, and one number.'], 422);
            if (!preg_match("/^[\p{L}][\p{L}\p{M}'’.\\-\s]{1,59}$/u", $name)) api_respond(['ok' => false, 'message' => 'Enter a valid full name with at least 2 letters.'], 422);
            if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 13) api_respond(['ok' => false, 'message' => 'Please enter a contact number with 7 to 13 digits.'], 422);
            if (find_user_by_email($email)) api_respond(['ok' => false, 'message' => 'An account with that email already exists. Sign in instead.'], 409);
            $now = date(DATE_ATOM);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hash === false) api_respond(['ok' => false, 'message' => 'We could not create your account. Please try again.'], 502);
            $user = ['email' => $email, 'name' => $name, 'phone' => $phone, 'address' => '', 'address_line' => '', 'city' => '', 'province' => '', 'zip' => '', 'landmark' => '', 'avatar' => '', 'password_hash' => $hash, 'created_at' => $now, 'updated_at' => $now];
            if (!save_user($user)) api_respond(['ok' => false, 'message' => 'We could not save your account. Please try again.'], 502);
            session_regenerate_id(true);
            $_SESSION['user'] = public_user($user);
            api_respond(['ok' => true, 'message' => 'Account created. You are signed in.']);

        case 'login':
            $email = normalize_user_email(trim((string)($payload['email'] ?? '')));
            $password = (string)($payload['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') api_respond(['ok' => false, 'message' => 'Enter your email and password.'], 422);
            $user = find_user_by_email($email);
            if (!$user || !password_verify($password, (string)($user['password_hash'] ?? ''))) api_respond(['ok' => false, 'message' => 'Incorrect email or password.'], 401);
            session_regenerate_id(true);
            $_SESSION['user'] = public_user($user);
            api_respond(['ok' => true, 'message' => 'Welcome back!']);

        case 'gmail_auth':
            if (GOOGLE_CLIENT_ID === '') api_respond(['ok' => false, 'message' => 'Gmail sign-in needs a Google Client ID in config.php.'], 503);
            $token = trim((string)($payload['access_token'] ?? ''));
            if ($token === '') api_respond(['ok' => false, 'message' => 'Google did not return an access token.'], 401);
            $profile = fetch_google_profile($token);
            $email = normalize_user_email((string)($profile['email'] ?? ''));
            if (!$profile || empty($profile['email_verified']) || !filter_var($email, FILTER_VALIDATE_EMAIL)) api_respond(['ok' => false, 'message' => 'That Gmail account could not be verified.'], 401);
            $user = find_user_by_email($email);
            if (!$user) {
                $now = date(DATE_ATOM);
                $hash = password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
                $avatar = normalize_avatar((string)($profile['picture'] ?? ''));
                $user = ['email' => $email, 'name' => (string)($profile['name'] ?? explode('@', $email)[0]), 'phone' => '', 'address' => '', 'address_line' => '', 'city' => '', 'province' => '', 'zip' => '', 'landmark' => '', 'avatar' => $avatar === false ? '' : $avatar, 'password_hash' => $hash, 'google_sub' => (string)($profile['sub'] ?? ''), 'created_at' => $now, 'updated_at' => $now];
                if (!save_user($user)) api_respond(['ok' => false, 'message' => 'We could not create your account. Please try again.'], 502);
            } else {
                $changed = false;
                if (empty($user['google_sub']) && !empty($profile['sub'])) { $user['google_sub'] = (string)$profile['sub']; $changed = true; }
                if (empty($user['avatar']) && !empty($profile['picture'])) {
                    $avatar = normalize_avatar((string)$profile['picture']);
                    if ($avatar !== false) { $user['avatar'] = $avatar; $changed = true; }
                }
                if ($changed) save_user($user);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = public_user($user);
            api_respond(['ok' => true, 'message' => 'Signed in with Gmail.']);

        case 'update_profile':
            if (empty($_SESSION['user'])) api_respond(['ok' => false, 'message' => 'Sign in to edit your profile.'], 401);
            $name = trim((string)($payload['name'] ?? ''));
            $phone = trim((string)($payload['phone'] ?? ''));
            $addressLine = trim((string)($payload['address_line'] ?? ''));
            $city = trim((string)($payload['city'] ?? ''));
            $province = trim((string)($payload['province'] ?? ''));
            $zip = trim((string)($payload['zip'] ?? ''));
            $landmark = trim((string)($payload['landmark'] ?? ''));
            $address = trim((string)($payload['address'] ?? ''));
            if ($address === '') $address = compose_address([$addressLine, $city, $province, $zip]);
            $phoneDigits = preg_replace('/\D+/', '', $phone);
            if ($name === '') api_respond(['ok' => false, 'message' => 'Your name cannot be empty.'], 422);
            if ($phone !== '' && (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 13)) api_respond(['ok' => false, 'message' => 'Please enter a contact number with 7 to 13 digits.'], 422);
            if ($zip !== '' && !preg_match('/^\d{4,5}$/', $zip)) api_respond(['ok' => false, 'message' => 'Please enter a valid 4 to 5 digit ZIP code.'], 422);
            $currentEmail = normalize_user_email((string)($_SESSION['user']['email'] ?? ''));
            $newEmail = array_key_exists('email', $payload) ? normalize_user_email(trim((string)$payload['email'])) : $currentEmail;
            $avatarValue = null;
            if (array_key_exists('avatar', $payload)) {
                $avatarValue = normalize_avatar((string)$payload['avatar']);
                if ($avatarValue === false) api_respond(['ok' => false, 'message' => 'Choose a JPG, PNG, WEBP, or GIF image under 1 MB.'], 422);
            }
            $result = update_user_profile($currentEmail, $newEmail, $name, $phone, $address, [
                'address_line' => $addressLine,
                'city' => $city,
                'province' => $province,
                'zip' => $zip,
                'landmark' => $landmark,
            ], $avatarValue);
            if (!empty($result['missing'])) unset($_SESSION['user']);
            if (!$result['ok']) api_respond(['ok' => false, 'message' => $result['message']], !empty($result['missing']) ? 401 : 502);
            $_SESSION['user'] = public_user($result['user']);
            api_respond(['ok' => true, 'message' => 'Profile saved.']);

        case 'logout':
            unset($_SESSION['user']);
            api_respond(['ok' => true, 'message' => 'You have been signed out.']);

        case 'order_history':
            if (empty($_SESSION['user'])) api_respond(['ok' => false, 'message' => 'Sign in to see your order history.'], 401);
            $email = strtolower(normalize_user_email((string)($_SESSION['user']['email'] ?? '')));
            $orders = [];
            foreach (all_orders() as $order) {
                $orderEmail = strtolower((string)($order['email'] ?? $order['customer_email'] ?? ''));
                if ($orderEmail === $email) $orders[] = $order;
            }
            api_respond(['ok' => true, 'orders' => array_slice($orders, 0, 50)]);

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
