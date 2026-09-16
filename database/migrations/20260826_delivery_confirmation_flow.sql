-- Delivery confirmation flow migration.
-- Safe to run after selecting the SouqLink database; no rows are deleted.
ALTER TABLE delivery_tasks
  MODIFY status ENUM('available','accepted','rejected','picked_up','in_transit','delivered','proof_submitted','approved','disputed') NOT NULL DEFAULT 'available';

-- Legacy proof_submitted means the courier already supplied the pickup proof.
-- It now waits for the customer's final confirmation.
UPDATE delivery_tasks SET status = 'in_transit' WHERE status = 'proof_submitted';

-- Legacy approved tasks were already completed by the previous flow.
UPDATE delivery_tasks SET status = 'delivered' WHERE status = 'approved';
