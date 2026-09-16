-- Marketplace reviews are bound to a completed transaction, not to listing status.
-- Both buyer-to-seller and seller-to-buyer reviews are supported once per transaction.

CREATE TABLE marketplace_transaction_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT UNSIGNED NOT NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  reviewee_id BIGINT UNSIGNED NOT NULL,
  reviewer_role ENUM('buyer','seller') NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_transaction_reviews_distinct_users CHECK (reviewer_id <> reviewee_id),
  CONSTRAINT chk_marketplace_transaction_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  UNIQUE KEY uk_marketplace_transaction_reviews_once (transaction_id, reviewer_id),
  INDEX idx_marketplace_transaction_reviews_reviewee (reviewee_id, created_at, id),
  CONSTRAINT fk_marketplace_transaction_reviews_transaction
    FOREIGN KEY (transaction_id) REFERENCES marketplace_transactions(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_transaction_reviews_reviewer
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_transaction_reviews_reviewee
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
