# Mill Creek-AR Learning Center site setup

## Local site

1. Start Apache in XAMPP.
2. Open `http://localhost/ghana-school/`.
3. No database is required for the public site or pre-launch sponsorship review.

## Connect secure recurring payments

The site never handles card data. Create verified hosted payment links in Stripe or PayPal, then expose their HTTPS URLs to Apache/PHP as environment variables. The naming pattern is:

- `STRIPE_MONTHLY_25_URL`, `STRIPE_MONTHLY_50_URL`, `STRIPE_MONTHLY_100_URL`
- `STRIPE_ONE_TIME_25_URL`, `STRIPE_ONE_TIME_50_URL`, `STRIPE_ONE_TIME_100_URL`
- Equivalent `PAYPAL_...` names

Each hosted link must already contain the correct amount and recurring/one-time setting. Custom amounts intentionally remain in pre-launch mode until a server-side Checkout Session/API implementation is added.

Before launch, add success and cancellation URLs at the provider, configure a webhook endpoint, verify webhook signatures, record completed gifts in a protected database or donor system, and test in the provider sandbox. Keep all secret keys outside source control.

## Required content review before publication

- Replace the founder portrait placeholder and add the founder's verified name, credentials, and approved biography.
- Confirm written guardian/media consent for every identifiable student image; otherwise replace it with a non-identifying classroom or project image.
- Add verified AD2 Alumni Foundation legal/nonprofit details, privacy policy, donation/refund terms, mailing address, and contact information.
- Replace general support examples with approved program costs or an audited allocation statement before describing what specific dollar amounts fund.
- Review all safeguarding language with local leadership and the foundation.
