-- فصل صورة تسليم عامل التوصيل عن صورة إثبات الزبون الاختيارية.
-- الترحيل آمن ولا يحذف أي بيانات موجودة.
ALTER TABLE delivery_tasks
  ADD COLUMN courier_delivery_proof_image_path VARCHAR(255) NULL AFTER delivery_proof_image_path,
  ADD COLUMN customer_proof_image_path VARCHAR(255) NULL AFTER courier_delivery_proof_image_path;

-- البيانات القديمة في delivery_proof_image_path كانت تُحفظ كتأكيد نهائي للزبون؛
-- نحتفظ بها كمرجع للعرض القديم وننسخها إلى الحقل الجديد عند الحاجة.
UPDATE delivery_tasks
SET customer_proof_image_path = delivery_proof_image_path
WHERE customer_proof_image_path IS NULL
  AND delivery_proof_image_path IS NOT NULL
  AND delivery_proof_image_path <> '';
