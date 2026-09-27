# Student Academy: coding education, student web spaces, and portals

Design proposal for adding a learner-facing academy to the Mill Creek-AR Learning
Center site, alongside the existing donor/sponsor side.

Covers: onboarding automation, per-student web directories, student / teacher / admin
portals, open-source coding tools, dual-language (English + Ghanaian language) support,
and cultural and legal fit for a program in Accra.

Status: **phases 1–4 and 6 are built and running**; see "What is built" below.
Phases 5 and 7 remain proposals.

---

## What is built

| Area | State |
| --- | --- |
| Learner accounts, separate from `users`, username + staff-issued PIN | Built — `includes/learner_auth.php`, `migrations/2026-09-27-academy.sql` |
| Separate session, idle timeout, PIN attempt limiting, forced first-time PIN change | Built |
| Automated onboarding, single and bulk, with printable welcome cards | Built — `teach/onboard.php`, `teach/cards.php`, `includes/onboarding.php` |
| Student web spaces at `/students/<slug>/`, draft and live, quotas, EXIF stripping | Built — `includes/student_sites.php`, `students/serve.php`, `academy/mysite.php` |
| Publish workflow: unpublished by default, admin approves, any staff can take down, global kill switch | Built — `teach/pages.php` |
| Student portal, teacher portal, role checks | Built — `academy/`, `teach/`, `require_role()` |
| Assignments, submissions, marking, feedback, badges | Built — `academy/assignment.php`, `teach/marking.php` |
| Consent scope enforced in the query, not the form | Built — `teach/marking.php` |
| Append-only audit log with hashed IPs | Built — `includes/audit.php` |
| Dual-language machinery, English complete, Twi started | Built — `includes/i18n.php`, `lang/` |
| Aggregate programme report for funders | Built — `teach/report.php` |
| Coding activities under `lab/`, with a CSP scoped to that directory | Scaffolded — `lab/README.md` explains the install; the directory ships empty |
| Sponsor bridge: shareable work becoming a draft sponsor update | Built — `includes/sponsor_bridge.php`, `teach/updates.php`, `migrations/2026-09-28-sponsor-bridge.sql` |
| Learner ↔ journey link, administrators only | Built — `teach/learner.php` |
| Aggregate, non-identifying programme figures on the public impact page | Built — `impact.php` |
| Kolibri at the centre, offline service worker | **Not built** — no web development needed for Kolibri; see phase 5 |
| Moodle or Chamilo on a subdomain | **Not built**, and only if the programme outgrows the above |

Setup instructions are in `SETUP.md`. Funding and free resources are in
`docs/FUNDING_AND_FREE_RESOURCES.md`.

### Deviations from this design, and why

- **The editor uses plain textareas, not CodeMirror** (§6). A CodeMirror bundle
  is around 300 KB before any language mode, against roughly zero for a textarea
  with a monospace font and tab handling. On metered Accra mobile data, for
  children writing their first twenty lines of HTML, the textarea is the better
  trade. Revisit when there is a local mirror or the learners outgrow it.
- **Student pages are served from the same hostname** with
  `Content-Security-Policy: sandbox` giving them an opaque origin (§5.3), because
  a subdomain has not been confirmed on the hosting plan. Get the subdomain when
  you can; it is strictly stronger.
- **An optional coarse `gender` column was added** to `learners`, against the
  data-minimisation rule in §5. Gender-disaggregated participation is a
  near-universal funder requirement, it is optional, it is never displayed beside
  a learner's work, and it is only read in aggregate.
- **A generated draft never contains the learner's own words** (§10). Children
  write freely — a surname, a school, a street — so drafts are composed from
  structured facts about the lesson alone. What the learner wrote is shown to the
  administrator beside the draft, marked as context and not published, to draw on
  in their own words.

---

## 1. Constraints that decide the answer

| Constraint | Consequence |
| --- | --- |
| **Shared FTP hosting** (`ftps15.us.cloudlogin.co`, deployed by GitHub Action). PHP + MySQL only. No Docker, Node, shell, or root. | Anything needing Python/Django, Ruby, Node, or containers **cannot run on this host**: Open edX, Canvas, Kolibri-on-the-website, Judge0, Piston, the p5.js Web Editor. |
| **Learners are children.** | Separate identity plane, no public self-signup, no child↔sponsor contact, guardian consent. Ghana's Data Protection Act 2012 treats data on "a child under parental control" as **special personal data** needing explicit guardian consent, and requires registering as a data controller with the Data Protection Commission (renewed every two years). |
| **Students will author and publish web pages.** | Student HTML/JS served from the main origin could read sponsor session cookies. Origin isolation is not optional — see §5.3. |
| **Accra bandwidth and power are not reliable.** | Offline-capable, small payloads, no CDN dependencies, a local mirror at the center. A 20 MB editor that reloads each lesson is unusable on metered mobile data. |
| **Current CSP is strict**: `script-src 'self'`, no CDN JS, `frame-ancestors 'self'`. | Blockly, Snap!, and Scratch **generate and `eval` JavaScript** and will be blocked. Do not loosen the site-wide header — scope it per origin (§4.2). |
| **The site's credibility rests on its privacy posture** — pseudonymous journeys, staff review before anything reaches sponsors. | Learner work never auto-publishes. Everything crossing to the sponsor side goes through the existing admin review gate. |

