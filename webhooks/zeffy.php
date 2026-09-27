<?php
/**
 * Zeffy webhook handler
 * Validates Zeffy webhook signatures and updates sponsorship status on successful donation
 *
 * Expected payload:
 * {
 *   "event": "donation.success",
 *   "donation": {
 *     "id": "...",
 *     "amount": 50.00,
 *     "currency": "USD",
 *     "custom_fields": [
 *       { "name": "Student journey first name or code", "value": "xyz123" }
 *     ]
 *   }
 * }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// Get webhook secret from environment
$webhook_secret = getenv('ZEFFY_WEBHOOK_SECRET');
if (!$webhook_secret) {
    http_response_code(500);
    exit('ZEFFY_WEBHOOK_SECRET not configured');
}

// Read request body
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ZEFFY_SIGNATURE'] ?? '';

// Verify signature (Zeffy uses HMAC-SHA256)
if (!verify_webhook_signature(json_decode($body, true) ?? [], $webhook_secret, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

$payload = json_decode($body, true);
if (!$payload) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Only handle donation.success events
if (($payload['event'] ?? '') !== 'donation.success') {
    http_response_code(200);
    exit('Ignored: not a donation.success event');
}

$donation = $payload['donation'] ?? [];
$amount = (float)($donation['amount'] ?? 0);
$donation_id = $donation['id'] ?? '';

if (!$donation_id || $amount <= 0) {
    http_response_code(400);
    exit('Missing required donation fields');
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Find pending sponsorship for this student
    // Extract student code/journey identifier from custom fields
    $student_ref = '';
    foreach (($donation['custom_fields'] ?? []) as $field) {
        if (stripos($field['name'] ?? '', 'student') !== false || stripos($field['name'] ?? '', 'journey') !== false) {
            $student_ref = $field['value'] ?? '';
            break;
        }
    }

    if ($student_ref) {
        // Update matching pending sponsorship
        $stmt = $pdo->prepare(
            'UPDATE sponsorships s
             JOIN student_journeys j ON s.student_journey_id = j.id
             SET s.status = ?, s.payment_id = ?, s.paid_at = NOW()
             WHERE j.public_code = ? AND s.status = ? AND s.payment_provider = ?
             LIMIT 1'
        );
        $stmt->execute(['active', $donation_id, $student_ref, 'pending', 'zeffy']);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            http_response_code(200);
            exit('Sponsorship activated');
        }
    }

    $pdo->commit();
    http_response_code(200);
    exit('No matching pending sponsorship found');

} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error: ' . $e->getMessage());
}
