-- Marketplace Chat: apply after the Marketplace core and listing migrations.
-- This migration is local only until the owner explicitly confirms production deployment.

CREATE TABLE marketplace_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  buyer_id BIGINT UNSIGNED NOT NULL,
  seller_id BIGINT UNSIGNED NOT NULL,
  listing_title_snapshot VARCHAR(180) NOT NULL,
  listing_price_snapshot DECIMAL(14,2) NULL,
  listing_currency_snapshot ENUM('USD','SYP') NULL,
  listing_image_path_snapshot VARCHAR(255) NULL,
  last_message_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_conversations_distinct_participants CHECK (buyer_id <> seller_id),
  UNIQUE KEY uk_marketplace_conversations_listing_buyer_seller (listing_id, buyer_id, seller_id),
  INDEX idx_marketplace_conversations_buyer_recent (buyer_id, last_message_at, id),
  INDEX idx_marketplace_conversations_seller_recent (seller_id, last_message_at, id),
  CONSTRAINT fk_marketplace_conversations_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_conversations_buyer
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_conversations_seller
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE marketplace_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  message_type ENUM('text','image','offer','counter_offer','system','transaction') NOT NULL DEFAULT 'text',
  body TEXT NULL,
  image_path VARCHAR(255) NULL,
  payload_json JSON NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_marketplace_messages_conversation (conversation_id, created_at, id),
  INDEX idx_marketplace_messages_unread (conversation_id, sender_id, read_at, id),
  CONSTRAINT fk_marketplace_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_messages_sender
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE marketplace_user_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blocker_id BIGINT UNSIGNED NOT NULL,
  blocked_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_user_blocks_distinct CHECK (blocker_id <> blocked_id),
  UNIQUE KEY uk_marketplace_user_blocks_pair (blocker_id, blocked_id),
  INDEX idx_marketplace_user_blocks_blocked (blocked_id, blocker_id),
  CONSTRAINT fk_marketplace_user_blocks_blocker
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_user_blocks_blocked
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE marketplace_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id BIGINT UNSIGNED NOT NULL,
  target_user_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  conversation_id BIGINT UNSIGNED NULL,
  message_id BIGINT UNSIGNED NULL,
  reason VARCHAR(80) NOT NULL,
  details TEXT NULL,
  status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_marketplace_reports_distinct_users CHECK (reporter_id <> target_user_id),
  INDEX idx_marketplace_reports_status (status, created_at, id),
  INDEX idx_marketplace_reports_reporter (reporter_id, created_at, id),
  CONSTRAINT fk_marketplace_reports_reporter
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_reports_target
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_reports_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_reports_conversation
    FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_reports_message
    FOREIGN KEY (message_id) REFERENCES marketplace_messages(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_reports_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE marketplace_typing_status (
  conversation_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (conversation_id, user_id),
  INDEX idx_marketplace_typing_status_expiry (expires_at),
  CONSTRAINT fk_marketplace_typing_status_conversation
    FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_typing_status_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
