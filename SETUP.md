# Mill Creek-AR Learning Center site setup

## Local site

1. Start Apache in XAMPP.
2. Open `http://localhost/ghana-school/`.
3. Import `database.sql` through phpMyAdmin (or run it with the XAMPP MariaDB client) to create the sponsorship portal tables and privacy-safe starter journeys.

The local defaults use database `ghana_school`, user `root`, and an empty password. For any shared or hosted environment, set `GHANA_DB_HOST`, `GHANA_DB_NAME`, `GHANA_DB_USER`, and `GHANA_DB_PASSWORD` outside source control and use a dedicated least-privilege database account.

## Connect Zeffy recurring donations

Zeffy is the default donation provider. The site creates the donor account and pending sponsorship record, then sends the donor to the foundation's secure Zeffy Donation Campaign. Card and bank information is never collected by this site.

1. An authorized AD2 Alumni Foundation representative creates or signs in to the verified Zeffy organization account.
2. Create a **Donation Campaign** for the Mill Creek-AR Learning Center sponsorship program.
3. Enable **monthly** and **one-time** frequencies. Add the site's suggested amounts ($25, $50, and $100) and approved impact descriptions.
4. Add a required custom question such as **Student journey first name or code** so staff can reconcile the gift with the pending sponsor-dashboard record. Do not request a child's surname or other sensitive information.
5. Publish the campaign, open **Campaigns -> Share**, and copy its full HTTPS campaign link.
6. Set that link for Apache/PHP as `ZEFFY_DONATION_FORM_URL`. A valid value begins with `https://www.zeffy.com/`.
7. Restart Apache and complete an authorized test donation. Verify the Zeffy receipt and reconcile the pending site record before launch.

Run `migrations/2026-08-30-zeffy-provider.sql` once on an existing database. New installations receive Zeffy support from `database.sql` automatically.

The campaign link is kept outside source code so an unreviewed test campaign is not published accidentally. The site does not claim automatic Zeffy synchronization: staff must verify transactions in Zeffy and update sponsorship status through a reviewed administrative process.

### Optional Stripe or PayPal links

Stripe and PayPal remain available as optional fallbacks. Create verified hosted payment links, then expose their HTTPS URLs to Apache/PHP as environment variables. The naming pattern is:

- `STRIPE_MONTHLY_25_URL`, `STRIPE_MONTHLY_50_URL`, `STRIPE_MONTHLY_100_URL`
- `STRIPE_ONE_TIME_25_URL`, `STRIPE_ONE_TIME_50_URL`, `STRIPE_ONE_TIME_100_URL`
- Equivalent `PAYPAL_...` names

Each hosted link must already contain the correct amount and recurring/one-time setting. Custom amounts intentionally remain in pre-launch mode until a server-side Checkout Session/API implementation is added.

For Stripe or PayPal, add success and cancellation URLs, configure a webhook endpoint, verify webhook signatures, record completed gifts in a protected database or donor system, and test in the provider sandbox. Keep all secret keys outside source control.

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

## Automatic deployment on merge to main

`.github/workflows/deploy.yml` uploads the site to the web server over FTPS whenever a change is pushed or merged to `main` (it can also be run manually from the Actions tab). Configure it in the GitHub repository under **Settings -> Secrets and variables -> Actions**:

- Secret `FTP_SERVER` — the FTP host, e.g. `ftp.example.org`.
- Secret `FTP_USERNAME` — the FTP account username.
- Secret `FTP_PASSWORD` — the FTP account password.
- Variable `FTP_SERVER_DIR` — remote directory to deploy into, ending with a slash, e.g. `public_html/`. Defaults to the FTP account's root when unset.

Use a dedicated FTP account restricted to the site directory, and prefer FTPS (the default in the workflow); only fall back to plain `ftp` if the host cannot do FTPS. The sync excludes development files (`.github/`, `.claude/`, `.mcp.json`, `SETUP.md`, `database.sql`, `migrations/`). Database changes are not deployed automatically — run new files in `migrations/` on the server database manually.

## Claude Code graph memory

The project ships a `.mcp.json` that loads the official MCP knowledge-graph memory server (`@modelcontextprotocol/server-memory`) in Claude Code sessions. The graph is stored at `.claude/memory.jsonl` (one JSON object per line) so it lives inside the repository. Sessions can record entities, relations, and observations about the project and recall them later. Because remote Claude Code containers are ephemeral, commit `.claude/memory.jsonl` after a session adds anything worth keeping — only committed changes persist to future sessions.

## Student academy

The academy adds a learner side to the site: student accounts, assignments, a
personal web page for every student, and a teacher portal. The design and the
reasoning behind it are in `docs/ACADEMY_INTEGRATION.md`; funding and free
resources are in `docs/FUNDING_AND_FREE_RESOURCES.md`.

### 1. Run the migration

```
mysql ghana_school < migrations/2026-09-27-academy.sql
```

It is safe to run more than once. It also adds a `role` column to `users` and
promotes anyone with the legacy `is_admin=1` flag to `role='admin'`.

