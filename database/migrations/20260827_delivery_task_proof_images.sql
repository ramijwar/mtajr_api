-- فصل صور مهمة التوصيل إلى صورة استلام وصورة تسليم دون حذف الصورة القديمة.
ALTER TABLE delivery_tasks
  ADD COLUMN pickup_proof_image_path VARCHAR(255) NULL AFTER accepted_at,
  ADD COLUMN delivery_proof_image_path VARCHAR(255) NULL AFTER pickup_proof_image_path;

UPDATE delivery_tasks
SET pickup_proof_image_path = proof_image_path
WHERE pickup_proof_image_path IS NULL AND proof_image_path IS NOT NULL;
