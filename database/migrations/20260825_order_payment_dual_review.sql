-- دورة موافقة مزدوجة لسندات الطلبات العادية: التاجر يراجع أولاً ثم الإدارة تعتمد أو ترفض.
ALTER TABLE payment_receipts
  ADD COLUMN merchant_review_status ENUM('pending','acknowledged','issue_reported') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN merchant_review_note VARCHAR(500) NULL AFTER merchant_review_status,
  ADD COLUMN merchant_reviewed_at DATETIME NULL AFTER merchant_review_note,
  ADD INDEX idx_payment_merchant_review (merchant_review_status, status, created_at);

-- السجلات القديمة التي اعتمدتها الإدارة سابقاً لا تعود معلقة لدى التاجر.
UPDATE payment_receipts
SET merchant_review_status = CASE WHEN status = 'verified' THEN 'acknowledged' ELSE merchant_review_status END;