Run `migrations/2026-09-27-email-verification.sql` too if you have not already.
It previously failed on a foreign-key type mismatch and never created its table;
that is fixed, so check whether `email_verifications` exists on your database.

### 2. Point storage outside the web root

Learner files — student pages and assignment uploads — must not be reachable
over HTTP. Set an absolute path above the document root:

```
SetEnv GHANA_STORAGE_PATH /home/youraccount/private/ghana-storage
```

The directory must be writable by the web server. Without this the files go in
`storage/` inside the project, which `storage/.htaccess` denies — a fallback, not
the preferred arrangement. Check it by requesting `/storage/student_sites/`
in a browser: anything other than 403 or 404 means the protection is not working
and you should stop and fix it before onboarding a child.

### 3. Confirm the rewrites work

`/students/<slug>/` and `/students/preview/<slug>/` are served by
`students/serve.php` through rules in `.htaccess`. They need `AllowOverride` to
permit `mod_rewrite` on the host. After creating your first learner, open their
page address: a 404 where a page should be usually means the rewrites are not
being applied.

### 4. Make someone a teacher

```
UPDATE users SET role='teacher' WHERE email='teacher@example.org';
```

Roles are `sponsor` (the default), `teacher`, and `admin`. Admins can do
everything teachers can. Administrator access is never granted automatically.

### 5. Create a class, then onboard learners

Sign in, open **Teacher portal**, and use **Onboard**. One submission creates the
account, a one-time PIN, the student's web page, and the audit entry, then prints
cut-up welcome cards. Paste a whole class roster to do thirty at once.

**PINs are shown once.** They are stored only as a hash. A forgotten PIN is reset
by a teacher from the learner's page, which is also the right identity check for
a child.

Add classes directly for now:

```
INSERT INTO cohorts (name, term) VALUES ('Tuesday Coders', 'Term 1 2026');
```

### 6. Install the coding activities

`lab/` ships empty. See `lab/README.md` — Blockly Games is the place to start, at
about 4 MB and fully offline. Modules that need a tool are seeded unpublished so
a learner never meets a broken link; publish them once the tool is in place.

### 7. Language

`lang/en.php` is complete. `lang/tw.php` holds a starter set of Twi, and every
key missing from it falls back to English automatically.

**Before showing Twi to families, have a Twi speaker on the Accra staff read and
extend that file.** Translate the guardian consent form first — consent given in
a language a guardian does not read is not informed consent.

To add Ga or Ewe, copy `lang/en.php` to `lang/gaa.php` or `lang/ee.php`, translate
what you can, and uncomment the language in `supported_langs()` in
`includes/i18n.php`.

### Before a child uses any of this

- Signed guardian consent, in a language the guardian reads, covering the account,
  the data held, and the public web page. The onboarding form requires the date
  from that form, and `consent_scope` is enforced in the database: work from a
  learner marked "learning only" can never become a sponsor update, whatever a
  teacher ticks.
- Registration as a data controller with Ghana's Data Protection Commission.
- A children's-data section in `privacy.php`. The current policy covers sponsors
  only.
- A written safeguarding policy agreed with the centre's leadership and the
  AD2 Alumni Foundation, naming who may view learner work and who takes a page
  down out of hours.

### Smoke test

`tests/smoke.sh` walks the whole academy flow and asserts the properties that
protect children: role separation, the publication gate, path traversal, CSRF,
draft privacy, and the take-everything-offline switch.

```
BASE_URL=http://localhost/ghana-school tests/smoke.sh
```

Run it against a development database only — it creates learners and publishes
pages. It expects the migrations applied plus two seeded accounts,
`admin@example.org` and `teacher@example.org`; the header comment lists them.

### Sponsor bridge

Turns reviewed classroom work into sponsor updates, through an approval gate.

```
mysql ghana_school < migrations/2026-09-28-sponsor-bridge.sql
```

Safe to run more than once. It does not change what sponsors currently see:
existing updates are marked approved and stay visible, and updates written by
hand in `admin.php` keep working.

**Before a learner's work can reach a sponsor, an administrator must link them
to a student journey** — Teacher portal → the learner → *Sponsor journey*. The
link is the join between a real child and the pseudonymous profile a sponsor
follows, so teachers cannot set it and one journey belongs to one learner.

How it runs day to day:

1. A teacher marks work as done and ticks **Propose this work for a sponsor
   update**. The tick only appears for learners whose guardian agreed to it,
   and the consent check is in the query, so a forced form post changes nothing.
2. A draft appears under **Sponsor updates** (administrators only). The draft
   text is built from facts about the lesson. It never contains what the learner
   wrote — children write freely, and a child may have named themselves, their
   school, or their street.
3. The administrator reads the learner's words shown alongside as context,
   **rewrites the update in their own words**, and approves it. Only then does a
   sponsor see anything.

Publishing a learner's page for the first time drafts a milestone the same way.

