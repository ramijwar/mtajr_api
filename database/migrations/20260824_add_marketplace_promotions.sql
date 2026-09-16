-- Marketplace Promotion. Apply after the existing Marketplace migrations.
-- Promotions are separate from listing status; expiring one never hides or deletes its listing.

CREATE TABLE marketplace_promotion_prices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  promotion_type ENUM('featured','top_category','boost','highlight') NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  price DECIMAL(14,2) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_promotion_price_amount CHECK (price >= 0),
  CONSTRAINT chk_marketplace_promotion_price_duration CHECK (duration_days BETWEEN 1 AND 365),
  UNIQUE KEY uk_marketplace_promotion_price (promotion_type, duration_days, currency),
  INDEX idx_marketplace_promotion_prices_public (status, promotion_type, duration_days, currency)
) ENGINE=InnoDB;

CREATE TABLE marketplace_promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  promotion_type ENUM('featured','top_category','boost','highlight') NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  payment_id BIGINT UNSIGNED NULL,
  price_id BIGINT UNSIGNED NULL,
  price_amount DECIMAL(14,2) NULL,
  price_currency ENUM('USD','SYP') NULL,
  status ENUM('pending','active','expired','cancelled','rejected') NOT NULL DEFAULT 'pending',
  note VARCHAR(500) NULL,
  reviewed_by_id BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_promotion_period CHECK (end_at IS NULL OR start_at IS NULL OR end_at > start_at),
  CONSTRAINT chk_marketplace_promotion_duration CHECK (duration_days BETWEEN 1 AND 365),
  INDEX idx_marketplace_promotions_listing (listing_id, promotion_type, status, end_at),
  INDEX idx_marketplace_promotions_user (user_id, status, created_at),
  INDEX idx_marketplace_promotions_public (status, promotion_type, end_at, listing_id),
  CONSTRAINT fk_marketplace_promotions_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_promotions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_promotions_price
    FOREIGN KEY (price_id) REFERENCES marketplace_promotion_prices(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_promotions_reviewer
    FOREIGN KEY (reviewed_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
