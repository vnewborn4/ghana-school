<?php
/**
 * Stripe webhook handler
 * Validates Stripe webhook signatures and updates sponsorship status on successful payment
 *
 * Events handled:
 * - payment_intent.succeeded: Donation completed, activate sponsorship
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// Get webhook secret from environment
$webhook_secret = getenv('STRIPE_WEBHOOK_SECRET');
if (!$webhook_secret) {
    http_response_code(500);
    exit('STRIPE_WEBHOOK_SECRET not configured');
}

// Get raw body for signature verification (Stripe requires raw body)
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify Stripe signature
// Stripe uses: timestamp.payload signature = HMAC-SHA256(timestamp.payload, secret)
$verified = false;
if (!empty($signature)) {
    $parts = explode(',', $signature);
    $timestamp = '';
    $sig = '';
    foreach ($parts as $part) {
        if (strpos($part, 't=') === 0) $timestamp = substr($part, 2);
        if (strpos($part, 'v1=') === 0) $sig = substr($part, 3);
    }

    $signed_content = "$timestamp.$body";
    $expected_sig = hash_hmac('sha256', $signed_content, $webhook_secret);
    $verified = hash_equals($expected_sig, $sig);
}

if (!$verified) {
    http_response_code(403);
    exit('Invalid signature');
}

$payload = json_decode($body, true);
if (!$payload) {
    http_response_code(400);
    exit('Invalid JSON');
}

$event_type = $payload['type'] ?? '';
$event_data = $payload['data']['object'] ?? [];

try {
    $pdo = db();

    if ($event_type === 'payment_intent.succeeded') {
        $payment_id = $event_data['id'] ?? '';
        $amount = ($event_data['amount'] ?? 0) / 100; // Stripe uses cents
        $metadata = $event_data['metadata'] ?? [];
        $sponsorship_id = (int)($metadata['sponsorship_id'] ?? 0);

        if ($payment_id && $sponsorship_id > 0) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE sponsorships
                 SET status = ?, payment_id = ?, paid_at = NOW()
                 WHERE id = ? AND status = ? AND payment_provider = ?'
            );
            $stmt->execute(['active', $payment_id, $sponsorship_id, 'pending', 'stripe']);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                http_response_code(200);
                exit('Sponsorship activated');
            }

            $pdo->rollBack();
        }
    }

    http_response_code(200);
    exit('Event processed');

} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error: ' . $e->getMessage());
}
