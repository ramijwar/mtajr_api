CREATE TABLE IF NOT EXISTS delivery_task_rejections (
  task_id BIGINT UNSIGNED NOT NULL,
  courier_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (task_id, courier_id),
  INDEX idx_delivery_task_rejections_courier (courier_id),
  CONSTRAINT fk_delivery_task_rejections_task
    FOREIGN KEY (task_id) REFERENCES delivery_tasks(id),
  CONSTRAINT fk_delivery_task_rejections_courier
    FOREIGN KEY (courier_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
