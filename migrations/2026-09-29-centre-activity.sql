-- Learning-centre activity synced from Kolibri.
--
-- Kolibri runs on a small server at the centre in Accra, on the local network,
-- with no internet required. This table holds what that server reports back:
-- one row per learner per day. Day totals are derived by query, so a window
-- can be re-synced any number of times without double counting.
--
-- Run once. See docs/KOLIBRI_CENTRE_SETUP.md.

CREATE TABLE IF NOT EXISTS centre_activity (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_date DATE NOT NULL,
    -- Kolibri usernames allow letters, numbers and underscores only, so an
    -- academy slug such as "ama-k9" is carried here as "ama_k9". The learner
    -- is matched back on that transform; a username with no academy account
    -- still counts towards centre totals.
    kolibri_username VARCHAR(60) NOT NULL,
    learner_id INT UNSIGNED NULL,
    sessions SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    device VARCHAR(60) NOT NULL DEFAULT '',
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_centre_day_user (activity_date, kolibri_username),
    CONSTRAINT fk_centre_learner FOREIGN KEY (learner_id)
        REFERENCES learners(id) ON DELETE SET NULL,
    INDEX idx_centre_date (activity_date),
    INDEX idx_centre_learner (learner_id, activity_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A record of each sync, so staff can see the centre is still reporting and
-- notice when it has gone quiet.
CREATE TABLE IF NOT EXISTS centre_syncs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device VARCHAR(60) NOT NULL DEFAULT '',
    facility VARCHAR(120) NOT NULL DEFAULT '',
    window_from DATE NULL,
    window_to DATE NULL,
    rows_received SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    rows_matched SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    method ENUM('http','upload') NOT NULL DEFAULT 'http',
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sync_user FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sync_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