Consent is checked again at approval, so if a guardian withdraws it between the
draft being created and someone approving it, approval is refused.

An approved update can be taken down again from the same page.

The public **Why technology** page shows aggregate figures — learners enrolled,
projects completed, pages published — drawn from the same records. They are
counts only, no learner is identified, and the section is hidden until there is
something real to report.

### Language policy

English is the primary language of the site. Ghanaian languages exist to help a
learner understand an instruction, not to replace English, which is the language
of instruction from Primary 4 and of every programming reference they will meet.

The donor-facing side — the sponsor portal, sponsor updates, and the public
pages — is English only and should stay that way. Sponsors read English.

## The learning centre's Kolibri server

Kolibri is an offline-first learning platform that runs on a Raspberry Pi or a
spare laptop **at the centre in Accra**, serving lessons over the centre's own
Wi-Fi with no internet at all. It is not installed on this web host and cannot
be: it is Python, and the point of it is to work where the line does not.

The full runbook — hardware, install, content, learners, backups,
troubleshooting — is `docs/KOLIBRI_CENTRE_SETUP.md`. The install kit is
`tools/kolibri/`, which is excluded from the web deploy because it belongs on
the centre machine, not here.

What the website does with it:

```
mysql ghana_school < migrations/2026-09-29-centre-activity.sql
```

**Roster out.** Teacher portal → **Centre** → *Download roster for Kolibri*
gives a CSV in Kolibri's own import format, so a learner onboarded here gets
the same username at the centre. Kolibri does not allow hyphens in usernames,
so `ama-k9` is carried across as `ama_k9`; the website does the swap in both
directions and shows the Kolibri username on the learner's page.

**Activity back.** Two routes, one format, same result:

- *Offline, the normal route.* At the centre,
  `kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json`, then upload
  that file under **Centre → Upload activity**.
- *Automatic, when there is a line.* The centre posts the same payload to
  `api/kolibri_sync.php`, signed with a shared secret. Set
  `KOLIBRI_SYNC_SECRET` on this server to a long random string and the same
  value in `/etc/kolibri-sync.env` at the centre. Without the secret the
  endpoint refuses everything rather than accepting unsigned data.

Re-sending the same window is safe: rows are keyed on the day and the learner.

Only counts travel — a date, a Kolibri username, sessions, completions,
minutes. No content titles, no real names, nothing a child typed. The figures
appear on the learner's page, in the programme report, and in the aggregate
totals on the public page.

## Offline access to the academy

The academy keeps working when the line goes down: the page still opens, the
interface is already on the device, and a learner's work is never lost to a
dropped connection. Nothing needs installing — it is a service worker at
`academy/sw.js` plus `assets/js/academy-offline.js`, and it turns itself on
the first time a learner visits.

**It only covers `/academy/`.** A service worker's scope is its own directory,
so this one never touches the sponsor portal, the teacher portal or a
student's published page.

### What is kept on the device, and what is not

| | |
| --- | --- |
| **The shell** — stylesheets, scripts, icons, the offline page | Cached. The same bytes for everyone, safe on a shared lab machine. |
| **Page HTML** | Never cached. Academy pages are personal and these machines are shared, so a failed navigation shows the offline page rather than the last child's dashboard. |
| **Unsent work** | Queued in the browser, tagged with the learner who made it. |
| **Unsaved typing in the page editor** | Kept locally and offered back on the next visit. Only the page's own code, which is written to be published — not reflections or answers. |

Both the queue and the local draft expire after seven days.

### What a learner sees

Offline, a banner says their work is being kept on the computer. When the
connection returns it sends by itself and the banner confirms it. Nothing is
ever dropped silently: if a save cannot be sent because the session expired,
it stays in the queue and the learner is told to sign in again on that
computer.

**Work is only ever sent for the learner who made it.** If a child queues
work and someone else signs in on the same machine, the queued work sits
untouched and the page explains why. One child's homework can never be
submitted under another child's name.

### Installing it to a phone

`academy/manifest.webmanifest` makes the academy installable to a home screen,
which is worth suggesting to learners who have a phone: it opens full screen
and the shell is already there, so it starts even with no data.

### When you change the interface

Bump `CACHE_VERSION` in `academy/sw.js`. Old caches are deleted on activate,
so a learner picks up the new shell on their next visit. Without the bump,
stale CSS or JavaScript can sit on a device for a day.

### Testing it

```
php -S localhost:8099 -t .          # localhost, not 127.0.0.1 — see below
ALLOW_DB_RESET=yes tests/run-offline-tests.sh
```

It drives a real Chromium through a real dropped connection: registration,
scope, what is and is not cached, queueing offline, draining when the line
returns, the file-upload round trip, the cross-learner guard, and the
manifest.

Use **localhost**, not an IP address. Browsers only treat localhost as a
secure context, and without one there are no service workers to test. In
production this is not a concern: the site already forces HTTPS.

Playwright is a development dependency and is not in the repository:
`PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install playwright`, then set
`PLAYWRIGHT_PATH` if it is not on the default module path.
