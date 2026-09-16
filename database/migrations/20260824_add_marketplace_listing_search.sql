-- Search and filter support for Marketplace listings.
-- Apply after Marketplace core and Marketplace home/location migrations.

ALTER TABLE listings_marketplace
  ADD COLUMN item_condition ENUM('new','used') NULL AFTER listing_kind,
  ADD COLUMN country_location_id BIGINT UNSIGNED NULL AFTER fulfillment_mode,
  ADD COLUMN city_location_id BIGINT UNSIGNED NULL AFTER country_location_id,
  ADD COLUMN area_location_id BIGINT UNSIGNED NULL AFTER city_location_id,
  ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured,
  ADD INDEX idx_listings_marketplace_search_category (status, category_id, published_at, id),
  ADD INDEX idx_listings_marketplace_search_price (status, currency, price, id),
  ADD INDEX idx_listings_marketplace_search_location (status, country_location_id, city_location_id, area_location_id, id),
  ADD INDEX idx_listings_marketplace_search_views (status, view_count, published_at, id),
  ADD CONSTRAINT fk_listings_marketplace_country_location
    FOREIGN KEY (country_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_listings_marketplace_city_location
    FOREIGN KEY (city_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_listings_marketplace_area_location
    FOREIGN KEY (area_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL;
