<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/mysql_store.php';

function migrate_read_json(string $file): array {
    if (!is_file($file)) return [];
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function migrate_branch_for_city(array $branches, string $city): array {
    $wanted = strtolower(trim($city));
    foreach ($branches as $branch) {
        if (strtolower(trim((string)($branch['city'] ?? ''))) === $wanted) return $branch;
    }
    foreach ($branches as $branch) {
        if (strtolower(trim((string)($branch['city'] ?? ''))) === 'quezon city') return $branch;
    }
    return $branches[0] ?? ['city' => 'Quezon City', 'area' => 'Cubao', 'address' => '18 General Roxas Ave, Cubao, Quezon City', 'hours' => '9 AM – 9 PM'];
}

try {
    $server = db_server_connection();
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) throw new RuntimeException('Could not read database/schema.sql.');
    $server->exec($schema);

    $pdo = db_connection();
    $menu = require __DIR__ . '/../data/menu.php';
    $branches = require __DIR__ . '/../data/branches.php';
    $now = date(DATE_ATOM);
    $adminHash = '$2y$10$eoC/xkAFbNnt3nGMZaYwCOftoDXJxV8wD4122ScYUtC7aqnaJHkw6';

    $menuStatement = $pdo->prepare('INSERT INTO menu_items (id, name, description, price, category, tag, prep, serving, image, allergens_json, customs_json, rice_option, spice_option, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), category = VALUES(category), tag = VALUES(tag), prep = VALUES(prep), serving = VALUES(serving), image = VALUES(image), allergens_json = VALUES(allergens_json), customs_json = VALUES(customs_json), rice_option = VALUES(rice_option), spice_option = VALUES(spice_option), sort_order = VALUES(sort_order), is_active = 1');
    foreach ($menu as $index => $item) {
        $menuStatement->execute([
            (string)($item['id'] ?? ''),
            (string)($item['name'] ?? ''),
            (string)($item['description'] ?? ''),
            (float)($item['price'] ?? 0),
            (string)($item['category'] ?? ''),
            (string)($item['tag'] ?? ''),
            (string)($item['prep'] ?? ''),
            (string)($item['serving'] ?? '1 serving'),
            (string)($item['image'] ?? ''),
            db_json($item['allergens'] ?? []),
            db_json($item['customs'] ?? []),
            !empty($item['rice']) ? 1 : 0,
            !empty($item['spice']) ? 1 : 0,
            $index + 1,
        ]);
    }

    $branchStatement = $pdo->prepare('INSERT INTO branches (city, area, address, hours) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE area = VALUES(area), address = VALUES(address), hours = VALUES(hours)');
    foreach ($branches as $branch) {
        $branchStatement->execute([
            (string)($branch['city'] ?? ''),
            (string)($branch['area'] ?? ''),
            (string)($branch['address'] ?? ''),
            (string)($branch['hours'] ?? ''),
        ]);
    }

    $pdo->prepare("INSERT INTO vouchers (code, discount_percent, minimum_subtotal, is_active, expires_at) VALUES ('SARAP10', 10, 299, 1, NULL) ON DUPLICATE KEY UPDATE discount_percent = 10, minimum_subtotal = 299, is_active = 1")->execute();
    $pdo->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)')->execute(['admin', $adminHash, $now]);

    $localUsers = migrate_read_json(LOCAL_USER_FILE);
    foreach ($localUsers as $email => $user) {
        if (!is_array($user)) continue;
        $user['email'] = $user['email'] ?? $email;
        mysql_save_user($user);
    }

    $localOrders = migrate_read_json(LOCAL_ORDER_FILE);
    foreach ($localOrders as $id => $order) {
        if (!is_array($order)) continue;
        $order['id'] = $order['id'] ?? $id;
        if (trim((string)($order['branch_city'] ?? '')) === '') {
            $branch = migrate_branch_for_city($branches, (string)($order['city'] ?? ''));
            $order['branch_city'] = (string)($branch['city'] ?? '');
            $order['branch_area'] = (string)($branch['area'] ?? '');
            $order['branch_address'] = (string)($branch['address'] ?? '');
        }
        mysql_save_order($order);
    }

    $counts = [
        'schema' => 'OK',
        'menu_items' => (int)$pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn(),
        'branches' => (int)$pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn(),
        'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'order_items' => (int)$pdo->query('SELECT COUNT(*) FROM order_items')->fetchColumn(),
        'order_status_history' => (int)$pdo->query('SELECT COUNT(*) FROM order_status_history')->fetchColumn(),
        'vouchers' => (int)$pdo->query('SELECT COUNT(*) FROM vouchers')->fetchColumn(),
        'admin_users' => (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn(),
    ];
    foreach ($counts as $label => $value) echo $label . ': ' . $value . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'MySQL migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
