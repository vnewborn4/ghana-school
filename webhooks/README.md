# Webhook Handlers

This directory contains webhook handlers for payment processors. Webhooks automatically update sponsorship status when donations are received.

## Setup

### Environment Variables

Add these to your `.env` or server environment:

```bash
# Zeffy
ZEFFY_WEBHOOK_SECRET=your-webhook-secret-from-zeffy-dashboard

# Stripe  
STRIPE_WEBHOOK_SECRET=whsec_your-webhook-secret-from-stripe-dashboard

# PayPal
PAYPAL_WEBHOOK_ID=your-webhook-id-from-paypal-dashboard

# Optional
APP_ENV=production  # Enable signature verification
```

### Webhook URLs

Configure these URLs in your payment provider dashboard:

| Provider | Endpoint | Signature Header |
|----------|----------|------------------|
| **Zeffy** | `https://millcreek-ar-learning.com/webhooks/zeffy.php` | `X-Zeffy-Signature` |
| **Stripe** | `https://millcreek-ar-learning.com/webhooks/stripe.php` | `Stripe-Signature` |
| **PayPal** | `https://millcreek-ar-learning.com/webhooks/paypal.php` | `PayPal-Transmission-Sig` |

## How It Works

1. **Donor completes payment** on payment provider's secure form
2. **Payment provider sends webhook** to our endpoint with donation details
3. **Webhook handler verifies signature** (ensures it came from the provider)
4. **Sponsorship status updated** from "pending" to "active"
5. **Sponsor can see donation** in their portal

## Testing

### Test with cURL

```bash
# Zeffy (no signature required for testing)
curl -X POST https://millcreek-ar-learning.com/webhooks/zeffy.php \
  -H "Content-Type: application/json" \
  -d '{
    "event": "donation.success",
    "donation": {
      "id": "test-123",
      "amount": 50.00,
      "custom_fields": [
        {"name": "Student journey code", "value": "student-code-123"}
      ]
    }
  }'
```

### Test with Stripe CLI

```bash
# Install Stripe CLI: https://stripe.com/docs/stripe-cli
stripe listen --forward-to https://yoursite.com/webhooks/stripe.php
stripe trigger payment_intent.succeeded
```

### Test with PayPal Sandbox

Use PayPal's [Webhook Event Simulator](https://developer.paypal.com/dashboard):
- Navigate to Apps & Credentials → Sandbox
- Create test buyer and seller accounts
- Use Webhook Event Simulator to send test events

## Signature Verification

Each webhook includes a signature to prove it came from the payment provider:

- **Zeffy**: HMAC-SHA256(payload, secret)
- **Stripe**: HMAC-SHA256(timestamp.body, secret)
- **PayPal**: RSA signature verification with certificate download

The `verify_webhook_signature()` helper in `includes/security.php` handles verification.

## Troubleshooting

### Webhook not received
- Verify the URL is publicly accessible (test with `curl`)
- Check payment provider dashboard for delivery failures/logs
- Ensure firewall allows POST requests to `/webhooks/`

### Sponsorship not activating
- Check webhook logs for errors (most providers have delivery history)
- Verify environment variables are set (missing `*_SECRET` disables verification)
- Ensure `APP_ENV=production` if strict signature verification needed
- Check database for matching pending sponsorship

### Signature verification fails
- Verify the webhook secret matches your dashboard configuration
- Check request headers are passed correctly
- For Stripe, ensure raw body is used (not parsed JSON)

## Production Checklist

Before launch:

- [ ] Configure all webhook secrets in environment
- [ ] Test each payment provider with real test accounts
- [ ] Verify HTTPS is enforced (all webhooks must use HTTPS)
- [ ] Set `APP_ENV=production` to enable strict verification
- [ ] Monitor webhook delivery logs for first week
- [ ] Verify sponsorship statuses update correctly
- [ ] Test refund scenarios (if applicable)

## References

- [Zeffy Webhooks](https://zeffy.com/docs/webhooks)
- [Stripe Webhooks](https://stripe.com/docs/webhooks)
- [PayPal Webhooks](https://developer.paypal.com/docs/api/webhooks/rest-webhooks/)
