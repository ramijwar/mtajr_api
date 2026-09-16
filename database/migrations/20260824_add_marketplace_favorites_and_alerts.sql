-- Favorites and local-sync alert state for Marketplace.
-- Apply after Marketplace core and Marketplace search migrations.

CREATE TABLE favorites_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  listing_title_snapshot VARCHAR(180) NOT NULL,
  image_path_snapshot VARCHAR(255) NULL,
  price_snapshot DECIMAL(14,2) NULL,
  currency_snapshot ENUM('USD','SYP') NULL,
  last_known_price DECIMAL(14,2) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_favorites_marketplace_user_listing (user_id, listing_id),
  INDEX idx_favorites_marketplace_user_created (user_id, created_at, id),
  CONSTRAINT fk_favorites_marketplace_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_marketplace_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE marketplace_alert_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  rule_type ENUM('new_listing','price_drop') NOT NULL,
  filters_json JSON NULL,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  last_checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_marketplace_alert_rules_user (user_id, status, rule_type, id),
  CONSTRAINT fk_marketplace_alert_rules_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE marketplace_alert_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  rule_id BIGINT UNSIGNED NULL,
  event_type ENUM('new_listing','price_drop') NOT NULL,
  event_key VARCHAR(255) NOT NULL,
  payload_json JSON NOT NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  delivered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_alert_events_user_key (user_id, event_key),
  INDEX idx_marketplace_alert_events_pending (user_id, delivered_at, occurred_at, id),
  CONSTRAINT fk_marketplace_alert_events_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_alert_events_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_alert_events_rule
    FOREIGN KEY (rule_id) REFERENCES marketplace_alert_rules(id) ON DELETE SET NULL
) ENGINE=InnoDB;