---

## 2. Architecture options

### Option A — Build a lightweight Academy natively in this PHP app
Own tables, own pages, assignments wrapping self-hosted static coding tools.
**Pros:** runs on the current host as-is; no new infrastructure or admin skill; child
data stays in your database; clean join to `student_journeys`; same FTP deploy.
**Cons:** you build the ~20% of an LMS you need. No quiz engine, no mobile app.

### Option B — Adopt a full PHP LMS on a subdomain, SSO from this site
**Moodle** has a real REST API (`/webservice/rest/server.php`, token via
`/login/token.php`) so this site could pull progress into the sponsor dashboard — but
it is heavy, wants real cron, and is painful on budget shared hosting. **Chamilo** is
the same PHP/MySQL stack, materially lighter, and runs acceptably on shared hosting for
small cohorts. Either way you double the patching and backup burden, and neither ships
a code environment — you'd still embed the tools from §6. Neither gives students a
personal website, which is a core requirement here.

### Option C — Hybrid ✅ recommended
1. **Native Academy module** on this site — identity, onboarding, assignments, student
   web spaces, portals, sponsor bridge. Small, auditable, yours.
2. **Self-hosted static coding tools** on a sandbox origin — no server-side code
   execution anywhere.
3. **Kolibri on a local box at the center** (Raspberry Pi or spare laptop; MIT-licensed,
   offline-first, built for low-connectivity schools) — the offline content library and
   progress tracking that works when the internet doesn't. Kolibri is Python, so it runs
   *at the center*, not on the web host; its Data Portal aggregates usage synced upward.

This splits along the line reality already draws: the **website** serves sponsors,
guardians, and remote review; the **center's local network** is where kids actually work.

---

## 3. Four portals, four audiences

| Portal | Path | Who | Auth |
| --- | --- | --- | --- |
| **Public site** | `/` | Anyone | none |
| **Sponsor portal** *(exists)* | `/portal.php` | Donors | `users`, email + password |
| **Teacher portal** *(new)* | `/teach/` | Coaches, instructors | `users` with `role='teacher'` |
| **Admin portal** *(exists, extended)* | `/admin.php` | Foundation staff | `users` with `role='admin'` |
| **Student portal** *(new)* | `/academy/` | Learners | `learners`, username + PIN |

**Roles for adults, a separate table for children.** Staff and sponsors are both adults
with email, so add a role column to `users`. Children get their own table — a `role`
flag on a shared table is one forgotten `WHERE` clause away from a child appearing in a
donor list.

```sql
ALTER TABLE users
  ADD COLUMN role ENUM('sponsor','teacher','admin') NOT NULL DEFAULT 'sponsor';
UPDATE users SET role='admin' WHERE is_admin=1;   -- keep is_admin in sync, then retire it
```

```php
function require_role(string ...$allowed): array {   // includes/auth.php
    $id = require_user();
    $stmt = db()->prepare('SELECT id, role FROM users WHERE id=?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u || !in_array($u['role'], $allowed, true)) {
        http_response_code(403); exit('You do not have access to this area.');
    }
    return $u;
}
// admin implies teacher:  require_role('teacher','admin');
```

### What each portal does

**Student portal `/academy/`** — deliberately simple, big targets, works on a phone.
- My assignments (to do / submitted / reviewed, with teacher feedback)
- Open the coding tool for the current lesson
- **My Site** — edit and preview my web page (§5)
- My badges and certificates
- Language switcher, and a large, always-visible **Sign out** (shared lab machines)
- No messaging, no other learners' profiles, no sponsor information

**Teacher portal `/teach/`**
- Roster for my cohorts; **Onboard a student** (§4) with a printable welcome card
- Reset a PIN, deactivate an account
- Grade submissions, leave feedback, mark "needs work"
- Review student sites and **request publication** (teachers propose, admins approve)
- Propose work as shareable with sponsors (blocked if consent scope forbids it)
- Attendance and simple cohort progress

**Admin portal `/admin.php`** — everything a teacher can do, plus:
- Approve/revoke student site publication; global unpublish kill switch
- Approve draft sponsor updates (the existing review gate)
- Manage cohorts, modules, assignments, badges, staff accounts
- Guardian consent records and data-erasure requests
- **Audit log** — every adult action on a child's record, immutable (§4.4)

---

## 4. Automated student onboarding

One form. One submit. Everything the student needs exists afterwards.

### 4.1 The flow

