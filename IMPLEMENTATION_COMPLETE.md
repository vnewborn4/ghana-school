# Implementation Complete: Pre-Launch Features

**Date:** 2026-09-27  
**Commits:** See `git log` on `feature/enhanced-copy` branch  
**Status:** All coding complete, ready for live testing

---

## Summary

All required coding features for pre-launch have been implemented and deployed. The site now includes:
- Email verification system
- Rate limiting on signup & login
- Payment webhook handlers (Zeffy, Stripe, PayPal)
- Automated sponsorship status management
- Security headers and HTTPS enforcement
- Privacy policy and data retention documentation

---

## What's Implemented

### 1. Email Verification System ✅

**Files:**
- `includes/mail.php` — Email sending utilities
- `verify-email.php` — Email verification endpoint
- `migrations/2026-09-27-email-verification.sql` — Database schema

**How It Works:**
1. New sponsor creates account on signup.php
2. Email verification token generated and sent to their inbox
3. Sponsor clicks verification link in email
4. System confirms token and marks email verified
5. Sponsor can access portal features

**Database Tables:**
- `email_verifications` — Tracks verification tokens and status
- `users.email_verified` — Boolean flag for quick lookup

**Configuration:**
None required — works out of the box with PHP's `mail()` function.

**For Production:** Replace `send_mail()` in `includes/mail.php` with SMTP/Mailer library for reliability.

---

### 2. Rate Limiting ✅

**Files:**
- `includes/security.php` — `rate_limit()` function
- Updated `signup.php` — 5 attempts per 5 minutes on signup
- Updated `login.php` — 5 attempts per 5 minutes on login

**How It Works:**
- Tracks attempts by IP address + action type
- Uses temporary files in `/tmp` to store attempt timestamps
- Old attempts automatically cleaned up
- Returns `false` if rate limit exceeded

**Configuration:**
```php
rate_limit('signup', $_SERVER['REMOTE_ADDR'], 5, 300)  // 5 per 5 minutes
rate_limit('login', $_SERVER['REMOTE_ADDR'], 5, 300)   // 5 per 5 minutes
```

**For Distributed/Production:** Replace file-based storage with Redis/Memcached.

---

### 3. Payment Webhook Handlers ✅

#### Zeffy Webhook (`webhooks/zeffy.php`)
- **Endpoint:** `https://millcreek-ar-learning.com/webhooks/zeffy.php`
- **Event:** `donation.success`
- **Signature:** HMAC-SHA256 in `X-Zeffy-Signature` header
- **Action:** Updates sponsorship status: pending → active

#### Stripe Webhook (`webhooks/stripe.php`)
- **Endpoint:** `https://millcreek-ar-learning.com/webhooks/stripe.php`
- **Event:** `payment_intent.succeeded`
- **Signature:** Stripe's custom format in `Stripe-Signature` header
- **Action:** Updates sponsorship status: pending → active
- **Metadata:** Includes `sponsorship_id` for lookup

#### PayPal Webhook (`webhooks/paypal.php`)
- **Endpoint:** `https://millcreek-ar-learning.com/webhooks/paypal.php`
- **Events:** `PAYMENT.CAPTURE.COMPLETED`, `BILLING_SUBSCRIPTION.PAYMENT_SUCCESS`
- **Signature:** RSA verification with certificate download (in progress for production)
- **Action:** Updates sponsorship status: pending → active

**Configuration Required:**

In your server environment, set:
```bash
# Required
ZEFFY_WEBHOOK_SECRET=your-secret-from-zeffy-dashboard
STRIPE_WEBHOOK_SECRET=whsec_your-secret-from-stripe-dashboard
PAYPAL_WEBHOOK_ID=your-id-from-paypal-dashboard

# Optional
APP_ENV=production  # Enforce strict signature verification
```

**Setup Steps:**

1. **Zeffy:**
   - Log in to Zeffy dashboard
   - Go to Settings → Webhooks
   - Add endpoint: `https://millcreek-ar-learning.com/webhooks/zeffy.php`
   - Copy webhook secret to `ZEFFY_WEBHOOK_SECRET`

2. **Stripe:**
   - Log in to Stripe Dashboard
   - Go to Developers → Webhooks
   - Add endpoint: `https://millcreek-ar-learning.com/webhooks/stripe.php`
   - Select event: `payment_intent.succeeded`
   - Copy signing secret to `STRIPE_WEBHOOK_SECRET`

3. **PayPal:**
   - Log in to PayPal Developer Dashboard
   - Go to Apps & Credentials → Sandbox/Live
   - Select your account → Webhook Event Simulator
   - Create webhook: `https://millcreek-ar-learning.com/webhooks/paypal.php`
   - Copy Webhook ID to `PAYPAL_WEBHOOK_ID`

---

### 4. Security Features ✅

**HTTPS Enforcement** (`includes/security.php`)
- Automatic HTTP → HTTPS redirect in production
- Respects `HTTPS` server variable

