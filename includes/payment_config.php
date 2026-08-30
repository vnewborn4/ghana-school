<?php
// Configure hosted checkout URLs in Apache/PHP environment variables. Never place secret keys here.
// Fixed payment links are looked up by provider, frequency, and amount, e.g. STRIPE_MONTHLY_50_URL.
function payment_link(string $provider, string $frequency, float $amount): ?string {
    if (floor($amount) !== $amount) return null;
    $key = strtoupper($provider . '_' . str_replace('-', '_', $frequency) . '_' . (int)$amount . '_URL');
    $url = getenv($key);
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'https' ? $url : null;
}
