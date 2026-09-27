# Mercury Bank Account Setup for AD2 Alumni Foundation + Zeffy Integration

**Purpose:** Create a dedicated business bank account for the AD2 Alumni Foundation and connect it to Zeffy for secure donation processing.

**Timeline:** 1-2 business days (Mercury account verification)

---

## Part 1: Create Mercury Business Account

### Prerequisites
You'll need:
- [ ] Foundation EIN (Employer Identification Number) or SSN if sole proprietor
- [ ] Foundation business name: "AD2 Alumni Foundation"
- [ ] Registered business address
- [ ] Authorized signatory's government ID (passport or driver's license)
- [ ] Phone number & email
- [ ] Expected monthly transaction volume estimate

### Step-by-Step Setup

1. **Go to Mercury.com**
   - Visit: https://mercury.com
   - Click "Sign up" (top right)
   - Select "Business account"

2. **Enter Business Information**
   - Business type: Select "Nonprofit" or "Other"
   - Legal business name: "AD2 Alumni Foundation"
   - EIN: [Your foundation's EIN]
   - Business address: [Foundation's address]
   - Phone: [Contact number]
   - Email: [Foundation email]
   - Website: https://millcreek-ar-learning.com (or foundation website)

3. **Verify Your Identity**
   - Upload government-issued ID (passport or driver's license)
   - Take selfie (photo verification)
   - Mercury will verify within 1-2 business days

4. **Link Funding Source** (Optional for now)
   - You can link a personal or existing business bank account
   - Or skip and fund later
   - Mercury will provide account details once verified

5. **Confirm & Review**
   - Review terms and conditions
   - Accept Mercury's service agreement
   - Submit application

### After Submission
- Mercury sends confirmation email
- Verification typically takes 1-2 business days
- You'll receive:
  - Account number
  - Routing number
  - Account holder name (AD2 Alumni Foundation)

---

## Part 2: Connect Mercury to Zeffy

### Prerequisites
- [ ] Mercury account created and verified (has account/routing numbers)
- [ ] Zeffy account already set up (you mentioned you have one)
- [ ] Zeffy admin access

### Step-by-Step Integration

1. **In Zeffy Dashboard**
   - Log in to: https://zeffy.com/dashboard
   - Go to: Settings → Payment Methods
   - Or: Integrations → Bank Account

2. **Select "Connect Bank Account"**
   - Click "Add Bank Account" or "Connect Bank"
   - Choose "Mercury" (if listed) or "Manual Bank Transfer"
   - If Mercury isn't listed, use "Manual Bank Transfer" (more common)

3. **Enter Mercury Account Details**
   - Account holder name: "AD2 Alumni Foundation"
   - Account number: [From Mercury]
   - Routing number: [From Mercury]
   - Account type: Checking
   - Bank name: "Mercury" or "Evolve Bank & Trust" (Mercury's banking partner)

4. **Verify Account**
   - Zeffy will send 2 micro-deposits (small test transfers)
   - Check Mercury account for incoming deposits (~2-3 business days)
   - Note the amounts (usually $0.01 and $0.02)
   - Return to Zeffy and enter the amounts to verify

5. **Confirm Connection**
   - Once verified, account is active
   - Donations will begin flowing to your Mercury account
   - Zeffy dashboard shows account connected ✓

---

## Part 3: Verify Integration Works

### Test Donation Flow
1. **In Zeffy Dashboard**
   - Go to: Test Mode or Sandbox (if available)
   - Create a test donation for $5-$10
   - Confirm success page displays

2. **Check Mercury Account**
   - Log in to: https://mercury.com
   - Go to: Transactions
   - Look for test donation from Zeffy
   - Verify amount and timestamp

3. **Enable Live Donations**
   - Turn off Test Mode in Zeffy
   - Your donation form is now live and collecting funds

---

## Part 4: Reconciliation & Monitoring

### Weekly
- Check Mercury balance
- Compare with Zeffy dashboard (should match)
- Review transaction details

### Monthly
- Download Mercury statement
- Download Zeffy reports
- Reconcile amounts and timing
- Update foundation records

---

## Troubleshooting

### Zeffy shows account connected but no deposits appearing

**Likely cause:** Mercury account not fully verified or test deposits not confirmed

**Solution:**
1. Return to Zeffy settings
2. Check if verification is still pending
3. Confirm Mercury micro-deposits were entered correctly
4. Contact Zeffy support with:
   - Mercury account number (last 4 digits)
   - Zeffy organization ID

### Mercury account taking longer to verify

**Normal:** 1-2 business days
**Delayed:** If ID verification fails, Mercury will email instructions

**Solution:**
1. Check email for verification issues
2. Re-submit ID if requested
3. Ensure photo is clear and matches ID
4. Contact Mercury support: support@mercury.com

### Small test deposits not arriving in Mercury

**Likely cause:** Account number or routing number entered incorrectly

**Solution:**
1. Go to Mercury → Account Details
2. Copy exact account/routing numbers
3. Return to Zeffy and correct the information
4. Request new verification deposits

---

## Important Notes

### Security
- ✅ Don't share Mercury login with anyone
- ✅ Don't share account/routing numbers in plain email
- ✅ Use strong password (16+ characters, mixed case + numbers + symbols)
- ✅ Enable two-factor authentication (2FA) in Mercury

### Compliance
- Keep Mercury & Zeffy records for audit/tax purposes (7 years)
- Reconcile weekly to catch discrepancies early
- Maintain donation records for donor receipts
- Report to accountant/CPA monthly

### Fees
- Mercury: No monthly fees for basic account
- Zeffy: Takes small percentage per donation (typically 1.5% + $0.25)
- ACH transfers: Usually free from Mercury

---

## Links & Resources

| Service | URL | Purpose |
|---------|-----|---------|
| **Mercury** | https://mercury.com | Business banking |
| **Mercury Help** | https://help.mercury.com | Support & docs |
| **Zeffy** | https://zeffy.com/dashboard | Donation platform |
| **Zeffy Help** | https://zeffy.com/help | Support & docs |

---

## Checklist for AD2 Alumni Foundation Setup

### Before Starting
- [ ] Foundation EIN obtained
- [ ] Authorized signatory identified
- [ ] Business address confirmed
- [ ] Government ID ready (passport or driver's license)

### Mercury Setup
- [ ] Account created
- [ ] Identity verified (1-2 business days)
- [ ] Account/routing numbers received
- [ ] 2FA enabled
- [ ] Strong password set

### Zeffy Integration
- [ ] Zeffy account accessed
- [ ] Mercury bank account details entered
- [ ] Micro-deposits received in Mercury
- [ ] Verification amounts entered in Zeffy
- [ ] Account marked "connected" in Zeffy

### Testing & Launch
- [ ] Test donation made and received
- [ ] Mercury shows incoming funds
- [ ] Zeffy reports match Mercury balance
- [ ] Live donation form enabled
- [ ] First real donation processed successfully

### Ongoing
- [ ] Weekly balance checks
- [ ] Monthly reconciliation
- [ ] 2FA enabled and secure
- [ ] Records kept for compliance

---

## Contact Info to Update Later

Once account is set up, update the site:

**In `privacy.php`:**
```
Foundation Contact: AD2 Alumni Foundation
Address: [From Mercury setup]
Email: [Foundation email]
```

**In `foundation.php`:**
```
Bank: Mercury (via Evolve Bank & Trust)
Payment Processor: Zeffy
Process: Donations securely processed to foundation account
```

---

## Timeline Summary

| Step | Time | Status |
|------|------|--------|
| Create Mercury account | 5 min | Today |
| Mercury verification | 1-2 days | Once submitted |
| Link to Zeffy | 5 min | After Mercury verified |
| Micro-deposit verification | 2-3 days | Zeffy waits for deposits |
| Go live | Immediate | Once verified |

**Total: 3-5 business days from start to live donations**

---

**Questions?** Each service has excellent support:
- Mercury: support@mercury.com or help.mercury.com
- Zeffy: support@zeffy.com or zeffy.com/help
