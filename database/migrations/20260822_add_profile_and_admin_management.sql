-- SouqLink: profile editing permission and administrative management support.
-- Run once, after the existing verification and financial migrations.
ALTER TABLE users
  ADD COLUMN profile_update_permission ENUM('allowed','locked') NOT NULL DEFAULT 'allowed' AFTER status,
  ADD INDEX idx_users_profile_update_permission (profile_update_permission);

UPDATE users u
JOIN verification_requests vr ON vr.user_id = u.id AND vr.subject_role = u.role AND vr.status = 'approved'
SET u.profile_update_permission = 'locked';
