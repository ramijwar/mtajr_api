-- Marketplace home configuration and geographic hierarchy.
-- Apply only to the existing SouqLink database after the Marketplace core migration.

CREATE TABLE marketplace_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  value_text VARCHAR(500) NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_marketplace_settings_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE marketplace_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  location_type ENUM('country','city','area') NOT NULL,
  name VARCHAR(160) NOT NULL,
  code VARCHAR(24) NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_marketplace_locations_parent_name (parent_id, name),
  UNIQUE KEY uk_marketplace_locations_code (code),
  INDEX idx_marketplace_locations_visible (parent_id, location_type, status, display_order, id),
  CONSTRAINT fk_marketplace_locations_parent
    FOREIGN KEY (parent_id) REFERENCES marketplace_locations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_marketplace_locations_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE listings_marketplace
  ADD COLUMN is_featured BOOLEAN NOT NULL DEFAULT FALSE AFTER is_negotiable,
  ADD COLUMN featured_until DATETIME NULL AFTER is_featured,
  ADD INDEX idx_listings_marketplace_featured (status, is_featured, featured_until, published_at, id);

INSERT INTO marketplace_settings (setting_key, value_text)
VALUES ('home_title', 'الحراج')
ON DUPLICATE KEY UPDATE value_text=VALUES(value_text);

-- Countries are initial geographic reference data in the database, not Flutter constants.
INSERT INTO marketplace_locations (parent_id, location_type, name, code, display_order, status) VALUES
  (NULL, 'country', 'الأردن', 'JO', 10, 'active'),
  (NULL, 'country', 'الإمارات العربية المتحدة', 'AE', 20, 'active'),
  (NULL, 'country', 'البحرين', 'BH', 30, 'active'),
  (NULL, 'country', 'الجزائر', 'DZ', 40, 'active'),
  (NULL, 'country', 'جيبوتي', 'DJ', 50, 'active'),
  (NULL, 'country', 'جزر القمر', 'KM', 60, 'active'),
  (NULL, 'country', 'السعودية', 'SA', 70, 'active'),
  (NULL, 'country', 'السودان', 'SD', 80, 'active'),
  (NULL, 'country', 'سوريا', 'SY', 90, 'active'),
  (NULL, 'country', 'الصومال', 'SO', 100, 'active'),
  (NULL, 'country', 'العراق', 'IQ', 110, 'active'),
  (NULL, 'country', 'عُمان', 'OM', 120, 'active'),
  (NULL, 'country', 'فلسطين', 'PS', 130, 'active'),
  (NULL, 'country', 'قطر', 'QA', 140, 'active'),
  (NULL, 'country', 'الكويت', 'KW', 150, 'active'),
  (NULL, 'country', 'لبنان', 'LB', 160, 'active'),
  (NULL, 'country', 'ليبيا', 'LY', 170, 'active'),
  (NULL, 'country', 'مصر', 'EG', 180, 'active'),
  (NULL, 'country', 'المغرب', 'MA', 190, 'active'),
  (NULL, 'country', 'موريتانيا', 'MR', 200, 'active'),
  (NULL, 'country', 'اليمن', 'YE', 210, 'active'),
  (NULL, 'country', 'تونس', 'TN', 220, 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), display_order=VALUES(display_order), status=VALUES(status);