```
Teacher opens /teach/onboard.php and enters:
    display name (first name or chosen nickname)   age band
    cohort                                          guardian consent date
    consent scope (learning only | learning + sponsor updates)
        |
        v  single transaction
   1. generate a unique, non-identifying slug        -> "kwesi-b4"
   2. INSERT learners                                -> account
   3. generate a one-time PIN, store password_hash() -> must_change_pin = 1
   4. create the student site record + storage dir   -> /students/kwesi-b4/ (unpublished)
   5. seed index.html / style.css from a template, greeting them by display name
   6. enrol in the cohort's default modules          -> first assignments appear
   7. write an audit_log entry
        |
        v
Printable welcome card (A6, cuts 4-up):
    "Kwesi — sign in at millcreek-ar-learning.com/academy
     Username: kwesi-b4   First-time PIN: 4821 (you will choose a new one)
     Your web page: lab.millcreek-ar-learning.com/students/kwesi-b4"
```

The PIN is shown **once**, on the card. It is never emailed, never redisplayed. A lost
PIN is reset in person by a teacher — which is also the correct identity check for a
child.

### 4.2 Slugs must not identify the child

`kwesi-b4` is a first name plus a cohort marker. Never surname, never initials plus
school, never a birthdate. The slug appears in a public URL, so treat it as public data
forever. Better still, let the learner choose a handle (`pixel-kwesi`, `codegirl-ama`)
at first sign-in — more dignified, more motivating, and less identifying.

### 4.3 Bulk onboarding

A CSV upload for a whole class (`display_name,age_band,cohort,consent_date,scope`),
previewed before commit, producing one PDF of welcome cards. A term intake of 30
students should take one teacher about five minutes.

### 4.4 Audit log

Non-negotiable once adults act on children's records.

```sql
CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT UNSIGNED NULL,       -- staff member
    actor_learner_id INT UNSIGNED NULL,    -- or the learner themselves
    action VARCHAR(60) NOT NULL,           -- learner.create, pin.reset, site.publish...
    subject_type VARCHAR(40) NOT NULL,
    subject_id INT UNSIGNED NULL,
    detail TEXT NULL,
    ip_hash CHAR(64) NULL,                 -- hashed, not raw
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_subject (subject_type, subject_id),
    INDEX idx_audit_time (created_at)
) ENGINE=InnoDB;
```

Append-only in practice: no UPDATE or DELETE paths in application code.

---

## 5. Student web spaces — `/students/<slug>/`

The motivating feature: *"this is my page, on the real internet, that I made."* It is
also the largest security and safeguarding surface in the whole design, so it gets the
most structure.

### 5.1 Storage layout

Files live **outside the web root**, keyed by slug:

```
<private storage>/student_sites/
    kwesi-b4/
        live/      index.html  style.css  script.js  assets/
        draft/     index.html  style.css  script.js  assets/
        revisions/ 2026-09-27T14-03-11.zip  ...        (last 20 kept)
```

`draft` is what the learner edits and previews. `live` is a snapshot copied on approval.
A learner editing cannot change what is already public — publication is always a
deliberate, staff-approved act.

### 5.2 Serving

**Recommended:** a router script, clean URLs via rewrite:

```apache
# .htaccess
RewriteRule ^students/([a-z0-9-]+)/?$        students/serve.php?s=$1&f=index.html  [L,QSA]
RewriteRule ^students/([a-z0-9-]+)/(.+)$     students/serve.php?s=$1&f=$2          [L,QSA]
```

`serve.php` resolves the slug, checks publication state (or an authenticated
learner/staff session for drafts), rejects any path containing `..`, whitelists the
extension, sets the right `Content-Type`, and streams the file with `readfile()`.

The alternative — real directories under the web root — is simpler but one
misconfiguration away from a student uploading `shell.php` and owning the server. If you
ever take that route, the directory needs its own `.htaccess` with PHP handling removed
(`RemoveHandler .php .phtml .php7 .php8` plus `php_flag engine off`), `Options -Indexes
-ExecCGI`, and uploads still whitelisted. The router is safer and host-independent.

### 5.3 Origin isolation — the important part

Student-authored HTML and JavaScript must not execute on the origin that holds sponsor
session cookies. Otherwise one student's `<script>` — or one attacker who compromises a
student PIN — can read the donor portal's DOM and cookies.

```
millcreek-ar-learning.com        sponsor + academy + teacher + admin  (cookies live here)
lab.millcreek-ar-learning.com    student sites + coding sandboxes     (NO cookies, ever)
```

If the hosting plan has no subdomain, the fallback is to serve every student page with

```
Content-Security-Policy: sandbox allow-scripts allow-forms;
X-Content-Type-Options: nosniff
```

The `sandbox` directive drops the response into an **opaque origin**, so its scripts
cannot reach cookies or same-origin data even though the hostname matches. Weaker than a
real second origin (still same-site for some purposes), but far better than nothing.
Get the subdomain if you can.

The academy's own CSP stays as strict as today's. The `lab.` origin carries its own,
looser policy allowing `'unsafe-eval'`, `blob:`, and `worker-src` for Blockly/Snap!, and
restricts `frame-ancestors` to the academy origin.

