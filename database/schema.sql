-- SouqLink schema. Run this file after selecting the target database in phpMyAdmin.
-- Use utf8mb4 and InnoDB for Arabic data and transactional integrity.

ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(32) NOT NULL UNIQUE,
  email VARCHAR(191) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','merchant','courier','admin') NOT NULL DEFAULT 'customer',
  status ENUM('active','restricted','pending','deleted') NOT NULL DEFAULT 'pending',
  profile_update_permission ENUM('allowed','locked') NOT NULL DEFAULT 'allowed',
  avatar_path VARCHAR(255) NULL,
  push_token VARCHAR(255) NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role_status (role, status),
  INDEX idx_users_profile_update_permission (profile_update_permission)
) ENGINE=InnoDB;

CREATE TABLE merchant_profiles (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  sham_cash_address VARCHAR(191) NOT NULL,
  payout_details TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE stores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  merchant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  logo_path VARCHAR(255) NULL,
  phone VARCHAR(32) NULL,
  store_type ENUM('retail','preorder') NOT NULL DEFAULT 'retail',
  status ENUM('draft','active','suspended') NOT NULL DEFAULT 'draft',
  is_verified BOOLEAN NOT NULL DEFAULT FALSE,
  rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_stores_merchant (merchant_id),
  INDEX idx_stores_status (status),
  FOREIGN KEY (merchant_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE courier_profiles (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  vehicle_type VARCHAR(80) NULL,
  identity_document_path VARCHAR(255) NULL,
  is_available BOOLEAN NOT NULL DEFAULT TRUE,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE verification_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  subject_role ENUM('merchant','courier') NOT NULL,
  identity_front_path VARCHAR(255) NOT NULL,
  portrait_path VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  review_note VARCHAR(500) NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_verification_user_role (user_id, subject_role),
  INDEX idx_verification_status_created (status, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE advertisements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  body VARCHAR(500) NULL,
  image_path VARCHAR(255) NOT NULL,
  size_preset ENUM('wide','medium','compact') NOT NULL DEFAULT 'wide',
  target_type ENUM('none','store','product','url') NOT NULL DEFAULT 'none',
  target_value VARCHAR(500) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ads_visible (status, starts_at, ends_at, display_order),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE courier_store_subscriptions (
  courier_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','active','blocked') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (courier_id, store_id),
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(80) NULL,
  status ENUM('active','hidden') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE platform_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  decimal_value DECIMAL(8,4) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO platform_settings (setting_key, decimal_value) VALUES ('sale_fee_percent', 0);

CREATE TABLE platform_wallets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  currency ENUM('USD','SYP') NOT NULL,
  available_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_platform_wallet_currency (currency)
) ENGINE=InnoDB;

CREATE TABLE products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  price DECIMAL(18,2) NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  preorder_unit_label VARCHAR(40) NULL,
  preorder_min_quantity DECIMAL(14,3) NULL,
  preorder_lead_days SMALLINT UNSIGNED NULL,
  delivery_fee DECIMAL(18,2) NOT NULL DEFAULT 0,
  cost_price DECIMAL(18,2) NOT NULL DEFAULT 0,
  status ENUM('draft','active','hidden','out_of_stock') NOT NULL DEFAULT 'draft',
  rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,
  sales_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_store_status (store_id, status),
  INDEX idx_products_category_status (category_id, status),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(80) NOT NULL,
  address_text VARCHAR(500) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_addresses_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE carts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cart_customer_store (customer_id, store_id),
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cart_product (cart_id, product_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(32) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  fulfillment_type ENUM('pickup','delivery') NOT NULL,
  status ENUM('pending_payment','payment_review','paid','preparing','ready_for_delivery','out_for_delivery','delivered','cancelled','rejected') NOT NULL DEFAULT 'pending_payment',
  products_subtotal DECIMAL(18,2) NOT NULL,
  delivery_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  platform_fee_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  platform_fee_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(18,2) NOT NULL,
  delivery_address_text VARCHAR(500) NULL,
  delivery_latitude DECIMAL(10,7) NULL,
  delivery_longitude DECIMAL(10,7) NULL,
  sham_cash_address_snapshot VARCHAR(191) NOT NULL,
  notes VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_customer_status (customer_id, status),
  INDEX idx_orders_store_status (store_id, status),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  product_name_snapshot VARCHAR(180) NOT NULL,
  unit_price DECIMAL(18,2) NOT NULL,
  unit_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
  unit_delivery_fee DECIMAL(18,2) NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL,
  line_total DECIMAL(18,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE platform_wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_id BIGINT UNSIGNED NOT NULL,
  transaction_type ENUM('sale_fee','manual_adjustment','refund') NOT NULL,
  direction ENUM('credit','debit') NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  balance_after DECIMAL(18,2) NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_platform_wallet_tx_created (wallet_id, created_at),
  FOREIGN KEY (wallet_id) REFERENCES platform_wallets(id),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE payment_receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('sham_cash') NOT NULL DEFAULT 'sham_cash',
  transaction_number VARCHAR(120) NOT NULL,
  paid_amount DECIMAL(18,2) NOT NULL,
  status ENUM('submitted','verified','rejected') NOT NULL DEFAULT 'submitted',
  merchant_review_status ENUM('pending','acknowledged','issue_reported') NOT NULL DEFAULT 'pending',
  merchant_review_note VARCHAR(500) NULL,
  merchant_reviewed_at DATETIME NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  rejection_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_payment_transaction (provider, transaction_number),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE preorder_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preorder_number VARCHAR(32) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  product_name_snapshot VARCHAR(180) NOT NULL,
  unit_label_snapshot VARCHAR(40) NOT NULL,
  requested_quantity DECIMAL(14,3) NOT NULL,
  unit_price_snapshot DECIMAL(18,2) NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  products_subtotal DECIMAL(18,2) NOT NULL,
  delivery_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  platform_fee_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  platform_fee_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(18,2) NOT NULL,
  fulfillment_type ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  delivery_address_text VARCHAR(500) NULL,
  delivery_latitude DECIMAL(10,7) NULL,
  delivery_longitude DECIMAL(10,7) NULL,
  requested_fulfillment_at DATETIME NOT NULL,
  sham_cash_address_snapshot VARCHAR(191) NOT NULL,
  customer_note VARCHAR(1000) NULL,
  status ENUM('pending_payment','payment_review','paid','merchant_confirmed','in_production','ready','fulfilled','cancelled','rejected') NOT NULL DEFAULT 'pending_payment',
  merchant_payment_review_status ENUM('pending','acknowledged','issue_reported') NOT NULL DEFAULT 'pending',
  merchant_payment_review_note VARCHAR(500) NULL,
  merchant_payment_reviewed_at DATETIME NULL,
  merchant_reminder_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,
  reminder_sent_at DATETIME NULL,
  merchant_confirmed_at DATETIME NULL,
  fulfilled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_preorders_customer_status (customer_id, status),
  INDEX idx_preorders_store_status_due (store_id, status, requested_fulfillment_at),
  INDEX idx_preorders_due_reminder (status, requested_fulfillment_at, reminder_sent_at),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE preorder_payment_receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preorder_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('sham_cash') NOT NULL DEFAULT 'sham_cash',
  transaction_number VARCHAR(120) NOT NULL,
  paid_amount DECIMAL(18,2) NOT NULL,
  status ENUM('submitted','verified','rejected') NOT NULL DEFAULT 'submitted',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  rejection_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_preorder_payment_transaction (provider, transaction_number),
  INDEX idx_preorder_payment_status (status, created_at),
  FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE preorder_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preorder_id BIGINT UNSIGNED NOT NULL,
  buyer_id BIGINT UNSIGNED NOT NULL,
  merchant_id BIGINT UNSIGNED NOT NULL,
  last_message_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_preorder_conversation (preorder_id),
  INDEX idx_preorder_conversations_buyer (buyer_id, last_message_at),
  INDEX idx_preorder_conversations_merchant (merchant_id, last_message_at),
  FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (merchant_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE preorder_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_preorder_messages_conversation (conversation_id, created_at),
  FOREIGN KEY (conversation_id) REFERENCES preorder_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE platform_wallet_transactions
  ADD COLUMN preorder_id BIGINT UNSIGNED NULL AFTER order_id,
  ADD INDEX idx_platform_wallet_tx_preorder (preorder_id),
  ADD CONSTRAINT fk_platform_wallet_tx_preorder FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id);

CREATE TABLE delivery_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL UNIQUE,
  courier_id BIGINT UNSIGNED NULL,
  delivery_fee DECIMAL(18,2) NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  status ENUM('available','accepted','rejected','picked_up','in_transit','delivered','proof_submitted','approved','disputed') NOT NULL DEFAULT 'available',
  eta_value SMALLINT UNSIGNED NULL,
  eta_unit ENUM('hours','days') NULL,
  accepted_at DATETIME NULL,
  proof_image_path VARCHAR(255) NULL,
  pickup_proof_image_path VARCHAR(255) NULL,
  delivery_proof_image_path VARCHAR(255) NULL,
  proof_submitted_at DATETIME NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tasks_courier_status (courier_id, status),
  INDEX idx_tasks_status (status),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE delivery_task_rejections (
  task_id BIGINT UNSIGNED NOT NULL,
  courier_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (task_id, courier_id),
  INDEX idx_delivery_task_rejections_courier (courier_id),
  FOREIGN KEY (task_id) REFERENCES delivery_tasks(id),
  FOREIGN KEY (courier_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE wallets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  available_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  pending_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_wallet_user_currency (user_id, currency),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_id BIGINT UNSIGNED NOT NULL,
  transaction_type ENUM('merchant_sale','courier_commission','withdrawal_hold','withdrawal_release','withdrawal_paid','manual_adjustment','refund') NOT NULL,
  direction ENUM('credit','debit') NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  balance_after DECIMAL(18,2) NOT NULL,
  reference_type VARCHAR(60) NOT NULL,
  reference_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wallet_tx_wallet_created (wallet_id, created_at),
  INDEX idx_wallet_tx_reference (reference_type, reference_id),
  FOREIGN KEY (wallet_id) REFERENCES wallets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE withdrawal_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  payout_method VARCHAR(80) NOT NULL,
  payout_details TEXT NOT NULL,
  status ENUM('requested','under_review','approved','rejected','paid') NOT NULL DEFAULT 'requested',
  reviewed_by BIGINT UNSIGNED NULL,
  review_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  INDEX idx_withdrawals_status_created (status, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (wallet_id) REFERENCES wallets(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1500) NULL,
  status ENUM('published','hidden') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_review_order_product (order_id, product_id),
  CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE store_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1500) NULL,
  status ENUM('published','hidden') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_store_review_order (order_id, store_id),
  INDEX idx_store_reviews_visible (store_id, status, created_at),
  CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE support_tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  category ENUM('account','order','payment','delivery','wallet','other') NOT NULL,
  priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  status ENUM('open','in_progress','waiting_user','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_admin_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tickets_status_updated (status, updated_at),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (assigned_admin_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE support_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  attachment_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_actor_created (actor_id, created_at),
  FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB;


-- Persistent customer delivery address, synchronized with the app's local cache.
CREATE TABLE customer_delivery_addresses (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  label VARCHAR(120) NOT NULL DEFAULT '',
  address_text VARCHAR(500) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Per-user notification preferences; defaults are enabled and can be changed independently per account.
CREATE TABLE notification_preferences (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  orders_enabled TINYINT(1) NOT NULL DEFAULT 1,
  payments_enabled TINYINT(1) NOT NULL DEFAULT 1,
  delivery_enabled TINYINT(1) NOT NULL DEFAULT 1,
  verification_enabled TINYINT(1) NOT NULL DEFAULT 1,
  wallet_enabled TINYINT(1) NOT NULL DEFAULT 1,
  marketplace_enabled TINYINT(1) NOT NULL DEFAULT 1,
  support_enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE courier_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courier_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1500) NULL,
  status ENUM('published','hidden') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_courier_review_order (order_id),
  INDEX idx_courier_reviews_courier (courier_id, status, created_at),
  CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE admin_notification_reads (
  admin_id BIGINT UNSIGNED NOT NULL,
  section_key VARCHAR(80) NOT NULL,
  read_at DATETIME NOT NULL,
  PRIMARY KEY (admin_id, section_key),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
