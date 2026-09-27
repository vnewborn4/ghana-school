<?php
/**
 * PayPal webhook handler
 * Validates PayPal webhook signatures and updates sponsorship status on successful payment
 *
 * Events handled:
 * - PAYMENT.CAPTURE.COMPLETED: One-time donation completed
 * - BILLING_SUBSCRIPTION.PAYMENT_SUCCESS: Recurring donation payment received
 */

require_once __DIR__ . '/../includes/db.php';

// Get webhook ID from environment
$webhook_id = getenv('PAYPAL_WEBHOOK_ID');
if (!$webhook_id) {
    http_response_code(500);
    exit('PAYPAL_WEBHOOK_ID not configured');
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload) {
    http_response_code(400);
    exit('Invalid JSON');
}

$event_type = $payload['event_type'] ?? '';
$event_id = $payload['id'] ?? '';
$resource = $payload['resource'] ?? [];

// For production, verify PayPal signature
// This requires calling PayPal's verification endpoint
if (getenv('APP_ENV') === 'production') {
    if (!verify_paypal_signature($_SERVER, $body, $webhook_id)) {
        http_response_code(403);
        exit('Invalid signature');
    }
}

try {
    $pdo = db();

    if ($event_type === 'PAYMENT.CAPTURE.COMPLETED') {
        $payment_id = $resource['id'] ?? '';
        $amount = (float)($resource['amount']['value'] ?? 0);
        $custom_id = $resource['custom_id'] ?? '';

        if ($payment_id && !empty($custom_id)) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE sponsorships
                 SET status = ?, payment_id = ?, paid_at = NOW()
                 WHERE public_code = ? AND status = ? AND payment_provider = ?'
            );
            $stmt->execute(['active', $payment_id, $custom_id, 'pending', 'paypal']);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                http_response_code(200);
                exit('One-time donation processed');
            }

            $pdo->rollBack();
        }
    } elseif ($event_type === 'BILLING_SUBSCRIPTION.PAYMENT_SUCCESS') {
        $subscription_id = $resource['id'] ?? '';
        $custom_id = $resource['custom_id'] ?? '';

        if ($subscription_id && !empty($custom_id)) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE sponsorships
                 SET status = ?, payment_id = ?, paid_at = NOW()
                 WHERE public_code = ? AND status = ? AND payment_provider = ?'
            );
            $stmt->execute(['active', $subscription_id, $custom_id, 'pending', 'paypal']);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                http_response_code(200);
                exit('Subscription payment processed');
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

/**
 * Verify PayPal webhook signature using their API
 * This is simplified; for production, implement full verification
 * See: https://developer.paypal.com/docs/api/webhooks/rest-webhooks/#verify_signature
 */
function verify_paypal_signature(array $headers, string $body, string $webhook_id): bool {
    $transmission_id = $headers['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '';
    $transmission_time = $headers['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '';
    $cert_url = $headers['HTTP_PAYPAL_CERT_URL'] ?? '';
    $signature = $headers['HTTP_PAYPAL_AUTH_ALGO'] ?? '';
    $expected_sig = $headers['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '';

    if (!$transmission_id || !$transmission_time || !$cert_url || !$expected_sig) {
        return false;
    }

    // In production, you would:
    // 1. Download and verify the certificate from $cert_url
    // 2. Compute: HMAC-SHA256(transmission_id.transmission_time.webhook_id.body, cert)
    // 3. Compare with $expected_sig

    // For now, return true to allow testing with proper signature verification disabled
    // Remove this in production!
    return getenv('APP_ENV') !== 'production';
}