### 5.4 Editor

`/academy/mysite.php` — CodeMirror 6 (self-hosted, ~300 KB), tabs for HTML/CSS/JS, a
live preview in `<iframe sandbox="allow-scripts">` pointed at the draft, **Save**,
**Undo to a previous version**, and **Ask my teacher to publish**. No FTP, no file
manager, no upload of arbitrary types.

### 5.5 Limits, enforced server-side

- Extension whitelist: `html htm css js txt md json svg png jpg jpeg gif webp`
- Hard block: `php phtml php5 php7 php8 phar htaccess htpasswd cgi pl py sh exe`
- Filenames regenerated to `[a-z0-9._-]+`; never trust the uploaded name
- Quota ~5 MB and ~100 files per student; per-file cap ~1 MB
- Images re-encoded on upload (strips EXIF, including **GPS coordinates** — a real
  safeguarding risk when children upload phone photos)
- SVG either sanitized or excluded; SVG can carry script

### 5.6 Moderation and safeguarding

1. **Unpublished by default.** Publication requires a staff approval, always.
2. **Pre-publish automated check** flags, for teacher attention: phone numbers, email
   addresses, anything resembling a surname or a school name, street addresses,
   third-party `<script src>`, `<iframe>`, external form actions, links to social media.
   Advisory, not a gate — a human still reads the page.
3. **Snapshot every publish** so there is always a record of what was public, when.
4. **Kill switch:** one admin action unpublishes every student site.
5. **In-editor reminder**, in the learner's language: *"Never put your full name, your
   address, your phone number, or your school on your page."*
6. **`noindex` by default** (`X-Robots-Tag: noindex, nofollow`); search-engine indexing
   only per-site, deliberately, and probably never for under-13s.
7. **Guardian visibility:** guardians can see their child's page and ask for it to be
   taken down, at any time, no reason required.

```sql
CREATE TABLE student_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL UNIQUE,
    slug VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL DEFAULT 'My page',
    status ENUM('draft','pending_review','published','unpublished','suspended')
        NOT NULL DEFAULT 'draft',
    bytes_used INT UNSIGNED NOT NULL DEFAULT 0,
    file_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    published_by INT UNSIGNED NULL,
    last_edited_at DATETIME NULL,
    CONSTRAINT fk_site_learner FOREIGN KEY (learner_id)
        REFERENCES learners(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

---

## 6. Coding tools (all self-hostable as static files — no server runtime)

| Tool | License | Hosting | Size | Age | Notes |
| --- | --- | --- | --- | --- | --- |
| **Blockly Games** | Apache-2.0 | Official offline zip, ~3.8 MB compressed / 6.7 MB unzipped; unzip and serve. Explicitly built for intranet/USB distribution. | Tiny | 8–14 | **Best first win.** Puzzle→Maze→Bird→Turtle→Movie→Music→Pond is a full beginner ladder. Offline builds are **per-language** — pick the locale at download time. |
| **Snap!** (UC Berkeley) | AGPL-3.0 | Pure static JS; copy and serve. | ~5 MB | 10–18 | Scratch-like with real CS depth. Easiest "serious" block language to own outright. |
| **Scratch / TurboWarp** | BSD-3 / GPL-3.0 | `scratch-gui` needs a Node build; easier to use TurboWarp Desktop on lab machines or a single-file offline build. | 10s of MB | 8–16 | Highest recognition, biggest project ecosystem. Too heavy for mobile data — serve from the **local** center server. `.sb3` exports make clean submissions. |
| **CodeMirror 6 + sandboxed iframe** | MIT | Bundle once, serve statically. | ~300 KB | 12+ | Powers both web-dev assignments and the **My Site** editor. No server execution, so no sandbox escape reaches your database. |
| **Skulpt** (Python in browser) | MIT | Static JS. | ~1 MB | 12+ | Prefer over **Pyodide** (~10 MB+) purely on bandwidth, unless served locally. |
| **micro:bit MakeCode** | MIT | Self-hostable editor target; offline-capable. | Large | 10–16 | Only if you have the hardware — very high engagement when you do. |
| **H5P** | MIT | `h5p-standalone` renders `.h5p` packages with **no LMS and no H5P server**; `h5p/h5p-php-library` handles import/export. | Small | all | For the non-coding half: digital literacy, typing, online safety. |
| **CS Unplugged** | CC BY-SA | Printable PDFs. | — | 5–14 | Keeps a class running through a power cut. Genuinely valuable, not a consolation prize. |

**Be careful with:** GitHub/GitHub Classroom (terms require **13+**); public
scratch.mit.edu accounts (puts children into a global community — self-host instead);
Google Classroom API (needs Workspace for Education and a Google account per child);
Replit (free education tier withdrawn).

### API reality check

| Platform | API? | Usable here? |
| --- | --- | --- |
| **Moodle** | Yes — full REST web services, token auth, JSON. Disabled by default; each function must be added to a service and the token's user must hold the capability. | Only if you adopt Moodle. |
| **Chamilo** | Yes — web-service API, smaller surface. | Only if you adopt Chamilo. |
| **Kolibri** | Yes — REST API locally, plus Data Portal sync upward. | Yes — how center activity reaches the website. |
| **H5P** | Not an API; a content format + embeddable library. | Yes, embed directly. |
| **Blockly / Snap! / TurboWarp** | No server API; client-side. Exchange via `postMessage` and file export. | Yes — sufficient. |
| **Code.org** | No meaningful progress API. | Use the curriculum (CC BY-NC-SA), not the platform. |
| **Scratch (MIT)** | Unofficial endpoints only. | Avoid; self-host. |
| **GitHub Classroom** | Yes, but 13+ only. | Teens only, if at all. |
| **Judge0 / Piston** | Yes, but both want Docker to self-host, and **Piston's free public API closed to open use in February 2026** (educational access now by request). | Not needed — client-side execution covers the entire beginner curriculum. |

---

## 7. Dual-language support (English + Ghanaian languages)

### 7.1 Which languages

Ghana's education policy approves **eleven** Ghanaian languages alongside English:
Asante Twi, Akuapem Twi, Fante, Ewe, Ga, Dangme, Dagbani, Dagaare, Gonja, Kasem, Nzema.
Ghanaian language is the medium of instruction from KG to Primary 3, transitioning to
English from Primary 4 upward.

For a center in **Accra**, in priority order:

1. **English** — official language, language of instruction from P4, and the language of
   all programming documentation. Stays the default.
2. **Twi (Akan)** — the dominant lingua franca in Accra; the single highest-value
   second language.
3. **Ga** — indigenous to Accra; carries real cultural weight locally and signals that
   the center belongs to its community rather than visiting it.
4. **Ewe** — large community in Accra and the Volta Region.
5. **Hausa** — widely spoken in zongo communities; also the one Ghanaian-relevant
   language Scratch already supports (§7.5).

Start with **English + Twi**, structured so adding Ga and Ewe is a file drop.

### 7.2 Implementation

No framework needed — plain PHP arrays are enough, and they deploy over FTP like
everything else.

```
lang/
  en.php   return ['nav.home'=>'Home', 'academy.my_site'=>'My page', ...];
  tw.php   return ['nav.home'=>'Fie',  'academy.my_site'=>'Me krataafa', ...];
  gaa.php
  ee.php
