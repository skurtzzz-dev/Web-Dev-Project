<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mysql_store.php';

function branches_catalog(): array {
    static $branches = null;
    if ($branches === null) {
        $loaded = require __DIR__ . '/../data/branches.php';
        $branches = is_array($loaded) ? $loaded : [];
    }
    return $branches;
}

function branch_for_city(string $city): array {
    $branches = branches_catalog();
    $wanted = strtolower(trim($city));
    foreach ($branches as $branch) {
        if (strtolower(trim((string)($branch['city'] ?? ''))) === $wanted) return $branch;
    }
    foreach ($branches as $branch) {
        if (strtolower(trim((string)($branch['city'] ?? ''))) === 'quezon city') return $branch;
    }
    return $branches[0] ?? ['city' => 'Quezon City', 'area' => 'Cubao', 'address' => '18 General Roxas Ave, Cubao, Quezon City', 'hours' => '9 AM – 9 PM'];
}

function branch_for_order(array $order): array {
    $branchCity = trim((string)($order['branch_city'] ?? ''));
    if ($branchCity !== '') return branch_for_city($branchCity);
    return branch_for_city((string)($order['city'] ?? ''));
}

function branch_label(array $branch): string {
    return trim((string)($branch['area'] ?? '') . ' branch · ' . (string)($branch['address'] ?? ''), " ·");
}

function read_local_orders(): array {
    $file = LOCAL_ORDER_FILE;
    if (!is_file($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function write_local_orders(array $orders): bool {
    $file = LOCAL_ORDER_FILE;
    $json = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    return file_put_contents($file, $json, LOCK_EX) !== false;
}

function n8n_post(string $path, array $payload, int $timeout = N8N_HTTP_TIMEOUT): array {
    $url = rtrim(N8N_WEBHOOK_BASE, '/') . '/' . ltrim($path, '/');
    $headers = ["Content-Type: application/json", "Accept: application/json"];
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) return ['ok' => false, 'message' => 'Could not encode the request for n8n.'];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $responseBody = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($responseBody === false) return ['ok' => false, 'message' => 'Could not reach n8n: ' . $error];
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ]]);
        $responseBody = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int)($matches[1] ?? 0);
        if ($responseBody === false) return ['ok' => false, 'message' => 'Could not reach n8n. Check that n8n is running and the webhook is active.'];
    }

    $data = json_decode((string)$responseBody, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($data) ? ($data['message'] ?? $data['error'] ?? '') : '';
        return ['ok' => false, 'message' => $message ?: "n8n returned HTTP {$status}. Check the workflow execution log."];
    }
    if (!is_array($data)) return ['ok' => false, 'message' => 'n8n returned an unexpected response. Check that the Respond to Webhook node returns JSON.'];
    if (($data['ok'] ?? false) !== true) return ['ok' => false, 'message' => $data['message'] ?? 'The n8n workflow reported an error.'];
    return $data;
}

function n8n_notify(string $path, array $payload): bool {
    if (N8N_WEBHOOK_BASE === '') return false;
    $result = n8n_post($path, $payload, 3);
    return ($result['ok'] ?? false) === true;
}

