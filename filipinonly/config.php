<?php
/**
 * FilipinOnly local configuration.
 * No MySQL is required. Use JSON files for local testing or n8n Data Tables for orders.
 */

// 'local' = persistent JSON file storage (works immediately).
// 'n8n'   = persist orders in the n8n Data Table workflows included in /n8n/workflows.
define('ORDER_STORAGE_MODE', 'local');

// n8n production webhook base. Activate imported workflows before using these URLs.
define('N8N_WEBHOOK_BASE', 'http://127.0.0.1:5678/webhook');
define('N8N_HTTP_TIMEOUT', 15);

// Change this before demonstrating the admin dashboard.
define('ADMIN_PASSWORD', 'filipinonly-admin-2026');

// JSON storage location for local mode. This is outside the public project folder.
define('LOCAL_ORDER_FILE', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filipinonly_orders.json');

// Used by receipt links and email copy in this demo.
define('APP_NAME', 'FilipinOnly');
