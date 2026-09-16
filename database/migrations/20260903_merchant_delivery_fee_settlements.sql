-- مستحقات أجور التوصيل على التاجر وتسويات الإدارة.
-- شغّل هذا الملف مرة واحدة بعد أخذ نسخة احتياطية من قاعدة البيانات.
CREATE TABLE IF NOT EXISTS merchant_delivery_fee_settlements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  merchant_id BIGINT UNSIGNED NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  settled_by BIGINT UNSIGNED NOT NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_merchant_delivery_settlements (merchant_id, currency, created_at),
  CONSTRAINT fk_merchant_delivery_settlement_merchant FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_merchant_delivery_settlement_admin FOREIGN KEY (settled_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE delivery_tasks
  ADD COLUMN delivery_fee_settled_at DATETIME NULL,
  ADD COLUMN delivery_fee_settled_by BIGINT UNSIGNED NULL,
  ADD INDEX idx_delivery_fee_settlement (delivery_fee_settled_at, courier_id, status);
