-- Timed product offers for retail store products.
-- Safe additive migration: no existing product or order data is deleted.

SET @db_name = DATABASE();

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = @db_name AND table_name = 'products' AND column_name = 'offer_price'
  ),
  'SELECT 1',
  'ALTER TABLE products ADD COLUMN offer_price DECIMAL(18,2) NULL AFTER price'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = @db_name AND table_name = 'products' AND column_name = 'offer_starts_at'
  ),
  'SELECT 1',
  'ALTER TABLE products ADD COLUMN offer_starts_at DATETIME NULL AFTER offer_price'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = @db_name AND table_name = 'products' AND column_name = 'offer_ends_at'
  ),
  'SELECT 1',
  'ALTER TABLE products ADD COLUMN offer_ends_at DATETIME NULL AFTER offer_starts_at'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = @db_name AND table_name = 'products' AND index_name = 'idx_products_offer_window'
  ),
  'SELECT 1',
  'ALTER TABLE products ADD INDEX idx_products_offer_window (offer_ends_at, status, store_id)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Existing products remain without an offer until the merchant explicitly creates one.
