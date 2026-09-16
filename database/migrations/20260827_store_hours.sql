-- Store opening hours configuration; JSON keeps full/partial schedules and holidays extensible.
SET @has_opening_hours_json := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'stores' AND column_name = 'opening_hours_json');
SET @sql := IF(@has_opening_hours_json = 0, 'ALTER TABLE stores ADD COLUMN opening_hours_json TEXT NULL AFTER store_type', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
