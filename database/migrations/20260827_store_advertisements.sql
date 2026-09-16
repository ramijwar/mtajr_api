-- إعلانات خاصة بالمتاجر تظهر أعلى كتالوج المتجر.
-- ترحيل إضافي آمن: لا يحذف بيانات المتاجر أو المنتجات ويمكن تشغيله مرة أخرى.
CREATE TABLE IF NOT EXISTS store_advertisements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  body VARCHAR(500) NULL,
  image_path VARCHAR(255) NULL,
  target_type ENUM('none','product','url') NOT NULL DEFAULT 'none',
  target_product_id BIGINT UNSIGNED NULL,
  target_url VARCHAR(1000) NULL,
  display_order INT NOT NULL DEFAULT 0,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_ads_active (store_id, status, starts_at, ends_at, display_order, id),
  INDEX idx_store_ads_product (target_product_id),
  CONSTRAINT fk_store_ads_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  CONSTRAINT fk_store_ads_product FOREIGN KEY (target_product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
