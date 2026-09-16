-- عداد مشاهدات منتجات المتاجر. ترحيل إضافي آمن وقابل لإعادة التنفيذ.
SET @db_name = DATABASE();
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db_name AND table_name='products' AND column_name='view_count'), 'SELECT 1', 'ALTER TABLE products ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER sales_count');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=@db_name AND table_name='products' AND index_name='idx_products_store_views'), 'SELECT 1', 'ALTER TABLE products ADD INDEX idx_products_store_views (store_id, view_count, id)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
