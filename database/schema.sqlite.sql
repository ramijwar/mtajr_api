-- SouqLink SQLite Schema
-- Complete schema compatible with all SouqLink API routes
PRAGMA foreign_keys = ON;

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  phone TEXT NOT NULL UNIQUE,
  email TEXT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'customer',
  status TEXT NOT NULL DEFAULT 'pending',
  profile_update_permission TEXT NOT NULL DEFAULT 'allowed',
  avatar_path TEXT NULL,
  push_token TEXT NULL,
  last_login_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);
CREATE INDEX IF NOT EXISTS idx_users_profile_update_permission ON users(profile_update_permission);

-- 2. Merchant Profiles
CREATE TABLE IF NOT EXISTS merchant_profiles (
  user_id INTEGER PRIMARY KEY,
  sham_cash_address TEXT NOT NULL DEFAULT '',
  payout_details TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Stores
CREATE TABLE IF NOT EXISTS stores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NULL,
  merchant_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT NULL,
  logo_path TEXT NULL,
  phone TEXT NULL,
  store_type TEXT NOT NULL DEFAULT 'retail',
  opening_hours_json TEXT NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  is_verified INTEGER NOT NULL DEFAULT 0,
  rating_avg NUMERIC NOT NULL DEFAULT 0,
  rating_count INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (merchant_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_stores_merchant ON stores(merchant_id);
CREATE INDEX IF NOT EXISTS idx_stores_status ON stores(status);

-- 4. Courier Profiles
CREATE TABLE IF NOT EXISTS courier_profiles (
  user_id INTEGER PRIMARY KEY,
  vehicle_type TEXT NULL,
  identity_document_path TEXT NULL,
  is_available INTEGER NOT NULL DEFAULT 1,
  verification_status TEXT NOT NULL DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Verification Requests
CREATE TABLE IF NOT EXISTS verification_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  subject_role TEXT NOT NULL,
  identity_front_path TEXT NOT NULL,
  portrait_path TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  is_active INTEGER NOT NULL DEFAULT 1,
  review_note TEXT NULL,
  reviewed_by INTEGER NULL,
  reviewed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, subject_role),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_verification_status_created ON verification_requests(status, created_at);
CREATE INDEX IF NOT EXISTS idx_verification_active ON verification_requests(subject_role, status, is_active, updated_at);

-- 6. Advertisements
CREATE TABLE IF NOT EXISTS advertisements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  body TEXT NULL,
  image_path TEXT NOT NULL,
  link_url TEXT NULL,
  size_preset TEXT NOT NULL DEFAULT 'banner',
  target_type TEXT NOT NULL DEFAULT 'all',
  target_value TEXT NULL,
  target_role TEXT NOT NULL DEFAULT 'all',
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  status TEXT NOT NULL DEFAULT 'active',
  starts_at TEXT NULL,
  ends_at TEXT NULL,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_advertisements_active ON advertisements(is_active, target_role, display_order);

-- 7. Store Advertisements
CREATE TABLE IF NOT EXISTS store_advertisements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  body TEXT NULL,
  image_path TEXT NOT NULL,
  target_type TEXT NOT NULL DEFAULT 'store',
  target_product_id INTEGER NULL,
  target_url TEXT NULL,
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  status TEXT NOT NULL DEFAULT 'active',
  starts_at TEXT NULL,
  ends_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (target_product_id) REFERENCES products(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_store_advertisements_store ON store_advertisements(store_id, is_active, display_order, id);

-- 8. Store Followers
CREATE TABLE IF NOT EXISTS store_followers (
  user_id INTEGER NOT NULL,
  store_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, store_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_store_followers_store ON store_followers(store_id, status);

-- 9. Store Visit Events
CREATE TABLE IF NOT EXISTS store_visit_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL,
  viewer_id INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_store_visit_week ON store_visit_events(store_id, created_at);
CREATE INDEX IF NOT EXISTS idx_store_visit_viewer ON store_visit_events(viewer_id, created_at);

-- 10. Courier Store Subscriptions
CREATE TABLE IF NOT EXISTS courier_store_subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL,
  courier_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (store_id, courier_id),
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_css_courier ON courier_store_subscriptions(courier_id, status);

-- 11. Categories
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  icon_path TEXT NULL,
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_categories_order ON categories(is_active, display_order);

-- 12. Platform Settings
CREATE TABLE IF NOT EXISTS platform_settings (
  setting_key TEXT PRIMARY KEY,
  decimal_value NUMERIC NOT NULL,
  updated_by INTEGER NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- 13. Platform Wallets
CREATE TABLE IF NOT EXISTS platform_wallets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  currency TEXT NOT NULL UNIQUE,
  available_balance NUMERIC NOT NULL DEFAULT 0,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 14. Products
CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT NULL,
  price NUMERIC NOT NULL,
  offer_price NUMERIC NULL,
  offer_starts_at TEXT NULL,
  offer_ends_at TEXT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  delivery_fee NUMERIC NOT NULL DEFAULT 0,
  cost_price NUMERIC NOT NULL DEFAULT 0,
  stock_quantity INTEGER NOT NULL DEFAULT 0,
  preorder_unit_label TEXT NULL,
  preorder_min_quantity NUMERIC NULL,
  preorder_lead_days INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  rating_avg NUMERIC NOT NULL DEFAULT 0,
  rating_count INTEGER NOT NULL DEFAULT 0,
  sales_count INTEGER NOT NULL DEFAULT 0,
  view_count INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
CREATE INDEX IF NOT EXISTS idx_products_store ON products(store_id, status);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id, status);
CREATE INDEX IF NOT EXISTS idx_products_offer_window ON products(offer_ends_at, status, store_id);
CREATE INDEX IF NOT EXISTS idx_products_store_views ON products(store_id, view_count, id);

-- 15. Product Images
CREATE TABLE IF NOT EXISTS product_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  file_path TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_product_images_product ON product_images(product_id, sort_order);

-- 16. Addresses
CREATE TABLE IF NOT EXISTS addresses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL DEFAULT '',
  label TEXT NOT NULL DEFAULT '',
  address_line TEXT NOT NULL DEFAULT '',
  address_text TEXT NOT NULL DEFAULT '',
  latitude NUMERIC NULL,
  longitude NUMERIC NULL,
  is_default INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_addresses_user ON addresses(user_id, is_default);

-- 17. Carts
CREATE TABLE IF NOT EXISTS carts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  store_id INTEGER NOT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (customer_id, store_id),
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

-- 18. Cart Items
CREATE TABLE IF NOT EXISTS cart_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cart_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (cart_id, product_id),
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 19. Orders
CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_number TEXT NOT NULL UNIQUE,
  customer_id INTEGER NOT NULL,
  store_id INTEGER NOT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  fulfillment_type TEXT NOT NULL DEFAULT 'delivery',
  status TEXT NOT NULL DEFAULT 'pending_payment',
  products_subtotal NUMERIC NOT NULL,
  delivery_total NUMERIC NOT NULL DEFAULT 0,
  platform_fee_percent NUMERIC NOT NULL DEFAULT 0,
  platform_fee_total NUMERIC NOT NULL DEFAULT 0,
  grand_total NUMERIC NOT NULL,
  delivery_address_text TEXT NULL,
  delivery_latitude NUMERIC NULL,
  delivery_longitude NUMERIC NULL,
  sham_cash_address_snapshot TEXT NULL,
  customer_notes TEXT NULL,
  notes TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (store_id) REFERENCES stores(id)
);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_orders_store ON orders(store_id, status);

-- 20. Order Items
CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  product_name_snapshot TEXT NOT NULL,
  unit_price NUMERIC NOT NULL,
  unit_cost NUMERIC NOT NULL DEFAULT 0,
  unit_delivery_fee NUMERIC NOT NULL DEFAULT 0,
  quantity INTEGER NOT NULL,
  line_total NUMERIC NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 21. Preorder Orders
CREATE TABLE IF NOT EXISTS preorder_orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  preorder_number TEXT NOT NULL UNIQUE,
  order_number TEXT NULL,
  customer_id INTEGER NOT NULL,
  store_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  product_name_snapshot TEXT NULL,
  unit_label_snapshot TEXT NULL,
  requested_quantity NUMERIC NULL,
  unit_price_snapshot NUMERIC NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  products_subtotal NUMERIC NOT NULL,
  delivery_total NUMERIC NOT NULL DEFAULT 0,
  platform_fee_percent NUMERIC NOT NULL DEFAULT 0,
  platform_fee_total NUMERIC NOT NULL DEFAULT 0,
  grand_total NUMERIC NOT NULL,
  fulfillment_type TEXT NOT NULL DEFAULT 'delivery',
  delivery_address_text TEXT NULL,
  delivery_latitude NUMERIC NULL,
  delivery_longitude NUMERIC NULL,
  requested_fulfillment_at TEXT NULL,
  sham_cash_address_snapshot TEXT NULL,
  customer_note TEXT NULL,
  customer_notes TEXT NULL,
  notes TEXT NULL,
  merchant_rejection_reason TEXT NULL,
  status TEXT NOT NULL DEFAULT 'pending_approval',
  merchant_payment_review_status TEXT NULL,
  merchant_payment_review_note TEXT NULL,
  merchant_payment_reviewed_at TEXT NULL,
  merchant_reminder_hours INTEGER NULL,
  reminder_sent_at TEXT NULL,
  merchant_confirmed_at TEXT NULL,
  fulfilled_at TEXT NULL,
  approved_at TEXT NULL,
  expected_ready_date TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE INDEX IF NOT EXISTS idx_preorder_orders_customer ON preorder_orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_preorder_orders_store ON preorder_orders(store_id, status);

-- 22. Platform Wallet Transactions
CREATE TABLE IF NOT EXISTS platform_wallet_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  wallet_id INTEGER NOT NULL,
  transaction_type TEXT NOT NULL,
  direction TEXT NOT NULL,
  amount NUMERIC NOT NULL,
  balance_after NUMERIC NOT NULL,
  order_id INTEGER NULL,
  preorder_id INTEGER NULL,
  description TEXT NOT NULL,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES platform_wallets(id),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_platform_wallet_tx_wallet ON platform_wallet_transactions(wallet_id, created_at);
CREATE INDEX IF NOT EXISTS idx_platform_wallet_tx_preorder ON platform_wallet_transactions(preorder_id);

-- 23. Payment Receipts
CREATE TABLE IF NOT EXISTS payment_receipts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  paid_amount NUMERIC NOT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  transaction_number TEXT NOT NULL UNIQUE,
  receipt_image_path TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'submitted',
  merchant_review_status TEXT NOT NULL DEFAULT 'pending',
  merchant_review_note TEXT NULL,
  merchant_reviewed_at TEXT NULL,
  verified_at TEXT NULL,
  verified_by INTEGER NULL,
  reviewed_by INTEGER NULL,
  reviewed_at TEXT NULL,
  rejection_reason TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (verified_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_payment_receipts_order ON payment_receipts(order_id);
CREATE INDEX IF NOT EXISTS idx_payment_merchant_review ON payment_receipts(merchant_review_status, status, created_at);

-- 24. Preorder Payment Receipts
CREATE TABLE IF NOT EXISTS preorder_payment_receipts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  preorder_id INTEGER NOT NULL,
  provider TEXT NULL DEFAULT 'sham_cash',
  transaction_number TEXT NOT NULL UNIQUE,
  paid_amount NUMERIC NOT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  receipt_image_path TEXT NULL,
  status TEXT NOT NULL DEFAULT 'submitted',
  reviewed_by INTEGER NULL,
  reviewed_at TEXT NULL,
  rejection_reason TEXT NULL,
  verified_at TEXT NULL,
  verified_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (verified_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_preorder_receipts_order ON preorder_payment_receipts(preorder_id);

-- 25. Preorder Conversations
CREATE TABLE IF NOT EXISTS preorder_conversations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  preorder_id INTEGER NOT NULL UNIQUE,
  buyer_id INTEGER NULL,
  merchant_id INTEGER NULL,
  last_message_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (preorder_id) REFERENCES preorder_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id),
  FOREIGN KEY (merchant_id) REFERENCES users(id)
);

-- 26. Preorder Messages
CREATE TABLE IF NOT EXISTS preorder_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  conversation_id INTEGER NOT NULL,
  sender_id INTEGER NOT NULL,
  body TEXT NULL,
  message_text TEXT NULL,
  read_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES preorder_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_preorder_messages_convo ON preorder_messages(conversation_id, created_at);

-- 27. Categories Marketplace
CREATE TABLE IF NOT EXISTS categories_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  parent_id INTEGER NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  icon_key TEXT NULL,
  icon_path TEXT NULL,
  image_path TEXT NULL,
  display_order INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  is_active INTEGER NOT NULL DEFAULT 1,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES categories_marketplace(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_categories_marketplace_tree ON categories_marketplace(parent_id, status, display_order, id);

-- 28. Category Attributes Marketplace
CREATE TABLE IF NOT EXISTS category_attributes_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  attribute_key TEXT NOT NULL,
  label TEXT NOT NULL,
  field_type TEXT NOT NULL DEFAULT 'text',
  placeholder TEXT NULL,
  help_text TEXT NULL,
  is_required INTEGER NOT NULL DEFAULT 0,
  applies_to_descendants INTEGER NOT NULL DEFAULT 0,
  display_order INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  is_filterable INTEGER NOT NULL DEFAULT 1,
  unit TEXT NULL,
  created_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (category_id, attribute_key),
  FOREIGN KEY (category_id) REFERENCES categories_marketplace(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_category_attributes_marketplace_order ON category_attributes_marketplace(category_id, display_order, id);

-- 29. Category Attribute Options Marketplace
CREATE TABLE IF NOT EXISTS category_attribute_options_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  attribute_id INTEGER NOT NULL,
  option_key TEXT NOT NULL,
  label TEXT NOT NULL,
  display_order INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (attribute_id, option_key),
  FOREIGN KEY (attribute_id) REFERENCES category_attributes_marketplace(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_category_attr_opts_order ON category_attribute_options_marketplace(attribute_id, display_order, id);

-- 30. Marketplace Settings
CREATE TABLE IF NOT EXISTS marketplace_settings (
  setting_key TEXT PRIMARY KEY,
  value_text TEXT NOT NULL,
  updated_by INTEGER NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 31. Marketplace Locations
CREATE TABLE IF NOT EXISTS marketplace_locations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  parent_id INTEGER NULL,
  location_type TEXT NOT NULL,
  name TEXT NOT NULL,
  code TEXT NULL UNIQUE,
  display_order INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (parent_id, name),
  FOREIGN KEY (parent_id) REFERENCES marketplace_locations(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_marketplace_locations_visible ON marketplace_locations(parent_id, location_type, status, display_order, id);

-- 32. Listings Marketplace
CREATE TABLE IF NOT EXISTS listings_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  seller_user_id INTEGER NOT NULL,
  store_id INTEGER NULL,
  category_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  description TEXT NOT NULL,
  listing_kind TEXT NOT NULL DEFAULT 'item',
  item_condition TEXT NULL,
  price NUMERIC NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  is_negotiable INTEGER NOT NULL DEFAULT 1,
  is_featured INTEGER NOT NULL DEFAULT 0,
  featured_until TEXT NULL,
  view_count INTEGER NOT NULL DEFAULT 0,
  fulfillment_mode TEXT NOT NULL DEFAULT 'pickup',
  country_location_id INTEGER NULL,
  city_location_id INTEGER NULL,
  area_location_id INTEGER NULL,
  link_type TEXT NOT NULL DEFAULT 'none',
  linked_product_id INTEGER NULL,
  external_url TEXT NULL,
  location_text TEXT NULL,
  address_text TEXT NULL,
  latitude NUMERIC NULL,
  longitude NUMERIC NULL,
  service_available_from TEXT NULL,
  service_available_to TEXT NULL,
  contact_phone TEXT NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  rejection_reason TEXT NULL,
  published_at TEXT NULL,
  expires_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES categories_marketplace(id) ON DELETE RESTRICT,
  FOREIGN KEY (country_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL,
  FOREIGN KEY (city_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL,
  FOREIGN KEY (area_location_id) REFERENCES marketplace_locations(id) ON DELETE SET NULL,
  FOREIGN KEY (linked_product_id) REFERENCES products(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_status ON listings_marketplace(status, published_at, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_seller ON listings_marketplace(seller_user_id, status, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_featured ON listings_marketplace(status, is_featured, featured_until, published_at, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_search_category ON listings_marketplace(status, category_id, published_at, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_search_price ON listings_marketplace(status, currency, price, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_search_location ON listings_marketplace(status, country_location_id, city_location_id, area_location_id, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_search_views ON listings_marketplace(status, view_count, published_at, id);
CREATE INDEX IF NOT EXISTS idx_listings_marketplace_linked_product ON listings_marketplace(linked_product_id);

-- 33. Listing Images Marketplace
CREATE TABLE IF NOT EXISTS listing_images_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  file_path TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_listing_images_order ON listing_images_marketplace(listing_id, sort_order, id);

-- 34. Listing Attribute Values Marketplace
CREATE TABLE IF NOT EXISTS listing_attribute_values_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  category_attribute_id INTEGER NOT NULL,
  value_json TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (listing_id, category_attribute_id),
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (category_attribute_id) REFERENCES category_attributes_marketplace(id) ON DELETE RESTRICT
);

-- 35. Marketplace Conversations
CREATE TABLE IF NOT EXISTS marketplace_conversations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  buyer_id INTEGER NOT NULL,
  seller_id INTEGER NOT NULL,
  listing_title_snapshot TEXT NOT NULL,
  listing_price_snapshot NUMERIC NOT NULL,
  listing_currency_snapshot TEXT NOT NULL,
  listing_image_path_snapshot TEXT NULL,
  last_message_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (listing_id, buyer_id),
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_marketplace_conv_buyer ON marketplace_conversations(buyer_id, updated_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_conv_seller ON marketplace_conversations(seller_id, updated_at, id);

-- 36. Marketplace Messages
CREATE TABLE IF NOT EXISTS marketplace_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  conversation_id INTEGER NOT NULL,
  sender_id INTEGER NOT NULL,
  message_type TEXT NOT NULL DEFAULT 'text',
  body TEXT NULL,
  image_path TEXT NULL,
  payload_json TEXT NULL,
  read_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_marketplace_msg_convo ON marketplace_messages(conversation_id, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_msg_unread ON marketplace_messages(conversation_id, sender_id, read_at, id);

-- 37. Marketplace Offers
CREATE TABLE IF NOT EXISTS marketplace_offers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  conversation_id INTEGER NOT NULL,
  listing_id INTEGER NOT NULL,
  buyer_id INTEGER NOT NULL,
  seller_id INTEGER NOT NULL,
  created_by_id INTEGER NULL,
  parent_offer_id INTEGER NULL,
  amount NUMERIC NOT NULL DEFAULT 0,
  offer_amount NUMERIC NOT NULL DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'SYP',
  status TEXT NOT NULL DEFAULT 'pending',
  counter_amount NUMERIC NULL,
  last_action_by INTEGER NULL,
  responded_at TEXT NULL,
  responded_by_id INTEGER NULL,
  message_id INTEGER NULL,
  transaction_id INTEGER NULL,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_marketplace_offers_convo ON marketplace_offers(conversation_id, status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_offers_listing ON marketplace_offers(listing_id, status, id);

-- 38. Marketplace Transactions
CREATE TABLE IF NOT EXISTS marketplace_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  transaction_number TEXT NOT NULL UNIQUE,
  listing_id INTEGER NOT NULL,
  offer_id INTEGER NULL,
  buyer_id INTEGER NOT NULL,
  seller_id INTEGER NOT NULL,
  agreed_price NUMERIC NOT NULL DEFAULT 0,
  amount NUMERIC NOT NULL DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'SYP',
  status TEXT NOT NULL DEFAULT 'pending',
  payment_status TEXT NOT NULL DEFAULT 'unpaid',
  delivery_status TEXT NOT NULL DEFAULT 'not_required',
  payment_method TEXT NOT NULL DEFAULT 'cash_on_delivery',
  payment_reference TEXT NULL,
  delivery_option TEXT NOT NULL DEFAULT 'meetup',
  cancellation_reason TEXT NULL,
  completed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE RESTRICT,
  FOREIGN KEY (offer_id) REFERENCES marketplace_offers(id) ON DELETE RESTRICT,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_marketplace_tx_listing ON marketplace_transactions(listing_id, status, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_tx_buyer ON marketplace_transactions(buyer_id, status, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_tx_seller ON marketplace_transactions(seller_id, status, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_transactions_status ON marketplace_transactions(status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_transactions_payment ON marketplace_transactions(payment_status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_transactions_delivery ON marketplace_transactions(delivery_status, created_at, id);

-- 39. Delivery Tasks
CREATE TABLE IF NOT EXISTS delivery_tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NULL,
  marketplace_transaction_id INTEGER NULL UNIQUE,
  courier_id INTEGER NULL,
  delivery_fee NUMERIC NOT NULL,
  currency TEXT NOT NULL,
  pickup_location_text TEXT NULL,
  pickup_latitude NUMERIC NULL,
  pickup_longitude NUMERIC NULL,
  delivery_location_text TEXT NULL,
  delivery_latitude NUMERIC NULL,
  delivery_longitude NUMERIC NULL,
  package_information TEXT NULL,
  tracking_code TEXT NULL UNIQUE,
  status TEXT NOT NULL DEFAULT 'available',
  eta_value INTEGER NULL,
  eta_unit TEXT NULL,
  accepted_at TEXT NULL,
  pickup_proof_image_path TEXT NULL,
  delivery_proof_image_path TEXT NULL,
  courier_delivery_proof_image_path TEXT NULL,
  customer_proof_image_path TEXT NULL,
  proof_image_path TEXT NULL,
  proof_submitted_at TEXT NULL,
  reviewed_at TEXT NULL,
  reviewed_by INTEGER NULL,
  delivery_fee_settled_at TEXT NULL,
  delivery_fee_settled_by INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (marketplace_transaction_id) REFERENCES marketplace_transactions(id) ON DELETE RESTRICT,
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id),
  FOREIGN KEY (delivery_fee_settled_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_delivery_tasks_status ON delivery_tasks(status, created_at);
CREATE INDEX IF NOT EXISTS idx_delivery_tasks_courier ON delivery_tasks(courier_id, status);
CREATE INDEX IF NOT EXISTS idx_delivery_tasks_marketplace_status ON delivery_tasks(marketplace_transaction_id, status);
CREATE INDEX IF NOT EXISTS idx_delivery_fee_settlement ON delivery_tasks(delivery_fee_settled_at, courier_id, status);

-- 40. Delivery Task Rejections
CREATE TABLE IF NOT EXISTS delivery_task_rejections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  task_id INTEGER NOT NULL,
  courier_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (task_id, courier_id),
  FOREIGN KEY (task_id) REFERENCES delivery_tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 41. Merchant Delivery Fee Settlements
CREATE TABLE IF NOT EXISTS merchant_delivery_fee_settlements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  courier_id INTEGER NULL,
  merchant_id INTEGER NOT NULL,
  task_id INTEGER NULL,
  amount NUMERIC NOT NULL,
  currency TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'completed',
  settled_by INTEGER NULL,
  note TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES delivery_tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (settled_by) REFERENCES users(id)
);

-- 42. Wallets
CREATE TABLE IF NOT EXISTS wallets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  currency TEXT NOT NULL,
  available_balance NUMERIC NOT NULL DEFAULT 0,
  pending_balance NUMERIC NOT NULL DEFAULT 0,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, currency),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 43. Wallet Transactions
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  wallet_id INTEGER NOT NULL,
  transaction_type TEXT NOT NULL,
  direction TEXT NOT NULL,
  amount NUMERIC NOT NULL,
  balance_after NUMERIC NOT NULL,
  reference_type TEXT NOT NULL,
  reference_id INTEGER NOT NULL,
  description TEXT NOT NULL,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_wallet_tx_wallet ON wallet_transactions(wallet_id, created_at);

-- 44. Withdrawal Requests
CREATE TABLE IF NOT EXISTS withdrawal_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  wallet_id INTEGER NOT NULL,
  amount NUMERIC NOT NULL,
  payout_account_snapshot TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'requested',
  receipt_image_path TEXT NULL,
  admin_note TEXT NULL,
  reviewed_by INTEGER NULL,
  reviewed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wallet_id) REFERENCES wallets(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_withdrawal_wallet ON withdrawal_requests(wallet_id, status);

-- 45. Reviews
CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  customer_id INTEGER NOT NULL,
  order_id INTEGER NULL,
  rating INTEGER NOT NULL,
  comment TEXT NULL,
  status TEXT NOT NULL DEFAULT 'published',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id);

-- 46. Store Reviews
CREATE TABLE IF NOT EXISTS store_reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  store_id INTEGER NOT NULL,
  customer_id INTEGER NOT NULL,
  order_id INTEGER NOT NULL UNIQUE,
  rating INTEGER NOT NULL,
  comment TEXT NULL,
  status TEXT NOT NULL DEFAULT 'published',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_store_reviews_store ON store_reviews(store_id);

-- 47. Courier Reviews
CREATE TABLE IF NOT EXISTS courier_reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  courier_id INTEGER NOT NULL,
  customer_id INTEGER NOT NULL,
  order_id INTEGER NOT NULL UNIQUE,
  rating INTEGER NOT NULL,
  comment TEXT NULL,
  status TEXT NOT NULL DEFAULT 'published',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (courier_id) REFERENCES users(id),
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_courier_reviews_courier ON courier_reviews(courier_id, status, created_at);

-- 48. Support Tickets
CREATE TABLE IF NOT EXISTS support_tickets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  category TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  priority TEXT NOT NULL DEFAULT 'medium',
  assigned_admin_id INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_admin_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_tickets_user ON support_tickets(user_id, status);

-- 49. Support Messages
CREATE TABLE IF NOT EXISTS support_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_id INTEGER NOT NULL,
  sender_id INTEGER NOT NULL,
  body TEXT NULL,
  message TEXT NULL,
  attachment_path TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_support_messages_ticket ON support_messages(ticket_id, created_at);

-- 50. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  actor_id INTEGER NULL,
  action TEXT NOT NULL,
  entity_type TEXT NOT NULL,
  entity_id INTEGER NULL,
  before_json TEXT NULL,
  after_json TEXT NULL,
  ip_address TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actor_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_actor_created ON audit_logs(actor_id, created_at);

-- 51. Customer Delivery Addresses
CREATE TABLE IF NOT EXISTS customer_delivery_addresses (
  user_id INTEGER PRIMARY KEY,
  label TEXT NOT NULL DEFAULT '',
  address_text TEXT NOT NULL,
  latitude NUMERIC NULL,
  longitude NUMERIC NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 52. Notification Preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
  user_id INTEGER PRIMARY KEY,
  orders_enabled INTEGER NOT NULL DEFAULT 1,
  payments_enabled INTEGER NOT NULL DEFAULT 1,
  delivery_enabled INTEGER NOT NULL DEFAULT 1,
  verification_enabled INTEGER NOT NULL DEFAULT 1,
  wallet_enabled INTEGER NOT NULL DEFAULT 1,
  marketplace_enabled INTEGER NOT NULL DEFAULT 1,
  support_enabled INTEGER NOT NULL DEFAULT 1,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 53. Admin Notification Reads
CREATE TABLE IF NOT EXISTS admin_notification_reads (
  admin_id INTEGER NOT NULL,
  section_key TEXT NOT NULL,
  read_at TEXT NOT NULL,
  PRIMARY KEY (admin_id, section_key),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 54. Marketplace User Blocks
CREATE TABLE IF NOT EXISTS marketplace_user_blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  blocker_id INTEGER NOT NULL,
  blocked_id INTEGER NOT NULL,
  reason TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (blocker_id, blocked_id),
  FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 55. Marketplace Reports
CREATE TABLE IF NOT EXISTS marketplace_reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reporter_id INTEGER NOT NULL,
  target_type TEXT NOT NULL DEFAULT 'user',
  target_user_id INTEGER NOT NULL,
  listing_id INTEGER NULL,
  conversation_id INTEGER NULL,
  message_id INTEGER NULL,
  reason TEXT NULL,
  reason_category TEXT NULL,
  details TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  reviewed_by INTEGER NULL,
  reviewed_at TEXT NULL,
  resolution_note TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_marketplace_reports_target_listing ON marketplace_reports(listing_id, status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_reports_target_user ON marketplace_reports(target_user_id, status, created_at, id);

-- 56. Marketplace Typing Status
CREATE TABLE IF NOT EXISTS marketplace_typing_status (
  conversation_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  expires_at TEXT NOT NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (conversation_id, user_id),
  FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 57. Favorites Marketplace
CREATE TABLE IF NOT EXISTS favorites_marketplace (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  listing_id INTEGER NOT NULL,
  listing_title_snapshot TEXT NOT NULL,
  image_path_snapshot TEXT NULL,
  price_snapshot NUMERIC NOT NULL,
  currency_snapshot TEXT NOT NULL,
  last_known_price NUMERIC NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, listing_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_favorites_marketplace_user ON favorites_marketplace(user_id, updated_at, listing_id);

-- 58. Marketplace Alert Rules
CREATE TABLE IF NOT EXISTS marketplace_alert_rules (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  rule_type TEXT NOT NULL,
  filters_json TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  last_checked_at TEXT NULL,
  listing_id INTEGER NULL,
  category_id INTEGER NULL,
  keyword TEXT NULL,
  min_price NUMERIC NULL,
  max_price NUMERIC NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories_marketplace(id) ON DELETE SET NULL
);

-- 59. Marketplace Alert Events
CREATE TABLE IF NOT EXISTS marketplace_alert_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  listing_id INTEGER NOT NULL,
  rule_id INTEGER NULL,
  event_type TEXT NOT NULL,
  event_key TEXT NOT NULL UNIQUE,
  payload_json TEXT NULL,
  occurred_at TEXT NULL,
  delivered_at TEXT NULL,
  is_read INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (rule_id) REFERENCES marketplace_alert_rules(id) ON DELETE SET NULL
);

-- 60. Marketplace Promotion Requests
CREATE TABLE IF NOT EXISTS marketplace_promotion_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  user_id INTEGER NULL,
  requested_by_id INTEGER NULL,
  promotion_type TEXT NOT NULL DEFAULT 'featured',
  duration_days INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  note TEXT NULL,
  reviewed_by_id INTEGER NULL,
  reviewed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE
);

-- 61. Marketplace Notifications
CREATE TABLE IF NOT EXISTS marketplace_notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  notification_type TEXT NOT NULL,
  title TEXT NOT NULL,
  body TEXT NOT NULL,
  event_key TEXT NULL UNIQUE,
  listing_id INTEGER NULL,
  conversation_id INTEGER NULL,
  offer_id INTEGER NULL,
  transaction_id INTEGER NULL,
  delivery_task_id INTEGER NULL,
  review_id INTEGER NULL,
  payload_json TEXT NULL,
  is_read INTEGER NOT NULL DEFAULT 0,
  read_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE SET NULL,
  FOREIGN KEY (conversation_id) REFERENCES marketplace_conversations(id) ON DELETE SET NULL,
  FOREIGN KEY (offer_id) REFERENCES marketplace_offers(id) ON DELETE SET NULL,
  FOREIGN KEY (transaction_id) REFERENCES marketplace_transactions(id) ON DELETE SET NULL,
  FOREIGN KEY (delivery_task_id) REFERENCES delivery_tasks(id) ON DELETE SET NULL,
  FOREIGN KEY (review_id) REFERENCES marketplace_transaction_reviews(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_marketplace_notifications_user ON marketplace_notifications(user_id, is_read, created_at, id);

-- 62. Marketplace Promotion Prices
CREATE TABLE IF NOT EXISTS marketplace_promotion_prices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  promotion_type TEXT NOT NULL,
  duration_days INTEGER NOT NULL,
  price NUMERIC NOT NULL,
  currency TEXT NOT NULL DEFAULT 'SYP',
  status TEXT NOT NULL DEFAULT 'active',
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (promotion_type, duration_days, currency)
);

-- 63. Marketplace Promotions
CREATE TABLE IF NOT EXISTS marketplace_promotions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  promotion_type TEXT NOT NULL,
  duration_days INTEGER NOT NULL,
  start_at TEXT NOT NULL,
  end_at TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  payment_id TEXT NULL,
  price_id INTEGER NULL,
  price_amount NUMERIC NULL,
  price_currency TEXT NULL,
  note TEXT NULL,
  reviewed_by_id INTEGER NULL,
  reviewed_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (reviewed_by_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_marketplace_promotions_active ON marketplace_promotions(listing_id, promotion_type, status, end_at);

-- 64. Marketplace Transaction Reviews
CREATE TABLE IF NOT EXISTS marketplace_transaction_reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  transaction_id INTEGER NOT NULL UNIQUE,
  reviewer_id INTEGER NOT NULL,
  reviewed_user_id INTEGER NULL,
  reviewee_id INTEGER NULL,
  reviewer_role TEXT NULL,
  rating INTEGER NOT NULL,
  comment TEXT NULL,
  status TEXT NOT NULL DEFAULT 'published',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transaction_id) REFERENCES marketplace_transactions(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_marketplace_reviews_user ON marketplace_transaction_reviews(reviewer_id, status, created_at, id);

-- 65. Marketplace Screens
CREATE TABLE IF NOT EXISTS marketplace_screens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  screen_code TEXT NULL UNIQUE,
  screen_key TEXT NULL UNIQUE,
  title TEXT NOT NULL,
  screen_name TEXT NULL,
  description TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  display_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 66. Marketplace Promotion Screens
CREATE TABLE IF NOT EXISTS marketplace_promotion_screens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  promotion_id INTEGER NOT NULL,
  screen_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (promotion_id, screen_id),
  FOREIGN KEY (promotion_id) REFERENCES marketplace_promotions(id) ON DELETE CASCADE,
  FOREIGN KEY (screen_id) REFERENCES marketplace_screens(id) ON DELETE RESTRICT
);

-- 67. Marketplace Comments
CREATE TABLE IF NOT EXISTS marketplace_comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  listing_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  parent_id INTEGER NULL,
  body TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'visible',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (parent_id) REFERENCES marketplace_comments(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_marketplace_comments_listing ON marketplace_comments(listing_id, status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_marketplace_comments_user ON marketplace_comments(user_id, created_at, id);

-- 68. Marketplace Follows
CREATE TABLE IF NOT EXISTS marketplace_follows (
  follower_id INTEGER NOT NULL,
  followed_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, followed_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_marketplace_follows_followed ON marketplace_follows(followed_id, created_at, follower_id);

-- Initial Base Data
INSERT OR IGNORE INTO marketplace_settings (setting_key, value_text) VALUES ('home_title', 'الحراج');

-- Countries Reference Data
INSERT OR IGNORE INTO marketplace_locations (id, parent_id, location_type, name, code, display_order, status) VALUES
  (1, NULL, 'country', 'الأردن', 'JO', 10, 'active'),
  (2, NULL, 'country', 'الإمارات العربية المتحدة', 'AE', 20, 'active'),
  (3, NULL, 'country', 'البحرين', 'BH', 30, 'active'),
  (4, NULL, 'country', 'الجزائر', 'DZ', 40, 'active'),
  (5, NULL, 'country', 'جيبوتي', 'DJ', 50, 'active'),
  (6, NULL, 'country', 'جزر القمر', 'KM', 60, 'active'),
  (7, NULL, 'country', 'السعودية', 'SA', 70, 'active'),
  (8, NULL, 'country', 'السودان', 'SD', 80, 'active'),
  (9, NULL, 'country', 'سوريا', 'SY', 90, 'active'),
  (10, NULL, 'country', 'الصومال', 'SO', 100, 'active'),
  (11, NULL, 'country', 'العراق', 'IQ', 110, 'active'),
  (12, NULL, 'country', 'عُمان', 'OM', 120, 'active'),
  (13, NULL, 'country', 'فلسطين', 'PS', 130, 'active'),
  (14, NULL, 'country', 'قطر', 'QA', 140, 'active'),
  (15, NULL, 'country', 'الكويت', 'KW', 150, 'active'),
  (16, NULL, 'country', 'لبنان', 'LB', 160, 'active'),
  (17, NULL, 'country', 'ليبيا', 'LY', 170, 'active'),
  (18, NULL, 'country', 'مصر', 'EG', 180, 'active'),
  (19, NULL, 'country', 'المغرب', 'MA', 190, 'active'),
  (20, NULL, 'country', 'موريتانيا', 'MR', 200, 'active'),
  (21, NULL, 'country', 'اليمن', 'YE', 210, 'active'),
  (22, NULL, 'country', 'تونس', 'TN', 220, 'active');
