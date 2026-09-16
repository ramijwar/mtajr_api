-- تقييم عامل التوصيل من الزبون بعد اكتمال الطلب
CREATE TABLE IF NOT EXISTS courier_reviews (
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
  CONSTRAINT chk_courier_review_rating CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;
