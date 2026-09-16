-- منصة تجارتي: حقول إعلانات الخدمات
-- ترحيل غير هدّام؛ ينفذ مرة واحدة بعد ترحيلات الحراج الأساسية.
ALTER TABLE listings_marketplace
  ADD COLUMN IF NOT EXISTS service_available_from TIME NULL AFTER longitude,
  ADD COLUMN IF NOT EXISTS service_available_to TIME NULL AFTER service_available_from,
  ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(32) NULL AFTER service_available_to;
