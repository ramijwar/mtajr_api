-- Apply only after selecting the existing SouqLink database in phpMyAdmin.
-- All Marketplace tables end with _marketplace to remain isolated from stores,
-- products, orders, and delivery tables already used by SouqLink.

CREATE TABLE categories_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  icon_key VARCHAR(100) NULL,
  image_path VARCHAR(255) NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_categories_marketplace_slug (slug),
  INDEX idx_categories_marketplace_parent_visible (parent_id, status, display_order, id),
  CONSTRAINT fk_categories_marketplace_parent
    FOREIGN KEY (parent_id) REFERENCES categories_marketplace(id) ON DELETE RESTRICT,
  CONSTRAINT fk_categories_marketplace_creator
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE category_attributes_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  attribute_key VARCHAR(100) NOT NULL,
  label VARCHAR(160) NOT NULL,
  field_type ENUM('text','number','select','multi_select','boolean','date','year','price','textarea') NOT NULL,
  placeholder VARCHAR(255) NULL,
  help_text VARCHAR(500) NULL,
  is_required BOOLEAN NOT NULL DEFAULT FALSE,
  applies_to_descendants BOOLEAN NOT NULL DEFAULT FALSE,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_category_attributes_marketplace_key (category_id, attribute_key),
  INDEX idx_category_attributes_marketplace_visible (category_id, status, display_order, id),
  CONSTRAINT fk_category_attributes_marketplace_category
    FOREIGN KEY (category_id) REFERENCES categories_marketplace(id) ON DELETE CASCADE,
  CONSTRAINT fk_category_attributes_marketplace_creator
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE category_attribute_options_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attribute_id BIGINT UNSIGNED NOT NULL,
  option_key VARCHAR(100) NOT NULL,
  label VARCHAR(160) NOT NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_category_attribute_options_marketplace_key (attribute_id, option_key),
  INDEX idx_category_attribute_options_marketplace_visible (attribute_id, status, display_order, id),
  CONSTRAINT fk_category_attribute_options_marketplace_attribute
    FOREIGN KEY (attribute_id) REFERENCES category_attributes_marketplace(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE listings_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_user_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  listing_kind ENUM('item','service') NOT NULL DEFAULT 'item',
  price DECIMAL(14,2) NULL,
  currency ENUM('USD','SYP') NULL,
  is_negotiable BOOLEAN NOT NULL DEFAULT TRUE,
  fulfillment_mode ENUM('pickup','delivery','both') NOT NULL DEFAULT 'pickup',
  location_text VARCHAR(500) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  status ENUM('draft','published','reserved','sold','expired','hidden','rejected') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_listings_marketplace_public (status, category_id, published_at, id),
  INDEX idx_listings_marketplace_seller (seller_user_id, status, created_at),
  INDEX idx_listings_marketplace_store (store_id, status, created_at),
  CONSTRAINT fk_listings_marketplace_seller
    FOREIGN KEY (seller_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_listings_marketplace_store
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
  CONSTRAINT fk_listings_marketplace_category
    FOREIGN KEY (category_id) REFERENCES categories_marketplace(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE listing_images_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_listing_images_marketplace_sort (listing_id, sort_order, id),
  CONSTRAINT fk_listing_images_marketplace_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE listing_attribute_values_marketplace (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id BIGINT UNSIGNED NOT NULL,
  category_attribute_id BIGINT UNSIGNED NOT NULL,
  value_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_listing_attribute_values_marketplace (listing_id, category_attribute_id),
  CONSTRAINT fk_listing_attribute_values_marketplace_listing
    FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  CONSTRAINT fk_listing_attribute_values_marketplace_attribute
    FOREIGN KEY (category_attribute_id) REFERENCES category_attributes_marketplace(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
