-- تقييمات المتاجر المرتبطة بطلبات مسلّمة فقط.
-- نفّذ هذا الترحيل مرة واحدة داخل قاعدة tlamsite_souqlink.

ALTER TABLE stores
  ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0 AFTER is_verified,
  ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_avg;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