```

```php
// includes/i18n.php
function current_lang(): string {
    $supported = ['en','tw','gaa','ee','ha'];
    foreach ([$_GET['lang'] ?? null, $_SESSION['lang'] ?? null,
              $_COOKIE['lang'] ?? null] as $c) {
        if ($c && in_array($c, $supported, true)) {
            $_SESSION['lang'] = $c;
            setcookie('lang', $c, ['expires'=>time()+31536000,'path'=>'/',
                                   'samesite'=>'Lax','secure'=>true]);
            return $c;
        }
    }
    return 'en';
}
function t(string $key, array $vars = []): string {
    static $strings = null, $fallback = null;
    if ($strings === null) {
        $fallback = require __DIR__ . '/../lang/en.php';
        $file = __DIR__ . '/../lang/' . current_lang() . '.php';
        $strings = is_file($file) ? require $file : $fallback;
    }
    $s = $strings[$key] ?? $fallback[$key] ?? $key;   // untranslated falls back to English
    foreach ($vars as $k => $v) $s = str_replace('{' . $k . '}', $v, $s);
    return $s;
}
```

Then `<?= htmlspecialchars(t('academy.my_site')) ?>`, `<html lang="<?= current_lang() ?>">`,
`hreflang` alternates in `<head>`, and a language switcher in the header that preserves
the current path.

### 7.3 Characters, fonts, input

- **The database is already correct** — `utf8mb4_unicode_ci` throughout handles
  `Ɛ ɛ Ɔ ɔ Ŋ ŋ Ɖ ɖ Ƒ ƒ Ɣ ɣ Ʋ ʋ` and Ewe's tone marks. Nothing to change.
- **Fonts:** use a family with full Latin Extended / African orthography coverage —
  Noto Sans, Gentium, or Charis SIL. **Self-host the font file**; the site otherwise
  avoids CDNs, and a missing glyph renders as a tofu box that makes the page look broken.
- **Input:** learners cannot type `Ɛ` or `Ɔ` on a standard keyboard or phone. Add a
  small character palette button beside text inputs that need it. This is the detail
  that decides whether local-language text actually gets typed.

### 7.4 What to translate, and what not to

**Translate:** all UI chrome, assignment briefs for younger learners, safety and privacy
reminders, badges and certificates, the guardian consent form, guardian reports, and the
public pages a family might read.

**Do not translate:** programming keywords, HTML tags, CSS properties. Every piece of
documentation and every job the student may later apply for uses the English terms.
Teaching `<paragraph>` would be a disservice.

**The consent form is the highest-value translation of all.** Consent given in a
language the guardian does not read is not informed consent — legally or ethically.
Translate it before translating the UI.

Have local staff translate and review. Do not ship machine translation into a
children's program; a clumsy Twi UI reads as carelessness and undermines trust faster
than English-only ever would.

### 7.5 Tool localization — a real opportunity

Verified against Scratch's supported-locale list: Scratch ships **Hausa**, Swahili,
Zulu, Amharic, and Afrikaans — but **no Twi/Akan, no Ga, no Ewe**. Blockly Games ships
many locales and its offline builds are per-language.

That gap is an asset. Scratch translations are community-contributed through Transifex.
**Senior students translating Scratch's interface into Twi** is an authentic
open-source contribution, a portfolio piece, a genuine service to every Ghanaian child
who uses Scratch afterwards, and exactly the kind of story sponsors want to hear. Put it
in the advanced module.

### 7.6 Oral-first for younger learners

Reading fluency varies. Short recorded voice instructions in Twi or Ga for the youngest
learners (a phone recording is fine) will do more than any amount of translated text —
and audio files are small enough to work offline.

---

## 8. Social and cultural fit for Ghana

These are the points most likely to cause avoidable harm or offence. Validate every one
with the center's leadership in Accra; treat this section as questions to ask, not
conclusions to adopt.

### 8.1 Language of the donor side

**Rename "adopt."** The file `adopt.php` and any "adopt a student" phrasing should go.
In Ghana, as elsewhere, *adoption* means legally taking someone's child. Applied to a
sponsorship it reads as ownership of a child by a foreigner, and it is the single
fastest way to lose a community's goodwill. The navigation already says *"Sponsor a
journey"* — finish the job and rename the route to `sponsor.php` with a 301 redirect.

### 8.2 Framing: investment, not rescue

Ghanaian families are acutely aware of being portrayed as needy for a foreign audience.
Frame the program as **investment in talent that already exists**, not rescue.

- Show capability: students at work, finished projects, real equipment.
- Avoid poverty-signalling imagery — bare feet, dirt, sad faces, "before/after."
- Let students' own work speak. A screenshot of a page a child built says more to a
  sponsor than any photograph of that child, and it carries no safeguarding risk.
- Retire the `Student A / Student B / Student C` placeholders in favour of
  learner-chosen handles. A pseudonym someone picked is dignified; a letter is a case number.

### 8.3 Dignity in the sponsor bridge

The learner is a person with achievements, not a subject being reported on. Show
learners, age-appropriately, what is shared about them, and let them opt out of an
individual update without losing their place in the program.

### 8.4 Guardians and community

- A **printed, signed termly report** for each guardian. Print matters here; not every
  family has a smartphone or data.
- Open days where guardians see the work, and their child's page, on a screen.
- Brief the PTA, community leaders, and faith leaders before launch, not after.
- A local advisory group with real authority over content and imagery decisions.

### 8.5 Religion

Ghana has a large Christian majority and a significant Muslim population, and faith is
central to daily life for most families.

- Schedule around **Sunday services** and **Friday Jumu'ah**; be aware of Ramadan.
- Keep curriculum content faith-neutral and welcoming to both.
- Review stock imagery, game assets, and any global content gallery for material that
  would be unwelcome — occult or Halloween themes, dating or romance content, alcohol.
  Self-hosting the tools is what makes this curation possible at all.

### 8.6 Respect and address

Titles carry weight — *Sir*, *Madam*, *Auntie*, *Uncle*, *Teacher*. Interface copy
should be warm but respectful; avoid American slang and over-familiar tone, which reads
as disrespect rather than friendliness. Have local staff review every string.

### 8.7 Girls in the program

Track and publish gender participation. Consider dedicated sessions, female mentors, and
guardian outreach explaining why a daughter's time at a computer is worth the trade
against household work. If participation is skewed, say so publicly and address it.

### 8.8 Recognition

Printed, signed, stamped **certificates** are highly valued and cost almost nothing.
Make them a first-class feature: PDF generation, a term-end ceremony, a certificate the
family can frame. This will motivate attendance more than any badge on a screen.

### 8.9 Curriculum credibility

Map modules to the **Ghana Education Service ICT/computing curriculum** and say so
plainly. Parents and schools will evaluate the program by whether it helps with school
and with work. A visible mapping converts the center from "extra activity" to
"supports my child's education."

### 8.10 Practical realities

School hours and exam terms; household chores and market days; harmattan dust and the
rainy season; **power cuts** (a UPS for the local server, and offline activities ready);
shared devices; data costs; and the fact that much of the typing may happen on phones.
Offline-first is a cultural requirement here, not just a technical one.

---

## 9. Data model (core)

```sql
CREATE TABLE learners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_journey_id INT UNSIGNED NULL,   -- private link to the public journey
    username VARCHAR(40) NOT NULL UNIQUE,   -- staff-issued slug, e.g. "kwesi-b4"
    display_name VARCHAR(60) NOT NULL,      -- first name or chosen handle ONLY
    age_band VARCHAR(40) NOT NULL,          -- band, never a date of birth
    preferred_lang VARCHAR(5) NOT NULL DEFAULT 'en',
    pin_hash VARCHAR(255) NOT NULL,         -- password_hash(), staff-resettable
    must_change_pin TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    cohort_id INT UNSIGNED NULL,
    guardian_consent_on DATE NULL,          -- no consent -> no account
    consent_scope ENUM('learning_only','learning_and_sponsor_updates')
        NOT NULL DEFAULT 'learning_only',
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_learner_journey FOREIGN KEY (student_journey_id)
        REFERENCES student_journeys(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cohorts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    term VARCHAR(40) NOT NULL,
    coach_user_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    summary TEXT NOT NULL,
    tool ENUM('blockly','snap','scratch','webdev','python','h5p','unplugged') NOT NULL,
    tool_url VARCHAR(255) NULL,          -- path on the lab origin
    ges_curriculum_ref VARCHAR(80) NULL, -- §8.9
    sort_order INT NOT NULL DEFAULT 0,
    published TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    brief TEXT NOT NULL,
    submission_type ENUM('file','code','screenshot','reflection','site') NOT NULL,
    due_on DATE NULL,
    CONSTRAINT fk_assignment_module FOREIGN KEY (module_id)
        REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    learner_id INT UNSIGNED NOT NULL,
    body MEDIUMTEXT NULL,              -- pasted code or reflection
    file_path VARCHAR(255) NULL,       -- stored OUTSIDE the web root
    status ENUM('draft','submitted','reviewed','needs_work') NOT NULL DEFAULT 'draft',
    teacher_feedback TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    shareable TINYINT(1) NOT NULL DEFAULT 0,   -- teacher proposes; admin approves
    submitted_at DATETIME NULL,
    CONSTRAINT fk_submission_assignment FOREIGN KEY (assignment_id)
        REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_learner FOREIGN KEY (learner_id)
        REFERENCES learners(id) ON DELETE CASCADE,
    INDEX idx_submission_learner (learner_id, status)
) ENGINE=InnoDB;

