<?php
/**
 * Receives a signed activity payload from the centre's Kolibri server.
 *
 * Used when the centre has a working connection. When it does not, the same
 * file is carried on a USB stick and uploaded in the teacher portal — see
 * teach/centre.php. Both routes share includes/centre_activity.php, so the
 * offline path is not second class.
 *
 * Authentication is an HMAC-SHA256 signature over the raw request body, using
 * a secret shared with the centre server. There is no fallback: if the secret
 * is not configured, this endpoint refuses everything rather than accepting
 * unsigned data.
 *
 * Set on the web server:  KOLIBRI_SYNC_SECRET=<a long random string>
 * Set on the centre Pi:   the same value, in /etc/kolibri-sync.env
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/centre_activity.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

/** Replay window. A Raspberry Pi has no battery-backed clock, so a centre
 *  that has been offline can come back with the wrong time; this is
 *  deliberately generous, and re-sending a window is harmless anyway because
 *  rows are keyed on (date, username). */
const SYNC_MAX_AGE_SECONDS = 604800;   // 7 days

function sync_fail(int $code, string $message): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    sync_fail(405, 'Use POST.');
}

$secret = (string)(getenv('KOLIBRI_SYNC_SECRET') ?: '');
if ($secret === '') {
    // Never accept unsigned data because the server is misconfigured.
    sync_fail(503, 'Sync is not configured on this server.');
}

if (!rate_limit('kolibri_sync', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 30, 3600)) {
    sync_fail(429, 'Too many sync attempts. Try again later.');
}

$length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($length > CENTRE_PAYLOAD_MAX_BYTES) {
    sync_fail(413, 'That payload is too large. Sync a shorter window.');
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') sync_fail(400, 'Empty request body.');
if (strlen($raw) > CENTRE_PAYLOAD_MAX_BYTES) sync_fail(413, 'That payload is too large.');

if (!verify_signature_raw($raw, $secret, (string)($_SERVER['HTTP_X_SIGNATURE'] ?? ''))) {
    sync_fail(401, 'Bad signature.');
}

$payload = json_decode($raw, true);
if (!is_array($payload)) sync_fail(400, 'The body is not valid JSON.');

$problem = centre_validate_payload($payload);
if ($problem !== null) sync_fail(422, $problem);

$generated = strtotime((string)($payload['generated_at'] ?? ''));
if ($generated === false) sync_fail(422, 'generated_at is missing or unreadable.');
if (abs(time() - $generated) > SYNC_MAX_AGE_SECONDS) {
    sync_fail(422, 'That payload is too old or dated in the future. Check the clock on the centre server.');
}

try {
    $result = centre_ingest($payload, 'http');
} catch (Throwable $e) {
    error_log('kolibri_sync: ' . $e->getMessage());
    sync_fail(500, 'The sync could not be recorded.');
}

echo json_encode([
    'ok'       => true,
    'received' => $result['received'],
    'matched'  => $result['matched'],
]);
