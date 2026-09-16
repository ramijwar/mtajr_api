-- Screen targeting for Marketplace promotions. Labels are configurable in the database.
CREATE TABLE marketplace_screens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  screen_key VARCHAR(80) NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_screens_key (screen_key),
  INDEX idx_marketplace_screens_visible (status, display_order, id)
) ENGINE=InnoDB;

INSERT INTO marketplace_screens (screen_key, title, display_order) VALUES
  ('home', 'الرئيسية', 1),
  ('featured', 'الإعلانات المميزة', 2),
  ('latest', 'أحدث الإعلانات', 3),
  ('category', 'شاشة القسم', 4),
  ('search', 'نتائج البحث', 5)
ON DUPLICATE KEY UPDATE title=VALUES(title), display_order=VALUES(display_order);

CREATE TABLE marketplace_promotion_screens (
  promotion_id BIGINT UNSIGNED NOT NULL,
  screen_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (promotion_id, screen_id),
  CONSTRAINT fk_marketplace_promotion_screens_promotion
    FOREIGN KEY (promotion_id) REFERENCES marketplace_promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_promotion_screens_screen
    FOREIGN KEY (screen_id) REFERENCES marketplace_screens(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
