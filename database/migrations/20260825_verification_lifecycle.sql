-- إضافة دورة حياة واضحة للتوثيق: approved فعال، أو approved موقوف، مع إبقاء السجل والصور للمراجعة.
ALTER TABLE verification_requests
  ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE AFTER status,
  ADD INDEX idx_verification_active (subject_role, status, is_active, updated_at);

UPDATE verification_requests
SET is_active = CASE WHEN status = 'approved' THEN TRUE ELSE FALSE END;
