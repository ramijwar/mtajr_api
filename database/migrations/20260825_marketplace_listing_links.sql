-- Optional references for Marketplace listings. Existing listings remain unchanged.
ALTER TABLE listings_marketplace
  ADD COLUMN link_type ENUM('none','product','external') NOT NULL DEFAULT 'none' AFTER fulfillment_mode,
  ADD COLUMN linked_product_id BIGINT UNSIGNED NULL AFTER link_type,
  ADD COLUMN external_url VARCHAR(2048) NULL AFTER linked_product_id,
  ADD INDEX idx_listings_marketplace_linked_product (linked_product_id),
  ADD CONSTRAINT fk_listings_marketplace_linked_product
    FOREIGN KEY (linked_product_id) REFERENCES products(id) ON DELETE SET NULL;
