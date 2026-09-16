-- Repair drifted production schemas without deleting or rewriting business data.
-- Safe to run more than once on MariaDB versions supporting ADD COLUMN IF NOT EXISTS.

ALTER TABLE listings_marketplace
  ADD COLUMN IF NOT EXISTS item_condition ENUM('new','used') NULL AFTER listing_kind,
  ADD COLUMN IF NOT EXISTS country_location_id BIGINT UNSIGNED NULL AFTER fulfillment_mode,
  ADD COLUMN IF NOT EXISTS city_location_id BIGINT UNSIGNED NULL AFTER country_location_id,
  ADD COLUMN IF NOT EXISTS area_location_id BIGINT UNSIGNED NULL AFTER city_location_id,
  ADD COLUMN IF NOT EXISTS view_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured;

ALTER TABLE verification_requests
  ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE AFTER status;

UPDATE verification_requests
SET is_active = CASE WHEN status = 'approved' THEN 1 ELSE 0 END;
