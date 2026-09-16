-- منصة تجارتي: ترحيل محادثات الحراج المتوافق مع MySQL/MariaDB القديم.
-- نفّذ هذا الملف أولاً بعد جداول الحراج الأساسية.
-- لا يحذف أي جدول أو بيانات موجودة.

CREATE TABLE IF NOT EXISTS marketplace_conversations (
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
  UNIQUE KEY uk_marketplace_conversations_listing_buyer_seller (listing_id, buyer_id, seller_id),
  INDEX idx_marketplace_conversations_buyer_recent (buyer_id, last_message_at, id),
  INDEX idx_marketplace_conversations_seller_recent (seller_id, last_message_at, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_messages (
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
  INDEX idx_marketplace_messages_unread (conversation_id, sender_id, read_at, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_user_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  blocker_id BIGINT UNSIGNED NOT NULL,
  blocked_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_user_blocks_pair (blocker_id, blocked_id),
  INDEX idx_marketplace_user_blocks_blocked (blocked_id, blocker_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_reports (
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
  INDEX idx_marketplace_reports_status (status, created_at, id),
  INDEX idx_marketplace_reports_reporter (reporter_id, created_at, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_typing_status (
  conversation_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (conversation_id, user_id),
  INDEX idx_marketplace_typing_status_expiry (expires_at)
) ENGINE=InnoDB;

-- العلاقات المنطقية والتحقق من المشاركين يطبقهما API، لتوافق الاستضافة مع النسخ القديمة.
-- لا يُضاف CHECK هنا لأن محلل phpMyAdmin في الخادم رفضه.