CREATE TABLE badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(80) NOT NULL,
    icon VARCHAR(120) NOT NULL,
    criteria TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE learner_badges (
    learner_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    awarded_on DATETIME NOT NULL,
    PRIMARY KEY (learner_id, badge_id)
) ENGINE=InnoDB;
```

Plus `student_sites` (§5.6) and `audit_log` (§4.4).

**Uploads** go outside the web root (or into a directory with `Require all denied`) and
are served only through a session-checked PHP script. An `.sb3` is a ZIP — never unpack
it on the server.

### Child account rules that follow

1. **No public signup.** Staff create accounts from a roster. `/academy/` offers no
   registration link, ever.
2. **No email required.** Username + PIN, reset in person. This removes the entire
   email-based account-takeover surface.
3. **No child↔sponsor channel.** No messaging, no comments, no sponsor identity visible
   to learners. One-directional and staff-mediated, always.
4. **Data minimization.** Age band, not birthdate. Display name, not surname. No
   address, no phone, no photo inside the learner app.
5. **Consent gates sharing.** `consent_scope='learning_only'` means work can never
   become a sponsor update — enforce it in the SQL, not the UI.
6. **Shared-lab hygiene.** ~30-minute idle timeout, no "remember me", a large visible
   **Sign out**, session cookie scoped to `/academy/`.
7. **Rate-limit and lock PIN attempts.** PINs are low-entropy by design; this is the
   whole defense. Staff unlock in person.
8. **Right to erasure**, documented and implemented: the learner, their submissions,
   their site, and their revisions.

---

## 10. The sponsor bridge

```
learner completes assignment
   -> teacher marks it reviewed and ticks "shareable"
        consent checked in the query, not the form: a learner marked
        learning_only cannot be shared however the form posts
   -> draft student_update generated  (status='draft', visible=0)
        composed from lesson facts only, never the learner's own text
   -> administrator rewrites and approves in teach/updates.php
        consent checked AGAIN here, in case a guardian withdrew it
   -> status='approved', visible=1: the sponsor dashboard shows it
