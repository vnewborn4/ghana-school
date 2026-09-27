-- Email verification system for production launch
-- Run this migration once on the production database

CREATE TABLE IF NOT EXISTS email_verifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_verified (verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add email_verified column to users table if not present
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE;

-- Add index for fast lookups of unverified emails
CREATE INDEX IF NOT EXISTS idx_email_verified ON users(email_verified);
