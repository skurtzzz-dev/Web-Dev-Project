<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function mysql_normalize_email(string $email): string {
    return strtolower(trim($email));
}

function mysql_user_from_row(array $row): array {
    return [
        'id' => (int)($row['id'] ?? 0),
        'name' => (string)($row['name'] ?? ''),
        'email' => mysql_normalize_email((string)($row['email'] ?? '')),
        'phone' => (string)($row['phone'] ?? ''),
        'password_hash' => (string)($row['password_hash'] ?? ''),
        'avatar' => (string)($row['avatar'] ?? ''),
        'address' => (string)($row['address'] ?? ''),
        'address_line' => (string)($row['address_line'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'province' => (string)($row['province'] ?? ''),
        'zip' => (string)($row['zip'] ?? ''),
        'landmark' => (string)($row['landmark'] ?? ''),
        'google_sub' => (string)($row['google_sub'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
    ];
}

function mysql_find_user_by_email(string $email): ?array {
    $email = mysql_normalize_email($email);
    if ($email === '') return null;
    $row = db_row('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    return is_array($row) ? mysql_user_from_row($row) : null;
}

function mysql_find_user_by_google_sub(string $sub): ?array {
    $sub = trim($sub);
    if ($sub === '') return null;
    $row = db_row('SELECT * FROM users WHERE google_sub = ? LIMIT 1', [$sub]);
    return is_array($row) ? mysql_user_from_row($row) : null;
}

function mysql_save_user(array $user): bool {
    $email = mysql_normalize_email((string)($user['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $now = (string)($user['updated_at'] ?? date(DATE_ATOM));
    $created = (string)($user['created_at'] ?? $now);
    $hash = (string)($user['password_hash'] ?? '');
    if ($hash === '') $hash = (string)password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
    $googleSub = trim((string)($user['google_sub'] ?? ''));
    $values = [
        'name' => (string)($user['name'] ?? ''),
        'email' => $email,
        'phone' => (string)($user['phone'] ?? ''),
        'password_hash' => $hash,
        'avatar' => (string)($user['avatar'] ?? '') !== '' ? (string)$user['avatar'] : null,
        'address_line' => (string)($user['address_line'] ?? ''),
        'city' => (string)($user['city'] ?? ''),
        'province' => (string)($user['province'] ?? ''),
        'zip' => (string)($user['zip'] ?? ''),
        'landmark' => (string)($user['landmark'] ?? ''),
        'address' => (string)($user['address'] ?? ''),
        'google_sub' => $googleSub !== '' ? $googleSub : null,
        'created_at' => $created,
        'updated_at' => $now,
    ];
    $columns = implode(', ', array_keys($values));
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $updates = [];
    foreach (array_keys($values) as $column) {
        if ($column !== 'created_at') $updates[] = $column . ' = VALUES(' . $column . ')';
    }
    $sql = 'INSERT INTO users (' . $columns . ') VALUES (' . $placeholders . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    return db_execute($sql, array_values($values));
}

function mysql_update_stored_user(string $email, string $name, string $phone, string $address, array $addressParts = []): ?array {
    $user = mysql_find_user_by_email($email);
    if (!$user) return null;
    $now = date(DATE_ATOM);
    $ok = db_execute(
        'UPDATE users SET name = ?, phone = ?, address = ?, address_line = ?, city = ?, province = ?, zip = ?, landmark = ?, updated_at = ? WHERE id = ?',
        [
            $name,
            $phone,
            $address,
            (string)($addressParts['address_line'] ?? $user['address_line'] ?? ''),
            (string)($addressParts['city'] ?? $user['city'] ?? ''),
            (string)($addressParts['province'] ?? $user['province'] ?? ''),
            (string)($addressParts['zip'] ?? $user['zip'] ?? ''),
            (string)($addressParts['landmark'] ?? $user['landmark'] ?? ''),
            $now,
            $user['id'],
        ]
    );
    return $ok ? mysql_find_user_by_email((string)$user['email']) : null;
}

function mysql_update_user_profile(string $currentEmail, string $newEmail, string $name, string $phone, string $address, array $addressParts, ?string $avatar): array {
    $currentEmail = mysql_normalize_email($currentEmail);
    $newEmail = mysql_normalize_email($newEmail);
    $user = mysql_find_user_by_email($currentEmail);
    if (!$user) return ['ok' => false, 'message' => 'Your account no longer exists. Please sign in again.', 'user' => null, 'missing' => true];
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'message' => 'Please enter a valid email address.', 'user' => null];
    if ($newEmail !== $currentEmail && mysql_find_user_by_email($newEmail)) return ['ok' => false, 'message' => 'That email is already used by another account.', 'user' => null];
    $avatarValue = $avatar === null ? $user['avatar'] : $avatar;
    $ok = db_execute(
        'UPDATE users SET email = ?, name = ?, phone = ?, address = ?, address_line = ?, city = ?, province = ?, zip = ?, landmark = ?, avatar = ?, updated_at = ? WHERE id = ?',
        [
            $newEmail,
            $name,
            $phone,
            $address,
            (string)($addressParts['address_line'] ?? ''),
            (string)($addressParts['city'] ?? ''),
            (string)($addressParts['province'] ?? ''),
            (string)($addressParts['zip'] ?? ''),
            (string)($addressParts['landmark'] ?? ''),
            $avatarValue !== '' ? $avatarValue : null,
            date(DATE_ATOM),
            $user['id'],
        ]
    );
    if (!$ok) return ['ok' => false, 'message' => 'We could not save your profile. Please try again.', 'user' => null];
    return ['ok' => true, 'message' => 'Profile saved.', 'user' => mysql_find_user_by_email($newEmail)];
}

function mysql_order_from_row(array $row): array {
    $id = (string)($row['id'] ?? '');
    $itemRows = db_rows('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', [$id]);
    $items = [];
    foreach ($itemRows as $itemRow) {
        $custom = json_decode((string)($itemRow['customization_json'] ?? '[]'), true);
        $items[] = [
            'id' => (string)($itemRow['item_id'] ?? ''),
            'name' => (string)($itemRow['name'] ?? ''),
            'price' => (float)($itemRow['price'] ?? 0),
            'qty' => (int)($itemRow['qty'] ?? 1),
            'serving' => (string)($itemRow['serving'] ?? '1 serving'),
            'image' => (string)($itemRow['image'] ?? ''),
            'line_total' => (float)($itemRow['line_total'] ?? 0),
            'custom' => is_array($custom) ? $custom : ['removed' => [], 'rice' => 'Garlic rice', 'spice' => 'Regular'],
        ];
    }
    $historyRows = db_rows('SELECT status, updated_at FROM order_status_history WHERE order_id = ? ORDER BY id ASC', [$id]);
    $history = array_map(static fn(array $entry): array => [
        'status' => (string)($entry['status'] ?? ''),
        'updated_at' => (string)($entry['updated_at'] ?? ''),
    ], $historyRows);
    if (!$history) {
        $history[] = [
            'status' => (string)($row['order_status'] ?? 'Order received'),
            'updated_at' => (string)($row['created_at'] ?? ''),
        ];
    }
    return [
        'id' => $id,
        'receipt_number' => (string)($row['receipt_number'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'delivered_at' => (string)($row['delivered_at'] ?? ''),
        'name' => (string)($row['customer_name'] ?? ''),
        'email' => strtolower((string)($row['customer_email'] ?? '')),
        'phone' => (string)($row['customer_phone'] ?? ''),
        'address' => (string)($row['delivery_address'] ?? ''),
        'address_line' => (string)($row['address_line'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'province' => (string)($row['province'] ?? ''),
        'zip' => (string)($row['zip'] ?? ''),
        'landmark' => (string)($row['landmark'] ?? ''),
        'branch_city' => (string)($row['branch_city'] ?? ''),
        'branch_area' => (string)($row['branch_area'] ?? ''),
        'branch_address' => (string)($row['branch_address'] ?? ''),
        'delivery' => (string)($row['delivery_type'] ?? 'ASAP'),
        'schedule' => (string)($row['scheduled_at'] ?? ''),
        'eta' => (string)($row['eta'] ?? '30–45 minutes'),
        'payment' => (string)($row['payment_method'] ?? ''),
        'payment_status' => (string)($row['payment_status'] ?? ''),
        'status' => (string)($row['order_status'] ?? 'Order received'),
        'items' => $items,
        'subtotal' => (float)($row['subtotal'] ?? 0),
        'discount' => (float)($row['discount'] ?? 0),
        'delivery_fee' => (float)($row['delivery_fee'] ?? 0),
        'total' => (float)($row['total'] ?? 0),
        'status_history' => $history,
    ];
}

function mysql_find_order(string $orderNumber): ?array {
    $orderNumber = strtoupper(trim($orderNumber));
    if ($orderNumber === '') return null;
    $row = db_row('SELECT * FROM orders WHERE id = ? LIMIT 1', [$orderNumber]);
    return is_array($row) ? mysql_order_from_row($row) : null;
}

function mysql_all_orders(): array {
    $rows = db_rows('SELECT * FROM orders ORDER BY created_at DESC, id DESC');
    return array_map('mysql_order_from_row', $rows);
}

function mysql_user_id_for_email(string $email): ?int {
    $email = mysql_normalize_email($email);
    if ($email === '') return null;
    $id = db_scalar('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
    return $id === false || $id === null ? null : (int)$id;
}

function mysql_save_order(array $order): array {
    $id = strtoupper(trim((string)($order['id'] ?? '')));
    if ($id === '') return ['ok' => false, 'message' => 'The order number is missing.'];
    $created = (string)($order['created_at'] ?? date(DATE_ATOM));
    $updated = (string)($order['updated_at'] ?? $created);
    $delivered = trim((string)($order['delivered_at'] ?? ''));
    $values = [
        'id' => $id,
        'receipt_number' => (string)($order['receipt_number'] ?? ''),
        'user_id' => mysql_user_id_for_email((string)($order['email'] ?? '')),
        'customer_name' => (string)($order['name'] ?? ''),
        'customer_email' => mysql_normalize_email((string)($order['email'] ?? '')),
        'customer_phone' => (string)($order['phone'] ?? ''),
        'address_line' => (string)($order['address_line'] ?? ''),
        'city' => (string)($order['city'] ?? ''),
        'province' => (string)($order['province'] ?? ''),
        'zip' => (string)($order['zip'] ?? ''),
        'landmark' => (string)($order['landmark'] ?? ''),
        'delivery_address' => (string)($order['address'] ?? ''),
        'branch_city' => (string)($order['branch_city'] ?? ''),
        'branch_area' => (string)($order['branch_area'] ?? ''),
        'branch_address' => (string)($order['branch_address'] ?? ''),
        'delivery_type' => (string)($order['delivery'] ?? 'ASAP'),
        'scheduled_at' => (string)($order['schedule'] ?? ''),
        'eta' => (string)($order['eta'] ?? '30–45 minutes'),
        'payment_method' => (string)($order['payment'] ?? ''),
        'payment_status' => (string)($order['payment_status'] ?? ''),
        'order_status' => (string)($order['status'] ?? 'Order received'),
        'subtotal' => (float)($order['subtotal'] ?? 0),
        'discount' => (float)($order['discount'] ?? 0),
        'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
        'total' => (float)($order['total'] ?? 0),
        'delivered_at' => $delivered !== '' ? $delivered : null,
        'created_at' => $created,
        'updated_at' => $updated,
    ];
    $pdo = db_connection();
    $pdo->beginTransaction();
    try {
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $updates = [];
        foreach (array_keys($values) as $column) {
            if ($column !== 'id' && $column !== 'created_at') $updates[] = $column . ' = VALUES(' . $column . ')';
        }
        $pdo->prepare('INSERT INTO orders (' . $columns . ') VALUES (' . $placeholders . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates))->execute(array_values($values));
        $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM order_status_history WHERE order_id = ?')->execute([$id]);
        $itemStatement = $pdo->prepare('INSERT INTO order_items (order_id, item_id, name, price, qty, serving, line_total, customization_json, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach (($order['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $price = (float)($item['price'] ?? 0);
            $qty = max(1, (int)($item['qty'] ?? 1));
            $itemStatement->execute([
                $id,
                (string)($item['id'] ?? ''),
                (string)($item['name'] ?? ''),
                $price,
                $qty,
                (string)($item['serving'] ?? '1 serving'),
                (float)($item['line_total'] ?? ($price * $qty)),
                db_json($item['custom'] ?? []),
                (string)($item['image'] ?? '') !== '' ? (string)$item['image'] : null,
                $created,
            ]);
        }
        $historyStatement = $pdo->prepare('INSERT INTO order_status_history (order_id, status, updated_at) VALUES (?, ?, ?)');
        $history = $order['status_history'] ?? [];
        if (!is_array($history) || !$history) {
            $history = [['status' => $order['status'] ?? 'Order received', 'updated_at' => $created]];
        }
        foreach ($history as $entry) {
            if (!is_array($entry)) continue;
            $historyStatement->execute([
                $id,
                (string)($entry['status'] ?? 'Order received'),
                (string)($entry['updated_at'] ?? $created),
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'message' => 'Could not save the order in MySQL: ' . $e->getMessage()];
    }
    return ['ok' => true, 'order' => mysql_find_order($id), 'message' => 'Order saved in MySQL.'];
}

function mysql_update_order_status(string $orderNumber, string $status): array {
    $allowed = ['Order received', 'Preparing', 'On the way', 'Delivered', 'Cancelled'];
    if (!in_array($status, $allowed, true)) return ['ok' => false, 'message' => 'Invalid order status.'];
    $orderNumber = strtoupper(trim($orderNumber));
    $row = db_row('SELECT * FROM orders WHERE id = ? LIMIT 1', [$orderNumber]);
    if (!$row) return ['ok' => false, 'message' => 'Order not found.'];
    $now = date(DATE_ATOM);
    $deliveredAt = $status === 'Delivered' ? (string)($row['delivered_at'] ?? '') ?: $now : ($row['delivered_at'] ?? null);
    $pdo = db_connection();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE orders SET order_status = ?, delivered_at = ?, updated_at = ? WHERE id = ?')->execute([$status, $deliveredAt, $now, $orderNumber]);
        $pdo->prepare('INSERT INTO order_status_history (order_id, status, updated_at) VALUES (?, ?, ?)')->execute([$orderNumber, $status, $now]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'message' => 'Could not update the order in MySQL: ' . $e->getMessage()];
    }
    return ['ok' => true, 'order' => mysql_find_order($orderNumber), 'message' => 'Order status updated.'];
}
