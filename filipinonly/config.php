<?php
/**
 * FilipinOnly local configuration.
 * MySQL is the primary store for users and orders; n8n handles automation.
 */
define('ORDER_STORAGE_MODE', 'mysql');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'filipinonly');
define('DB_USER', 'root');
define('DB_PASS', 'filipinonly-db-2026');
define('DB_CHARSET', 'utf8mb4');

// n8n automation webhook base. Activate imported workflows before using these URLs.
define('N8N_WEBHOOK_BASE', 'http://127.0.0.1:5678/webhook');
define('N8N_HTTP_TIMEOUT', 15);

// Change this before demonstrating the admin dashboard.
define('ADMIN_PASSWORD', 'filipinonly-admin-2026');
define('GOOGLE_CLIENT_ID', '');

// JSON storage location for local mode. This is outside the public project folder.
define('LOCAL_ORDER_FILE', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filipinonly_orders.json');
define('LOCAL_USER_FILE', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filipinonly_users.json');

// Used by receipt links and email copy in this demo.
define('APP_NAME', 'FilipinOnly');
