-- Independent Marketplace transaction lifecycle.
-- Apply after 20260824_add_marketplace_offers_and_transactions.sql.
-- Listing status remains a visibility/reservation concern only; transaction state is authoritative for the deal.

ALTER TABLE marketplace_transactions
  ADD COLUMN agreed_price DECIMAL(14,2) NULL AFTER offer_id,
  ADD COLUMN payment_status ENUM('unpaid','waiting_payment','paid','refunded','cancelled') NOT NULL DEFAULT 'unpaid' AFTER status,
  ADD COLUMN delivery_status ENUM('not_required','waiting_delivery','delivering','delivered','cancelled') NOT NULL DEFAULT 'not_required' AFTER payment_status,
  ADD COLUMN completed_at DATETIME NULL AFTER created_at;

UPDATE marketplace_transactions
SET agreed_price = amount
WHERE agreed_price IS NULL;

UPDATE marketplace_transactions
SET status = CASE status
  WHEN 'payment_pending' THEN 'waiting_payment'
  WHEN 'payment_review' THEN 'waiting_payment'
  WHEN 'expired' THEN 'cancelled'
  ELSE status
END;

ALTER TABLE marketplace_transactions
  MODIFY agreed_price DECIMAL(14,2) NOT NULL,
  MODIFY status ENUM('pending','confirmed','waiting_payment','paid','waiting_delivery','delivering','completed','cancelled','disputed') NOT NULL DEFAULT 'pending',
  ADD INDEX idx_marketplace_transactions_status (status, created_at, id),
  ADD INDEX idx_marketplace_transactions_payment (payment_status, created_at, id),
  ADD INDEX idx_marketplace_transactions_delivery (delivery_status, created_at, id);
