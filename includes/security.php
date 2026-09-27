<?php
/**
 * Security utilities: HTTPS enforcement, rate limiting, email verification
 */

// Enforce HTTPS in production
if (!empty($_SERVER['HTTP_HOST']) && empty($_SERVER['HTTPS'])) {
    // Don't redirect on localhost or if already on HTTPS
    if (getenv('APP_ENV') === 'production' || !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'])) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}

/**
 * Rate limiting: track attempts by IP and action
 * Usage: rate_limit('login', $_SERVER['REMOTE_ADDR'], 5, 300) — max 5 per 5 minutes
 */
function rate_limit(string $action, string $identifier, int $max_attempts, int $window_seconds): bool {
    $file = sys_get_temp_dir() . '/ratelimit_' . hash('sha256', $action . $identifier) . '.json';

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
        $now = time();

        // Clean old attempts
        $data = array_filter($data, fn($t) => $now - $t < $window_seconds);

        if (count($data) >= $max_attempts) {
            return false; // Rate limited
        }

        $data[] = $now;
    } else {
        $data = [time()];
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
    return true; // Allowed
}

/**
 * Email verification token generation
 */
function generate_email_token(string $email): string {
    return bin2hex(random_bytes(32));
}

/**
 * Verify email token (check token and expiry — max 24 hours)
 * Table schema expected: CREATE TABLE email_verifications (
 *   id INT PRIMARY KEY AUTO_INCREMENT,
 *   user_id INT NOT NULL,
 *   token VARCHAR(64) NOT NULL UNIQUE,
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   verified_at TIMESTAMP NULL,
 *   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
 * );
 */
function verify_email_token(string $token): ?int {
    require_once __DIR__ . '/db.php';
    $stmt = db()->prepare(
        'SELECT user_id FROM email_verifications
         WHERE token = ? AND verified_at IS NULL
         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $result = $stmt->fetchColumn();

    if ($result) {
        // Mark as verified
        $update = db()->prepare(
            'UPDATE email_verifications SET verified_at = NOW() WHERE token = ?'
        );
        $update->execute([$token]);
    }

    return $result ? (int)$result : null;
}

/**
 * Check if user's email is verified
 */
function is_email_verified(int $user_id): bool {
    require_once __DIR__ . '/db.php';
    $stmt = db()->prepare(
        'SELECT 1 FROM email_verifications
         WHERE user_id = ? AND verified_at IS NOT NULL LIMIT 1'
    );
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Webhook signature verification (generic HMAC-SHA256)
 * Usage: verify_webhook_signature($_POST, 'your-webhook-secret', $_SERVER['HTTP_X_SIGNATURE'] ?? '')
 */
function verify_webhook_signature(array $payload, string $secret, string $signature): bool {
    $body = json_encode($payload);
    $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
    return hash_equals($expected, $signature);
}

/**
 * Add security headers
 */
function add_security_headers(): void {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // CSP: allow self + trusted CDNs for fonts/styles
        header('Content-Security-Policy: default-src \'self\'; style-src \'self\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; script-src \'self\'; frame-ancestors \'self\'');
    }
}
