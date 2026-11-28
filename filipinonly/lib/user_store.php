<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mysql_store.php';

function find_user_by_email(string $email): ?array {
    return mysql_find_user_by_email($email);
}

function save_user(array $user): bool {
    return mysql_save_user($user);
}

function update_stored_user(string $email, string $name, string $phone, string $address, array $addressParts = []): ?array {
    return mysql_update_stored_user($email, $name, $phone, $address, $addressParts);
}

function update_user_profile(string $currentEmail, string $newEmail, string $name, string $phone, string $address, array $addressParts, ?string $avatar): array {
    return mysql_update_user_profile($currentEmail, $newEmail, $name, $phone, $address, $addressParts, $avatar);
}
