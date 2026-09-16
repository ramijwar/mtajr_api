CREATE TABLE IF NOT EXISTS admin_notification_reads (
  admin_id BIGINT UNSIGNED NOT NULL,
  section_key VARCHAR(80) NOT NULL,
  read_at DATETIME NOT NULL,
  PRIMARY KEY (admin_id, section_key),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
