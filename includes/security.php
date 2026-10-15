<?php
/**
 * Security utilities: HTTPS enforcement, rate limiting, email verification
 */

// Enforce HTTPS only when explicitly enabled and SSL certificate is verified
if (!empty($_SERVER['HTTP_HOST']) && empty($_SERVER['HTTPS']) && getenv('ENFORCE_HTTPS') === 'true') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

/**
 * Rate limiting: track attempts by IP and action
 * Usage: rate_limit('login', $_SERVER['REMOTE_ADDR'], 5, 300) — max 5 per 5 minutes
 */
function rate_limit($action, $identifier, $max_attempts, $window_seconds) {
    $file = sys_get_temp_dir() . '/ratelimit_' . hash('sha256', $action . $identifier) . '.json';

    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode($raw, true);
        $data = is_array($decoded) ? $decoded : array();
        $now = time();

        // Clean old attempts
        $data = array_filter($data, function($t) use ($now, $window_seconds) {
            return ($now - $t) < $window_seconds;
        });

        if (count($data) >= $max_attempts) {
            return false; // Rate limited
        }

        $data[] = $now;
    } else {
        $data = array(time());
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
    return true; // Allowed
}

/**
 * Email verification token generation
 */
function generate_email_token($email) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
    return md5(uniqid(mt_rand(), true));
}

/**
 * Verify email token (check token and expiry — max 24 hours)
 */
function verify_email_token($token) {
    require_once __DIR__ . '/db.php';
    $stmt = db()->prepare(
        'SELECT user_id FROM email_verifications
         WHERE token = ? AND verified_at IS NULL
         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         LIMIT 1'
    );
    $stmt->execute(array($token));
    $result = $stmt->fetchColumn();

    if ($result) {
        // Mark as verified
        $update = db()->prepare(
            'UPDATE email_verifications SET verified_at = NOW() WHERE token = ?'
        );
        $update->execute(array($token));
    }

    return $result ? (int)$result : null;
}

/**
 * Check if user's email is verified
 */
function is_email_verified($user_id) {
    require_once __DIR__ . '/db.php';
    $stmt = db()->prepare(
        'SELECT 1 FROM email_verifications
         WHERE user_id = ? AND verified_at IS NOT NULL LIMIT 1'
    );
    $stmt->execute(array($user_id));
    return (bool)$stmt->fetchColumn();
}

/**
 * Webhook signature verification (generic HMAC-SHA256)
 */
function verify_webhook_signature($payload, $secret, $signature) {
    $body = json_encode($payload);
    $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
    return hash_equals($expected, $signature);
}

/**
 * Add security headers
 */
function add_security_headers() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // CSP: allow self + trusted CDNs for fonts/styles
        header('Content-Security-Policy: default-src \'self\'; style-src \'self\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; script-src \'self\'; frame-ancestors \'self\'');
    }
}