**Security Headers** (added to every page)
- `X-Content-Type-Options: nosniff` — Prevent MIME type sniffing
- `X-Frame-Options: SAMEORIGIN` — Prevent clickjacking
- `X-XSS-Protection: 1; mode=block` — XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin` — Limit referrer leaks
- `Content-Security-Policy` — Restrict script/style sources

**Session Security** (was already in place)
- HttpOnly cookies (prevent XSS theft)
- SameSite=Lax (prevent CSRF)
- Secure flag (HTTPS only)

**Webhook Signature Verification**
- Verifies all incoming webhooks are genuine
- Prevents malicious actors from spoofing payments
- Uses provider-specific cryptographic signatures

---

### 5. Privacy & Compliance ✅

**Privacy Policy Page** (`privacy.php`)
- Accessible at `/privacy.php`
- Data collection, use, retention documented
- Placeholders for foundation contact info
- Links from footer

**Data Retention Policy**
- Account data: Duration of sponsorship + 24 months
- Payment records: 7 years (tax compliance)
- Student updates: Indefinite (published); 12 months (draft)
- Logs: 90 days

**Email Templates** (`includes/mail.php`)
- Email verification
- Donation receipt
- Password reset

---

### 6. Database Schema ✅

**New Table: `email_verifications`**
```sql
CREATE TABLE email_verifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

**New Column: `users.email_verified`**
```sql
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE
```

**Migration File:**
- `migrations/2026-09-27-email-verification.sql`
- Run once on production database before launch

---

## What Still Needs Manual Action

### 🔐 Critical (Before Live Testing)

1. **HTTPS Certificate**
   - Ensure SSL certificate is installed on `millcreek-ar-learning.com`
   - Test: navigate to site, verify no security warnings
   - Force HTTPS in `.htaccess` or server config

2. **Database Migration**
   - Run: `migrations/2026-09-27-email-verification.sql` on production DB
   - Verify tables and columns created successfully

3. **Environment Variables**
   - Set webhook secrets in server environment
   - See "Payment Webhook Handlers" section above

### 👥 Content Review (Can happen in parallel)

4. **Foundation Information**
   - Get tax status, EIN, leadership, contact info
   - Update `privacy.php` with foundation details
   - Update footer with contact email

5. **Van Newborn's Profile**
   - Upload verified portrait
   - Add credentials and approved biography
   - Update `bio.php`

6. **Student Consent & Images**
   - Verify written media consent for all identifiable student photos
   - Replace photos lacking consent with non-identifying images
   - Remove any surnames from photo captions

7. **Program Costs**
   - Replace starter amounts ($25, $50, $100 examples)
   - Add actual program costs from foundation
   - Update `impact.php` with real dollar-to-outcome mapping

---

## Testing Checklist

Before launching, test each feature:

### Email Verification
- [ ] Create new account
- [ ] Check inbox for verification email
- [ ] Click verification link
- [ ] Confirm email verified in database
- [ ] Test expired token (>24 hours old)

### Rate Limiting
- [ ] Try signup 6 times rapidly → should block on 6th
- [ ] Try login 6 times rapidly → should block on 6th
- [ ] Wait 5 minutes → should unblock

### Payment Webhooks

**Zeffy:**
- [ ] Test donation in Zeffy sandbox
- [ ] Verify webhook delivered (check Zeffy logs)
- [ ] Confirm sponsorship status updated to "active"
- [ ] Test with different student code/journey

**Stripe:**
- [ ] Install Stripe CLI: `stripe listen --forward-to ...`
- [ ] Trigger test payment: `stripe trigger payment_intent.succeeded`
- [ ] Verify webhook received and sponsorship updated

**PayPal:**
- [ ] Use PayPal Webhook Event Simulator
- [ ] Trigger `PAYMENT.CAPTURE.COMPLETED` event
- [ ] Verify sponsorship status updated

### Security
- [ ] Navigate to site → should be HTTPS (check browser lock icon)
- [ ] Open DevTools → Network → check response headers for security headers
- [ ] Try accessing with HTTP → should redirect to HTTPS
- [ ] Test privacy policy page is accessible and complete

### User Journey
- [ ] New visitor → browse journeys → select student
- [ ] New sponsor → sign up → verify email
- [ ] Confirmed sponsor → donate → see confirmation
- [ ] Existing sponsor → sign in → view portal
- [ ] Sponsor → see student updates (if published)

---

## Deployment

Already deployed on `feature/enhanced-copy` branch and live at:
`https://millcreek-ar-learning.com/`

Files auto-sync via FTPS on every push.

---

## Code References

| Feature | Files | Key Functions |
|---------|-------|----------------|
| Email Verification | `includes/mail.php`, `verify-email.php` | `send_verification_email()`, `verify_email_token()` |
| Rate Limiting | `includes/security.php`, `signup.php`, `login.php` | `rate_limit()` |
| Webhooks | `webhooks/*.php` | `verify_webhook_signature()` |
| Security | `includes/security.php`, `includes/header.php` | `add_security_headers()` |
| Privacy | `privacy.php` | — |

---

## Known Limitations

1. **Email:** Uses PHP's `mail()` — unreliable for production. Replace with SMTP/Mailer.
2. **Rate Limiting:** File-based — doesn't work on distributed servers. Use Redis for scale.
3. **PayPal Signature:** Simplified verification — enable full RSA verification in production.
4. **Webhooks:** Assume webhook IPs are trusted — add IP whitelisting if needed.

---

## Next Steps

1. Gather foundation information (contact, tax ID, etc.)
2. Collect student photos and verify consent
3. Set up HTTPS certificate
4. Configure webhook secrets in environment
5. Run email verification migration
6. Test all features per "Testing Checklist" above
7. Gather approval from foundation & local leadership
8. **Go live!**

---

**Questions?** See `LAUNCH_CHECKLIST.md` for detailed task breakdown.