```

Publishing a learner's web page for the first time drafts a milestone the same
way. Both sources are deduplicated, so a piece of work can only ever produce one
update, and a discarded draft is not regenerated.

Three things must all hold before anything can be drafted:

1. **Guardian consent** is `learning_and_sponsor_updates`.
2. **The learner is linked to a student journey.** That link is the sensitive
   join between a real child and a pseudonymous public profile, so only an
   administrator may set it, and one journey belongs to one learner.
3. **A teacher proposed the work.** Nothing is drafted automatically from
   ordinary marking.

Work that a teacher proposed but which fails 1 or 2 appears in a "held back"
list on the approval page, saying which of the two is missing, so it is visible
rather than silently dropped.

An administrator can also take an approved update back down at any time; it is
marked withdrawn and disappears from the sponsor dashboard, with the record kept.

Sponsors currently read hand-written updates. This makes real classroom progress their
source while keeping every existing control: first names only, no surnames, no faces
without consent, nothing published unreviewed. Aggregate anonymous figures can also feed
the public impact page — *"47 projects published this term"* — with no individual data
at all.

---

## 11. Phased roadmap

**Phase 1 — Identity and portals**
`role` on `users`; `require_role()`; `learners`, `cohorts`, `audit_log`; `/academy/`
login (username + PIN, forced PIN change); `/teach/` with roster and one-click
onboarding + printable welcome cards; student dashboard. No coding tools yet — prove the
account plumbing and the audit trail first.

**Phase 2 — Student web spaces**
`student_sites`; private storage; `students/serve.php` with clean URLs; the CodeMirror
editor with live preview; quotas and the extension whitelist; the publish→review→live
workflow; the automated pre-publish scan; the kill switch. Ship the `lab.` origin here.

**Phase 3 — Assignments and coding tools**
`modules`, `assignments`, `submissions`; Blockly Games self-hosted on `lab.`; file and
code submissions; the teacher grading loop; badges; printable certificates.

**Phase 4 — Language**
`lang/en.php` and `lang/tw.php`; `t()` throughout; the switcher; the character palette;
self-hosted font. Translate the **consent form and guardian materials first**. Add Ga
and Ewe as capacity allows.

**Phase 5 — Offline at the center**
Kolibri on a Pi or spare laptop on the center LAN; a local mirror of the sandbox tools;
a service worker so `/academy/` loads and queues submissions offline.

**Phase 6 — The sponsor bridge**
`shareable` → draft update → existing admin approval → sponsor dashboard; aggregate
stats on the impact page.

**Phase 7 — Only if the program outgrows all of the above**
Chamilo or Moodle on `learn.` for quizzes and gradebook, SSO from this site, progress
pulled back over its REST API.

---

## 12. Before any of this ships

- [ ] Guardian consent form — **in the family's language** — covering the account, the
      data held, the public web page, and (separately) sharing work with sponsors.
      No consent, no account.
- [ ] Register as a data controller with Ghana's Data Protection Commission and confirm
      current obligations for children's data. Verify with local counsel; do not rely on
      this document.
- [ ] Add a children's-data section to `privacy.php` — collection, purpose, retention,
      erasure, and who can see what. The current policy covers sponsors only.
- [ ] Safeguarding policy with the center's leadership and the AD2 Alumni Foundation:
      who may view learner work, how accounts are issued and revoked, what happens when
      a learner leaves, and who takes a page down out of hours.
- [ ] **Confirm a subdomain is available** on the hosting plan (§5.3). If not, the
      opaque-origin CSP fallback becomes mandatory and should be tested before launch.
- [ ] Confirm PHP version, `upload_max_filesize`, `post_max_size`, and whether
      `.htaccess` overrides are honoured.
- [ ] Rename `adopt.php` → `sponsor.php` with a 301 (§8.1).
- [ ] Local review of every user-facing string by Accra staff, in both languages.

---

## Sources

- [Kolibri (Learning Equality)](https://learningequality.org/kolibri/) · [kolibri on GitHub](https://github.com/learningequality/kolibri)
- [Moodle: Using web services](https://docs.moodle.org/502/en/Using_web_services) · [Moodle External Services](https://moodledev.io/docs/5.0/apis/subsystems/external)
- [Blockly Games offline wiki](https://github.com/google/blockly-games/wiki/offline)
- [TurboWarp Desktop](https://desktop.turbowarp.org/) · [TurboWarp offline docs](https://docs.turbowarp.org/packager/offline)
- [h5p-standalone](https://github.com/tunapanda/h5p-standalone) · [h5p-php-library](https://github.com/h5p/h5p-php-library)
- [Scratch supported locales (scratch-l10n)](https://github.com/scratchfoundation/scratch-l10n/blob/master/src/supported-locales.mjs) · [How to Translate Scratch](https://en.scratch-wiki.info/wiki/How_to_Translate_Scratch)
- [Piston code execution engine](https://github.com/engineer-man/piston) · [Judge0 CE](https://ce.judge0.com/)
- [Ghana Data Protection Act 2012 (DLA Piper)](https://www.dlapiperdataprotection.com/index.html?t=law&c=GH) · [Data Protection Africa: Ghana](https://dataprotection.africa/ghana/)
- [Ghana Education Ministry: language of instruction policy](https://www.ghanaweb.com/GhanaHomePage/NewsArchive/Education-Ministry-clarifies-language-of-instruction-policy-for-basic-schools-2046047) · [Ghana's school language policy (The Conversation)](https://theconversation.com/why-ghana-is-struggling-to-get-its-language-policy-right-in-schools-120814)
- [Chamilo vs Moodle on PHP/MySQL hosting](https://gurkhatech.com/php-mysql-self-hosted-lms-options/)
