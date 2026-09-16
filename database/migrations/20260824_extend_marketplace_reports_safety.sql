-- Marketplace Reports & Safety extension.
-- Apply locally only after 20260824_add_marketplace_chat.sql.
-- Do not delete or hide listings automatically from this migration or its API.

ALTER TABLE marketplace_reports
  ADD COLUMN target_type ENUM('listing','user') NOT NULL DEFAULT 'user' AFTER reporter_id,
  ADD COLUMN resolution_note VARCHAR(1000) NULL AFTER reviewed_at,
  MODIFY COLUMN status ENUM('open','reviewing','under_review','resolved','dismissed','rejected') NOT NULL DEFAULT 'open',
  ADD INDEX idx_marketplace_reports_target_listing (listing_id, status, created_at, id),
  ADD INDEX idx_marketplace_reports_target_user (target_user_id, status, created_at, id);

UPDATE marketplace_reports
SET target_type = CASE WHEN listing_id IS NULL THEN 'user' ELSE 'listing' END,
    status = CASE
      WHEN status = 'reviewing' THEN 'under_review'
      WHEN status = 'dismissed' THEN 'rejected'
      ELSE status
    END;

ALTER TABLE marketplace_reports
  MODIFY COLUMN status ENUM('open','under_review','resolved','rejected') NOT NULL DEFAULT 'open';
