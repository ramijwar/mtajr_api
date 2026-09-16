-- Apply after selecting the existing SouqLink database in phpMyAdmin.
ALTER TABLE stores ADD COLUMN is_verified BOOLEAN NOT NULL DEFAULT FALSE AFTER status;

CREATE TABLE verification_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  subject_role ENUM('merchant','courier') NOT NULL,
  identity_front_path VARCHAR(255) NOT NULL,
  portrait_path VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  review_note VARCHAR(500) NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_verification_user_role (user_id, subject_role),
  INDEX idx_verification_status_created (status, created_at),
  CONSTRAINT fk_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_verification_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE products ADD COLUMN sales_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_count;

CREATE TABLE advertisements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  body VARCHAR(500) NULL,
  image_path VARCHAR(255) NOT NULL,
  size_preset ENUM('wide','medium','compact') NOT NULL DEFAULT 'wide',
  target_type ENUM('none','store','product','url') NOT NULL DEFAULT 'none',
  target_value VARCHAR(500) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ads_visible (status, starts_at, ends_at, display_order),
  CONSTRAINT fk_ads_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;
