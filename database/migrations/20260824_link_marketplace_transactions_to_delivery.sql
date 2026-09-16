-- Reuse the existing SouqLink delivery_tasks table for Marketplace transactions.
-- Apply after the Marketplace transaction refactor and existing delivery task migrations.

ALTER TABLE delivery_tasks
  MODIFY order_id BIGINT UNSIGNED NULL,
  ADD COLUMN marketplace_transaction_id BIGINT UNSIGNED NULL AFTER order_id,
  ADD COLUMN pickup_location_text VARCHAR(500) NULL AFTER currency,
  ADD COLUMN pickup_latitude DECIMAL(10,7) NULL AFTER pickup_location_text,
  ADD COLUMN pickup_longitude DECIMAL(10,7) NULL AFTER pickup_latitude,
  ADD COLUMN delivery_location_text VARCHAR(500) NULL AFTER pickup_longitude,
  ADD COLUMN delivery_latitude DECIMAL(10,7) NULL AFTER delivery_location_text,
  ADD COLUMN delivery_longitude DECIMAL(10,7) NULL AFTER delivery_latitude,
  ADD COLUMN package_information VARCHAR(500) NULL AFTER delivery_longitude,
  ADD COLUMN tracking_code VARCHAR(48) NULL AFTER package_information;

ALTER TABLE delivery_tasks
  MODIFY status ENUM('available','accepted','rejected','picked_up','proof_submitted','approved','disputed','requested','searching_driver','driver_assigned','going_to_pickup','in_transit','delivered','cancelled') NOT NULL DEFAULT 'available',
  ADD UNIQUE KEY uk_delivery_tasks_marketplace_transaction (marketplace_transaction_id),
  ADD UNIQUE KEY uk_delivery_tasks_tracking_code (tracking_code),
  ADD INDEX idx_delivery_tasks_marketplace_status (marketplace_transaction_id, status),
  ADD CONSTRAINT fk_delivery_tasks_marketplace_transaction
    FOREIGN KEY (marketplace_transaction_id) REFERENCES marketplace_transactions(id) ON DELETE RESTRICT;
