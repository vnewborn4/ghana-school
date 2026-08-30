# Mill Creek-AR Learning Center site setup

## Local site

1. Start Apache in XAMPP.
2. Open `http://localhost/ghana-school/`.
3. Import `database.sql` through phpMyAdmin (or run it with the XAMPP MariaDB client) to create the sponsorship portal tables and privacy-safe starter journeys.

The local defaults use database `ghana_school`, user `root`, and an empty password. For any shared or hosted environment, set `GHANA_DB_HOST`, `GHANA_DB_NAME`, `GHANA_DB_USER`, and `GHANA_DB_PASSWORD` outside source control and use a dedicated least-privilege database account.

## Connect secure recurring payments

The site never handles card data. Create verified hosted payment links in Stripe or PayPal, then expose their HTTPS URLs to Apache/PHP as environment variables. The naming pattern is:

- `STRIPE_MONTHLY_25_URL`, `STRIPE_MONTHLY_50_URL`, `STRIPE_MONTHLY_100_URL`
- `STRIPE_ONE_TIME_25_URL`, `STRIPE_ONE_TIME_50_URL`, `STRIPE_ONE_TIME_100_URL`
- Equivalent `PAYPAL_...` names

Each hosted link must already contain the correct amount and recurring/one-time setting. Custom amounts intentionally remain in pre-launch mode until a server-side Checkout Session/API implementation is added.

Before launch, add success and cancellation URLs at the provider, configure a webhook endpoint, verify webhook signatures, record completed gifts in a protected database or donor system, and test in the provider sandbox. Keep all secret keys outside source control.

## Sponsor accounts and updates

The local portal supports account signup, sign-in, journey selection, pending sponsorship records, contribution status, and privacy-safe learning updates. Passwords are hashed and forms use CSRF protection.

### Enable an administrator

1. Create a normal account through the sponsorship signup flow.
2. Run the migration in `migrations/2026-08-30-admin-student-profiles.sql` once on an existing database.
3. Promote the approved account directly in the database: `UPDATE users SET is_admin=1 WHERE email='approved-admin@example.org';`
4. Sign out and sign back in, then open `/ghana-school/admin.php`.

Administrator access is deliberately never granted to the first signup automatically. The protected workspace can add first-name-only student profiles, hide or publish them for selection, and publish reviewed sponsor updates. Do not enter surnames or sensitive child information.

Before public launch, require HTTPS; add email verification, password reset, rate limiting, secure production session settings, privacy/retention terms, and an administrator-only workflow for reviewing and publishing updates. Replace every starter update with verified program content. Payment webhooks—not browser redirects—must be the only process allowed to mark a sponsorship active or completed.

## Required content review before publication

- Replace Van Newborn's portrait placeholder and add his verified credentials and approved biography.
- Confirm written guardian/media consent for every identifiable student image; otherwise replace it with a non-identifying classroom or project image.
- Confirm the AD2 Alumni Foundation's current tax status, EIN, leadership, official contact information, privacy policy, donation/refund terms, and reporting commitments before live fundraising.
- Replace general support examples with approved program costs or an audited allocation statement before describing what specific dollar amounts fund.
- Review all safeguarding language with local leadership and the foundation.
