-- منصة تجارتي: ترحيل عروض وصفقات الحراج المتوافق مع MySQL/MariaDB.
-- نفّذ هذا الملف بعد marketplace_chat_compatible.sql.
-- لا يحذف أي جدول أو بيانات موجودة.

CREATE TABLE IF NOT EXISTS marketplace_offers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  listing_id BIGINT UNSIGNED NOT NULL,
  buyer_id BIGINT UNSIGNED NOT NULL,
  seller_id BIGINT UNSIGNED NOT NULL,
  created_by_id BIGINT UNSIGNED NOT NULL,
  parent_offer_id BIGINT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  status ENUM('pending','accepted','rejected','counter_offer','expired','cancelled') NOT NULL DEFAULT 'pending',
  expires_at DATETIME NOT NULL,
  responded_at DATETIME NULL,
  responded_by_id BIGINT UNSIGNED NULL,
  message_id BIGINT UNSIGNED NULL,
  transaction_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_marketplace_offers_conversation (conversation_id, created_at, id),
  INDEX idx_marketplace_offers_listing_status (listing_id, status, expires_at, id),
  INDEX idx_marketplace_offers_buyer (buyer_id, created_at, id),
  INDEX idx_marketplace_offers_seller (seller_id, created_at, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS marketplace_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_number VARCHAR(48) NOT NULL,
  listing_id BIGINT UNSIGNED NOT NULL,
  offer_id BIGINT UNSIGNED NULL,
  buyer_id BIGINT UNSIGNED NOT NULL,
  seller_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  currency ENUM('USD','SYP') NOT NULL,
  status ENUM('pending','payment_pending','payment_review','paid','cancelled','expired','completed') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_transactions_number (transaction_number),
  UNIQUE KEY uk_marketplace_transactions_offer (offer_id),
  INDEX idx_marketplace_transactions_buyer (buyer_id, created_at, id),
  INDEX idx_marketplace_transactions_seller (seller_id, created_at, id),
  INDEX idx_marketplace_transactions_listing (listing_id, created_at, id)
) ENGINE=InnoDB;

-- قيد UNIQUE على offer_id يمنع إنشاء صفقتين للعرض المقبول نفسه.
-- التحقق من buyer_id <> seller_id وتحديث حالة الإعلان يتم داخل API.
-- أزيلت قيود CHECK وFOREIGN KEY من هذا الملف لتفادي رفض محلل phpMyAdmin القديم.
