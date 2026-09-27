-- Sponsor bridge: reviewed classroom work becomes a draft sponsor update,
-- which an administrator edits and approves before any sponsor sees it.
--
-- Nothing here changes what sponsors currently see. Existing updates are
-- marked 'approved' so they stay visible, and the existing admin flow keeps
-- working unchanged because 'approved' is the column default.
--
-- Run once. See docs/ACADEMY_INTEGRATION.md section 10.

ALTER TABLE student_updates
  -- draft   : generated from classroom work, waiting for an administrator
  -- approved: reviewed and visible to the sponsor
  -- withdrawn: taken down, kept for the record
  ADD COLUMN IF NOT EXISTS status ENUM('draft','approved','withdrawn')
      NOT NULL DEFAULT 'approved',
  -- What the draft was generated from. Unique, so a piece of work can never
  -- produce two updates. NULL for updates written by hand.
  ADD COLUMN IF NOT EXISTS source_submission_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS source_site_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS created_by_user_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS approved_by_user_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Bring rows that predate this migration into line with the new column.
UPDATE student_updates SET status = 'withdrawn' WHERE visible = 0 AND status = 'approved';

ALTER TABLE student_updates
  ADD UNIQUE INDEX IF NOT EXISTS uq_update_submission (source_submission_id),
  ADD UNIQUE INDEX IF NOT EXISTS uq_update_site (source_site_id),
  ADD INDEX IF NOT EXISTS idx_update_status (status);

-- Foreign keys are added separately: ADD CONSTRAINT has no IF NOT EXISTS in
-- MySQL, so a second run of this file would fail on a duplicate name.
-- Each is guarded by a lookup in information_schema instead.
SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'student_updates'
              AND CONSTRAINT_NAME = 'fk_update_submission');
SET @sql := IF(@fk = 0,
  'ALTER TABLE student_updates ADD CONSTRAINT fk_update_submission
     FOREIGN KEY (source_submission_id) REFERENCES submissions(id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'student_updates'
              AND CONSTRAINT_NAME = 'fk_update_site');
SET @sql := IF(@fk = 0,
  'ALTER TABLE student_updates ADD CONSTRAINT fk_update_site
     FOREIGN KEY (source_site_id) REFERENCES student_sites(id) ON DELETE SET NULL',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Track that a piece of work has already produced a draft, so withdrawing an
-- update does not cause it to be regenerated on the next review.
ALTER TABLE submissions
  ADD COLUMN IF NOT EXISTS bridged_at DATETIME NULL;
ALTER TABLE student_sites
  ADD COLUMN IF NOT EXISTS bridged_at DATETIME NULL;
