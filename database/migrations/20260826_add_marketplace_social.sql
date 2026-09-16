-- Marketplace social layer. Isolated from store comments/follows.
CREATE TABLE IF NOT EXISTS marketplace_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  status ENUM('visible','hidden','deleted') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_marketplace_comments_listing (listing_id, status, created_at, id),
  INDEX idx_marketplace_comments_user (user_id, created_at, id),
  CONSTRAINT fk_marketplace_comments_listing FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_comments_parent FOREIGN KEY (parent_id) REFERENCES marketplace_comments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_follows (
  follower_id BIGINT UNSIGNED NOT NULL,
  followed_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, followed_id),
  INDEX idx_marketplace_follows_followed (followed_id, created_at, follower_id),
  CONSTRAINT fk_marketplace_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_follows_followed FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Optional denormalized counters are deliberately avoided; counts are derived from source rows.
