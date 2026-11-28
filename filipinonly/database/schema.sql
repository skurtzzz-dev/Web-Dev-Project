CREATE DATABASE IF NOT EXISTS filipinonly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE filipinonly;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(32) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  avatar TEXT NULL,
  address_line VARCHAR(180) NOT NULL DEFAULT '',
  city VARCHAR(80) NOT NULL DEFAULT '',
  province VARCHAR(80) NOT NULL DEFAULT '',
  zip VARCHAR(12) NOT NULL DEFAULT '',
  landmark VARCHAR(180) NOT NULL DEFAULT '',
  address TEXT NOT NULL DEFAULT '',
  google_sub VARCHAR(255) NULL UNIQUE,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id VARCHAR(32) PRIMARY KEY,
  receipt_number VARCHAR(32) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(255) NOT NULL DEFAULT '',
  customer_phone VARCHAR(32) NOT NULL,
  address_line VARCHAR(180) NOT NULL DEFAULT '',
  city VARCHAR(80) NOT NULL DEFAULT '',
  province VARCHAR(80) NOT NULL DEFAULT '',
  zip VARCHAR(12) NOT NULL DEFAULT '',
  landmark VARCHAR(180) NOT NULL DEFAULT '',
  delivery_address TEXT NOT NULL,
  branch_city VARCHAR(80) NOT NULL DEFAULT '',
  branch_area VARCHAR(120) NOT NULL DEFAULT '',
  branch_address VARCHAR(180) NOT NULL DEFAULT '',
  delivery_type VARCHAR(24) NOT NULL DEFAULT 'ASAP',
  scheduled_at VARCHAR(32) NOT NULL DEFAULT '',
  eta VARCHAR(64) NOT NULL DEFAULT '30–45 minutes',
  payment_method VARCHAR(48) NOT NULL,
  payment_status VARCHAR(80) NOT NULL DEFAULT 'Pending',
  order_status VARCHAR(32) NOT NULL DEFAULT 'Order received',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivered_at VARCHAR(32) NULL,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL,
  INDEX idx_orders_user_id (user_id),
  INDEX idx_orders_customer_email (customer_email),
  INDEX idx_orders_created_at (created_at),
  INDEX idx_orders_status (order_status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(32) NOT NULL,
  item_id VARCHAR(80) NOT NULL DEFAULT '',
  name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  qty INT NOT NULL DEFAULT 1,
  serving VARCHAR(80) NOT NULL DEFAULT '1 serving',
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  customization_json JSON NULL,
  image TEXT NULL,
  created_at VARCHAR(32) NOT NULL,
  INDEX idx_order_items_order_id (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL,
  INDEX idx_status_history_order_id (order_id),
  INDEX idx_status_history_updated_at (updated_at),
  CONSTRAINT fk_status_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_items (
  id VARCHAR(80) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  category VARCHAR(48) NOT NULL,
  tag VARCHAR(48) NOT NULL DEFAULT '',
  prep VARCHAR(48) NOT NULL DEFAULT '',
  serving VARCHAR(48) NOT NULL DEFAULT '1 serving',
  image TEXT NOT NULL DEFAULT '',
  allergens_json JSON NULL,
  customs_json JSON NULL,
  rice_option TINYINT(1) NOT NULL DEFAULT 0,
  spice_option TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branches (
  city VARCHAR(80) PRIMARY KEY,
  area VARCHAR(120) NOT NULL,
  address VARCHAR(180) NOT NULL,
  hours VARCHAR(48) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vouchers (
  code VARCHAR(32) PRIMARY KEY,
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  minimum_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  expires_at VARCHAR(32) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