function order_to_n8n_row(array $order): array {
    return [
        'order_number' => (string)($order['id'] ?? ''),
        'created_at' => (string)($order['created_at'] ?? date(DATE_ATOM)),
        'updated_at' => (string)($order['updated_at'] ?? $order['created_at'] ?? date(DATE_ATOM)),
        'customer_name' => (string)($order['name'] ?? ''),
        'customer_email' => (string)($order['email'] ?? ''),
        'customer_phone' => (string)($order['phone'] ?? ''),
        'delivery_address' => (string)($order['address'] ?? ''),
        'landmark' => (string)($order['landmark'] ?? ''),
        'branch_city' => (string)($order['branch_city'] ?? ''),
        'branch_area' => (string)($order['branch_area'] ?? ''),
        'branch_address' => (string)($order['branch_address'] ?? ''),
        'delivery_type' => (string)($order['delivery'] ?? 'ASAP'),
        'scheduled_at' => (string)($order['schedule'] ?? ''),
        'payment_method' => (string)($order['payment'] ?? 'Cash on Delivery'),
        'payment_status' => (string)($order['payment_status'] ?? 'Pending - demonstration only'),
        'order_status' => (string)($order['status'] ?? 'Order received'),
        'items_json' => json_encode($order['items'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'subtotal' => (float)($order['subtotal'] ?? 0),
        'discount' => (float)($order['discount'] ?? 0),
        'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
        'total' => (float)($order['total'] ?? 0),
        'receipt_number' => (string)($order['receipt_number'] ?? ''),
        'status_history_json' => json_encode($order['status_history'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
}

function n8n_row_to_order(array $row): array {
    $items = json_decode((string)($row['items_json'] ?? '[]'), true);
    $history = json_decode((string)($row['status_history_json'] ?? '[]'), true);
    return [
        'id' => (string)($row['order_number'] ?? ''),
        'receipt_number' => (string)($row['receipt_number'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'name' => (string)($row['customer_name'] ?? ''),
        'email' => (string)($row['customer_email'] ?? ''),
        'phone' => (string)($row['customer_phone'] ?? ''),
        'address' => (string)($row['delivery_address'] ?? ''),
        'landmark' => (string)($row['landmark'] ?? ''),
        'branch_city' => (string)($row['branch_city'] ?? ''),
        'branch_area' => (string)($row['branch_area'] ?? ''),
        'branch_address' => (string)($row['branch_address'] ?? ''),
        'delivery' => (string)($row['delivery_type'] ?? 'ASAP'),
        'schedule' => (string)($row['scheduled_at'] ?? ''),
        'eta' => ($row['delivery_type'] ?? 'ASAP') === 'Scheduled' ? (string)($row['scheduled_at'] ?? '') : '30–45 minutes',
        'payment' => (string)($row['payment_method'] ?? ''),
        'payment_status' => (string)($row['payment_status'] ?? 'Pending - demonstration only'),
        'status' => (string)($row['order_status'] ?? 'Order received'),
        'items' => is_array($items) ? $items : [],
        'subtotal' => (float)($row['subtotal'] ?? 0),
        'discount' => (float)($row['discount'] ?? 0),
        'delivery_fee' => (float)($row['delivery_fee'] ?? 0),
        'total' => (float)($row['total'] ?? 0),
        'status_history' => is_array($history) ? $history : [],
    ];
}

function save_order(array $order): array {
    if (ORDER_STORAGE_MODE === 'mysql') {
        $saved = mysql_save_order($order);
        if (!$saved['ok']) return $saved;
        n8n_notify('filipinonly-order-create', order_to_n8n_row($saved['order'] ?? $order));
        return $saved;
    }
    if (ORDER_STORAGE_MODE === 'n8n') {
        $result = n8n_post('filipinonly-order-create', order_to_n8n_row($order));
        if (!$result['ok']) return $result;
        return ['ok' => true, 'order' => $order, 'message' => 'Order saved in n8n Data Tables.'];
    }
    $orders = read_local_orders();
    $orders[$order['id']] = $order;
    if (!write_local_orders($orders)) return ['ok' => false, 'message' => 'Could not save the order file. Check folder permissions.'];
    return ['ok' => true, 'order' => $order, 'message' => 'Order saved in local JSON storage.'];
}

function find_order(string $orderNumber): ?array {
    $orderNumber = strtoupper(trim($orderNumber));
    if ($orderNumber === '') return null;
    if (ORDER_STORAGE_MODE === 'mysql') return mysql_find_order($orderNumber);
    if (ORDER_STORAGE_MODE === 'n8n') {
        $result = n8n_post('filipinonly-order-get', ['order_number' => $orderNumber]);
        if (!$result['ok']) throw new RuntimeException($result['message']);
        if (empty($result['order']) || !is_array($result['order'])) return null;
        return n8n_row_to_order($result['order']);
    }
    $orders = read_local_orders();
    return isset($orders[$orderNumber]) && is_array($orders[$orderNumber]) ? $orders[$orderNumber] : null;
}

function all_orders(): array {
    if (ORDER_STORAGE_MODE === 'mysql') {
        $orders = mysql_all_orders();
    } elseif (ORDER_STORAGE_MODE === 'n8n') {
        $result = n8n_post('filipinonly-order-list', []);
        if (!$result['ok']) throw new RuntimeException($result['message']);
        $rows = $result['orders'] ?? [];
        if (!is_array($rows)) return [];
        $orders = array_map('n8n_row_to_order', array_filter($rows, 'is_array'));
    } else {
        $orders = array_values(read_local_orders());
    }
    usort($orders, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
    return $orders;
}

function update_order_status(string $orderNumber, string $status): array {
    $allowed = ['Order received', 'Preparing', 'On the way', 'Delivered', 'Cancelled'];
    if (!in_array($status, $allowed, true)) return ['ok' => false, 'message' => 'Invalid order status.'];
    if (ORDER_STORAGE_MODE === 'mysql') {
        $result = mysql_update_order_status($orderNumber, $status);
        if ($result['ok']) n8n_notify('filipinonly-order-update', order_to_n8n_row($result['order']));
        return $result;
    }
    $order = find_order($orderNumber);
    if (!$order) return ['ok' => false, 'message' => 'Order not found.'];
    $now = date(DATE_ATOM);
    $order['status'] = $status;
    $order['updated_at'] = $now;
    if ($status === 'Delivered' && empty($order['delivered_at'])) $order['delivered_at'] = $now;
    $order['status_history'] = $order['status_history'] ?? [];
    $order['status_history'][] = ['status' => $status, 'updated_at' => $now];

    if (ORDER_STORAGE_MODE === 'n8n') {
        $payload = [
            'order_number' => $orderNumber,
            'order_status' => $status,
            'updated_at' => $now,
            'status_history_json' => json_encode($order['status_history'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
        $result = n8n_post('filipinonly-order-update', $payload);
        if (!$result['ok']) return $result;
    } else {
        $orders = read_local_orders();
        $orders[$orderNumber] = $order;
        if (!write_local_orders($orders)) return ['ok' => false, 'message' => 'Could not update the local order file.'];
    }
    return ['ok' => true, 'order' => $order, 'message' => 'Order status updated.'];
}
