-- اشتراكات الزبائن بالمتاجر وإشعارات إعلانات المتجر
-- آمن لإعادة التنفيذ ولا يحذف أي بيانات موجودة.
CREATE TABLE IF NOT EXISTS store_followers (
  user_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, store_id),
  INDEX idx_store_followers_store_status (store_id, status, created_at),
  INDEX idx_store_followers_user_status (user_id, status, created_at),
  CONSTRAINT fk_store_followers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_store_followers_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول marketplace_notifications الحالي يملك بالفعل فهرساً فريداً
-- على (user_id, event_key)، لذلك يمنع INSERT IGNORE التكرار دون تعديل الجدول.
