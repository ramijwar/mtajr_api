-- سجل زيارات المتاجر لحساب متجر ترند الأسبوع.
-- لا يحذف أي بيانات قائمة، ويمكن تشغيله بأمان مرة واحدة.
CREATE TABLE IF NOT EXISTS store_visit_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id BIGINT UNSIGNED NOT NULL,
  viewer_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_store_visit_week (store_id, created_at),
  INDEX idx_store_visit_viewer (viewer_id, created_at),
  CONSTRAINT fk_store_visit_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  CONSTRAINT fk_store_visit_viewer FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
