# Pre-Launch Checklist

**Site:** Mill Creek-AR Learning Center Sponsorship Portal  
**Launch Date:** [To be determined]  
**Status:** In progress

---

## ✅ Completed (by Claude)

### Infrastructure & Security
- [x] FTPS automated deployment configured (`.github/workflows/deploy.yml`)
- [x] HTTPS enforcement (redirect HTTP → HTTPS)
- [x] Security headers added (CSP, X-Frame-Options, X-Content-Type-Options)
- [x] Session security (HttpOnly, SameSite, Secure flag)
- [x] CSRF protection on all forms
- [x] Rate limiting utilities for login/signup
- [x] Email verification system scaffold
- [x] Webhook signature verification helper

### Content & Documentation
- [x] Privacy policy page (`/privacy.php`)
- [x] Data retention policy documented
- [x] Graph memory system for Claude sessions
- [x] Database migration for email verification

---

## ⬜ Requires Manual Action

### 🔐 **Security & Compliance (DO NOT SKIP)**

#### HTTPS & SSL Certificate
- [ ] **Assign SSL certificate** to `ftp.millcreek-ar-learning.com`
  - Contact hosting provider or use Let's Encrypt
  - Ensure certificate covers the production domain
  - Force HTTPS in `.htaccess` or server config: `Header always set Strict-Transport-Security "max-age=31536000"`

#### Email Verification (Optional but Recommended)
- [ ] Run migration: `migrations/2026-09-27-email-verification.sql`
- [ ] Update `signup.php` to call `rate_limit('signup', $_SERVER['REMOTE_ADDR'], 5, 300)`
- [ ] Update `login.php` to call `rate_limit('login', $_SERVER['REMOTE_ADDR'], 5, 300)`
- [ ] Send verification email after signup
- [ ] Create `/verify-email.php` endpoint to handle token clicks

#### Payment Webhooks
- [ ] **For Zeffy:** Configure webhook URL in Zeffy dashboard
  - Endpoint: `https://millcreek-ar-learning.com/webhooks/zeffy.php`
  - Only allow webhook calls to mark sponsorship status (never browser redirects)
  - Verify signature using `verify_webhook_signature()` helper
- [ ] **For Stripe/PayPal:** Set webhook secrets in environment variables
  - Stripe: `STRIPE_WEBHOOK_SECRET`
  - PayPal: `PAYPAL_WEBHOOK_ID`

---

### 👥 **Content Review (Foundation & Leadership)**

