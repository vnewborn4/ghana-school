<?php
// Configure hosted checkout URLs in Apache/PHP environment variables. Never place secret keys here.
// Zeffy uses one Donation Campaign URL; donors confirm the amount and frequency on Zeffy's form.
// Fixed Stripe/PayPal links are looked up by provider, frequency, and amount.
function payment_link(string $provider, string $frequency, float $amount): ?string {
    if ($provider === 'zeffy') {
        $url = getenv('ZEFFY_DONATION_FORM_URL');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $isZeffyHost = $host === 'zeffy.com' || str_ends_with($host, '.zeffy.com');
        return $scheme === 'https' && $isZeffyHost ? $url : null;
    }
    if (floor($amount) !== $amount) return null;
    $key = strtoupper($provider . '_' . str_replace('-', '_', $frequency) . '_' . (int)$amount . '_URL');
    $url = getenv($key);
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'https' ? $url : null;
}
