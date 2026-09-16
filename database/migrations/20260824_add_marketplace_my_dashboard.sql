-- My Marketplace dashboard state and promotion requests.
-- Apply after 20260824_add_marketplace_offers_and_transactions.sql.

ALTER TABLE listings_marketplace
  MODIFY status ENUM('draft','pending_review','published','reserved','sold','expired','hidden','rejected','deleted') NOT NULL DEFAULT 'draft';

CREATE TABLE marketplace_promotion_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  requested_by_id BIGINT UNSIGNED NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  note VARCHAR(500) NULL,
  reviewed_by_id BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_marketplace_promotion_listing (listing_id, status, created_at),
  INDEX idx_marketplace_promotion_requester (requested_by_id, status, created_at),
  CONSTRAINT fk_marketplace_promotion_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_promotion_requester
    FOREIGN KEY (requested_by_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_promotion_reviewer
    FOREIGN KEY (reviewed_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
