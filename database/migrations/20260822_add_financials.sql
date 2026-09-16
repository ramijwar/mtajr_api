-- Apply after the verification and discovery migration in phpMyAdmin.
ALTER TABLE products ADD COLUMN cost_price DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER delivery_fee;
ALTER TABLE orders ADD COLUMN platform_fee_percent DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER delivery_total;
ALTER TABLE orders ADD COLUMN platform_fee_total DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER platform_fee_percent;
ALTER TABLE order_items ADD COLUMN unit_cost DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER unit_price;

CREATE TABLE platform_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  decimal_value DECIMAL(8,4) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_platform_settings_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;
INSERT INTO platform_settings (setting_key, decimal_value) VALUES ('sale_fee_percent', 0);

CREATE TABLE platform_wallets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  currency ENUM('USD','SYP') NOT NULL,
  available_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_platform_wallet_currency (currency)
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
  CONSTRAINT fk_platform_wallet_tx_wallet FOREIGN KEY (wallet_id) REFERENCES platform_wallets(id),
  CONSTRAINT fk_platform_wallet_tx_order FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT fk_platform_wallet_tx_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;
