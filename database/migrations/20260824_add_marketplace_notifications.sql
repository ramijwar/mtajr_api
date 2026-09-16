-- Marketplace notification center. This is an in-app/local synchronization source,
-- not a separate push delivery provider.

CREATE TABLE marketplace_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  notification_type VARCHAR(80) NOT NULL,
  title VARCHAR(180) NOT NULL,
  body VARCHAR(1000) NOT NULL,
  event_key VARCHAR(255) NOT NULL,
  listing_id BIGINT UNSIGNED NULL,
  conversation_id BIGINT UNSIGNED NULL,
  offer_id BIGINT UNSIGNED NULL,
  transaction_id BIGINT UNSIGNED NULL,
  delivery_task_id BIGINT UNSIGNED NULL,
  review_id BIGINT UNSIGNED NULL,
  payload_json JSON NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_notifications_user_event (user_id, event_key),
  INDEX idx_marketplace_notifications_inbox (user_id, read_at, created_at, id),
  CONSTRAINT fk_marketplace_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_notifications_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_notifications_conversation
    FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_notifications_offer
    FOREIGN KEY (offer_id) REFERENCES marketplace_offers(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_notifications_transaction
    FOREIGN KEY (transaction_id) REFERENCES marketplace_transactions(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_notifications_delivery
    FOREIGN KEY (delivery_task_id) REFERENCES delivery_tasks(id) ON DELETE SET NULL,
  CONSTRAINT fk_marketplace_notifications_review
    FOREIGN KEY (review_id) REFERENCES marketplace_transaction_reviews(id) ON DELETE SET NULL
) ENGINE=InnoDB;