#### Foundation Information
- [ ] **Verify AD2 Alumni Foundation Details:**
  - [ ] Current tax status & EIN
  - [ ] Legal entity name & address
  - [ ] Leadership names & titles
  - [ ] Official contact email & phone
  - [ ] Official privacy policy (if separate from this Site's policy)
  - [ ] Donation/refund policy (update in `foundation.php`)
  - [ ] Reporting commitments (add to `foundation.php`)
  - [ ] 990-N tax form filing status
  
  *Add to:* `foundation.php`, footer, and `/privacy.php`

#### Approved Program Costs
- [ ] **Replace starter examples** in `impact.php` with:
  - [ ] Actual program costs from foundation
  - [ ] Audited allocation statement (what dollars fund)
  - [ ] Examples: "$50 funds 2 weeks of mentoring," etc.
  - [ ] Show in adopt flow and sponsorship cards

#### Student & Staff Content
- [ ] **Replace Van Newborn's profile** in `bio.php`:
  - [ ] Upload verified portrait/photo
  - [ ] Add approved credentials & title
  - [ ] Add approved biography
  - [ ] Ensure written media consent is on file

- [ ] **Review all student content:**
  - [ ] Verify guardian/parent written media consent for every identifiable image
  - [ ] Replace photos lacking consent with non-identifying classroom/project images
  - [ ] Verify no surnames appear with student images
  - [ ] Verify no sensitive personal details (addresses, family info) included

#### Safeguarding & Legal
- [ ] **Review safeguarding language** with local leadership:
  - [ ] All references to child safety align with foundation policy
  - [ ] Sponsor communication guidelines clear (appropriate boundaries)
  - [ ] Report child safety concerns protocol documented
  
- [ ] **Legal Review** (optional but recommended):
  - [ ] Terms of service (if not yet written)
  - [ ] Data processing agreement with payment providers
  - [ ] Donation acknowledgment & tax deduction language

---

### 🧪 **Live Testing & Launch**

#### Email Verification (if implemented)
- [ ] Test signup → verification email → click link → login
- [ ] Verify rate limiting blocks >5 signup attempts in 5 min
- [ ] Verify rate limiting blocks >5 login attempts in 5 min

#### Payment Processing
- [ ] **Zeffy test:** 
  - [ ] Create sponsor account
  - [ ] Donate via test campaign
  - [ ] Verify donation appears in Zeffy dashboard
  - [ ] Verify sponsorship marked "pending" in portal
  - [ ] Confirm webhook called to update status

- [ ] **Stripe (if enabled):**
  - [ ] Test donation in Stripe sandbox
  - [ ] Verify webhook received and logged
  - [ ] Verify signature verification passes

- [ ] **PayPal (if enabled):**
  - [ ] Test donation in PayPal sandbox
  - [ ] Verify webhook received
  - [ ] Verify donation status updated correctly

#### Admin Workflow
- [ ] Create admin account:
  ```sql
  -- 1. Create sponsor account via signup.php
  -- 2. Run: UPDATE users SET is_admin=1 WHERE email='admin@example.org';
  -- 3. Sign out and sign back in
  -- 4. Visit /admin.php
  ```
- [ ] [ ] Can add student profiles
- [ ] [ ] Can hide/publish student profiles
- [ ] [ ] Can create and publish learning updates
- [ ] [ ] Updates appear in sponsor portal

#### Security & Compliance
- [ ] HTTPS enforced (no HTTP access)
- [ ] Security headers present (check in browser DevTools → Network)
- [ ] Session cookies marked Secure + HttpOnly + SameSite
- [ ] CSRF tokens required on all forms
- [ ] Privacy policy page accessible and complete
- [ ] Foundation contact info in privacy policy & footer

#### User Journeys
- [ ] [ ] New visitor can browse learning journeys
- [ ] [ ] New sponsor can sign up → verify email (if enabled)
- [ ] [ ] Sponsor can adopt a student
- [ ] [ ] Sponsor can donate → receives confirmation
- [ ] [ ] Sponsor can view learning updates
- [ ] [ ] Sponsor can update profile

---

## 📋 Notes

- **Email Verification:** Not required for launch but strongly recommended for compliance
- **HTTPS:** Required for payment security and modern browsers (do not skip)
- **Safeguarding:** Consult local leadership on child safety language
- **Foundation Verification:** Critical for donor trust and legal compliance
- **Webhook Testing:** Must use actual test keys from payment providers, not mock endpoints

---

## Deployment Steps on Launch Day

1. **Run migrations** on production database:
   ```sql
   -- Source: migrations/2026-09-27-email-verification.sql
   ```

2. **Set environment variables** on hosting:
   ```
   APP_ENV=production
   ZEFFY_DONATION_FORM_URL=https://www.zeffy.com/...
   STRIPE_WEBHOOK_SECRET=whsec_...  (if using Stripe)
   PAYPAL_WEBHOOK_ID=...  (if using PayPal)
   ```

3. **Verify SSL certificate** is installed and HTTPS is enforced

4. **Run final security check:**
   - [ ] HTTPS loads without warnings
   - [ ] Donations process successfully
   - [ ] Admin panel accessible
   - [ ] No PHP errors in logs

5. **Announce to foundation & sponsors**

---

## Contacts & Escalation

| Item | Owner | Contact |
|------|-------|---------|
| Foundation Info | AD2 Alumni Foundation | [To be filled] |
| Student Consent | Mill Creek-AR Staff | [To be filled] |
| HTTPS Certificate | Hosting Provider | [To be filled] |
| Payment Testing | Payment Provider | Zeffy/Stripe/PayPal support |
| Launch Approval | Foundation Leadership | [To be filled] |

---

**Last Updated:** 2026-09-27  
**Next Review:** [Before launch]
