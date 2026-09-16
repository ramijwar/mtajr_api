-- SouqLink: أنماط المتاجر والطلبات المسبقة.
-- يُطبّق مرة واحدة وبالترتيب على قاعدة SouqLink فقط بعد المراجعة.

ALTER TABLE stores
  ADD COLUMN store_type ENUM('retail','preorder') NOT NULL DEFAULT 'retail' AFTER phone;

ALTER TABLE products
  ADD COLUMN preorder_unit_label VARCHAR(40) NULL AFTER stock_quantity,
  ADD COLUMN preorder_min_quantity DECIMAL(14,3) NULL AFTER preorder_unit_label,
  ADD COLUMN preorder_lead_days SMALLINT UNSIGNED NULL AFTER preorder_min_quantity;

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
