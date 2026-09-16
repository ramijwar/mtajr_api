-- Marketplace Offers and Transactions.
-- Apply after 20260824_add_marketplace_chat.sql.
-- A successful offer reserves the listing and creates one Marketplace transaction;
-- it never changes the listing directly to sold.

CREATE TABLE marketplace_transactions (
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
  CONSTRAINT chk_marketplace_transactions_distinct_participants CHECK (buyer_id <> seller_id),
  UNIQUE KEY uk_marketplace_transactions_number (transaction_number),
  UNIQUE KEY uk_marketplace_transactions_offer (offer_id),
  INDEX idx_marketplace_transactions_buyer (buyer_id, created_at, id),
  INDEX idx_marketplace_transactions_seller (seller_id, created_at, id),
  INDEX idx_marketplace_transactions_listing (listing_id, created_at, id),
  CONSTRAINT fk_marketplace_transactions_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_transactions_buyer
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_transactions_seller
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE marketplace_offers (
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
  CONSTRAINT chk_marketplace_offers_distinct_participants CHECK (buyer_id <> seller_id),
  INDEX idx_marketplace_offers_conversation (conversation_id, created_at, id),
  INDEX idx_marketplace_offers_listing_status (listing_id, status, expires_at, id),
  INDEX idx_marketplace_offers_buyer (buyer_id, created_at, id),
  INDEX idx_marketplace_offers_seller (seller_id, created_at, id),
  CONSTRAINT fk_marketplace_offers_conversation
    FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_offers_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_offers_buyer
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_offers_seller
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_offers_creator
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_offers_parent
    FOREIGN KEY (parent_offer_id) REFERENCES marketplace_offers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_offers_responder
    FOREIGN KEY (responded_by_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_offers_message
    FOREIGN KEY (message_id) REFERENCES marketplace_messages(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_offers_transaction
    FOREIGN KEY (transaction_id) REFERENCES marketplace_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE marketplace_transactions
  ADD CONSTRAINT fk_marketplace_transactions_offer
    FOREIGN KEY (offer_id) REFERENCES marketplace_offers(id) ON DELETE RESTRICT;
