<?php
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/onboarding.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');

$errors = [];
$created = [];

$cohorts = db()->query('SELECT id, name, term FROM cohorts WHERE active=1 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = ($_POST['mode'] ?? 'single') === 'bulk' ? 'bulk' : 'single';
    $cohortId = (int)($_POST['cohort_id'] ?? 0);

    $rows = $mode === 'bulk'
        ? parse_roster_rows((string)($_POST['roster'] ?? ''))
        : [[
            'line'                => 1,
            'display_name'        => (string)($_POST['display_name'] ?? ''),
            'age_band'            => (string)($_POST['age_band'] ?? ''),
            'guardian_consent_on' => (string)($_POST['guardian_consent_on'] ?? ''),
            'consent_scope'       => (string)($_POST['consent_scope'] ?? 'learning_only'),
            'gender'              => (string)($_POST['gender'] ?? 'not_recorded'),
          ]];

    if (!$rows) $errors[] = 'No rows were found to add.';

    foreach ($rows as $row) {
        $row['cohort_id'] = $cohortId;
        $row['preferred_lang'] = (string)($_POST['preferred_lang'] ?? 'en');
        try {
            $created[] = onboard_learner($row, (int)$user['id']);
        } catch (InvalidArgumentException $e) {
            $errors[] = 'Row ' . $row['line'] . ' (' . htmlspecialchars($row['display_name'] ?: 'no name') . '): ' . $e->getMessage();
        } catch (Throwable $e) {
            $errors[] = 'Row ' . $row['line'] . ': could not be added. Please try again.';
        }
    }

    if ($created) {
        // Hand the one-time PINs to the printable cards page, then forget them.
        $_SESSION['onboarded'] = $created;
        header('Location: ' . app_url('teach/cards.php'));
        exit;
    }
}

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Onboard students';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Onboard students</h1>
    <p>One submission creates the account, the first-time PIN, and the student's own web page.</p>
</div>

<?php if ($errors): ?>
    <div class="notice notice-bad">
        <?php foreach ($errors as $error): ?><p><?= $error ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="notice notice-warn">
    <strong>Guardian consent comes first.</strong> Do not create an account before a guardian
    has signed the consent form, in a language they read. Enter the date on that form below.
    Use a first name or a nickname only &mdash; never a surname.
</div>

<div class="academy-card">
    <h2>One student</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="mode" value="single">

        <div class="field">
            <label for="display_name">First name or nickname</label>
            <input id="display_name" name="display_name" type="text" required maxlength="60">
            <p class="field-hint">This is shown on their page and to their teacher. No surnames.</p>
        </div>

        <div class="field">
            <label for="age_band">Age band</label>
            <select id="age_band" name="age_band">
                <option value="8-10">8-10</option>
                <option value="11-12">11-12</option>
                <option value="13-14">13-14</option>
            </select>
            <p class="field-hint">A band, never a date of birth.</p>
        </div>

        <div class="field">
            <label for="cohort_id">Class</label>
            <select id="cohort_id" name="cohort_id">
                <option value="0">No class yet</option>
                <?php foreach ($cohorts as $cohort): ?>
                    <option value="<?= (int)$cohort['id'] ?>"><?= htmlspecialchars($cohort['name'] . ($cohort['term'] !== '' ? ' — ' . $cohort['term'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="gender">Gender (optional)</label>
            <select id="gender" name="gender">
                <option value="not_recorded">Prefer not to record</option>
                <option value="girl">Girl</option>
                <option value="boy">Boy</option>
                <option value="other">Other</option>
            </select>
            <p class="field-hint">Recorded only so participation can be reported in aggregate to
               funders, who almost always ask. It is never shown beside a learner's work. Leave it
               unrecorded if your consent form does not cover it.</p>
        </div>

        <div class="field">
            <label for="preferred_lang">Language</label>
            <select id="preferred_lang" name="preferred_lang">
                <?php foreach (supported_langs() as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="guardian_consent_on">Date on the signed guardian consent form</label>
            <input id="guardian_consent_on" name="guardian_consent_on" type="date" required
                   max="<?= date('Y-m-d') ?>">
        </div>

        <div class="field">
            <label for="consent_scope">What the guardian agreed to</label>
            <select id="consent_scope" name="consent_scope">
                <option value="learning_only">Learning only — work is never shared with sponsors</option>
                <option value="learning_and_sponsor_updates">Learning, and reviewed work may become a sponsor update</option>
            </select>
            <p class="field-hint">This is enforced in the database. "Learning only" work can never become a sponsor update, whatever anyone ticks later.</p>
        </div>

        <button class="button-big" type="submit">Create account and web page</button>
    </form>
</div>

<div class="academy-card">
    <h2>A whole class</h2>
    <p class="field-hint">
        One student per line:
        <code>first name, age band, consent date (YYYY-MM-DD), scope, gender</code><br>
        Scope and gender are optional. A header row is ignored.
    </p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="mode" value="bulk">

        <div class="field">
            <label for="cohort_id_bulk">Class for all of these students</label>
            <select id="cohort_id_bulk" name="cohort_id">
                <option value="0">No class yet</option>
                <?php foreach ($cohorts as $cohort): ?>
                    <option value="<?= (int)$cohort['id'] ?>"><?= htmlspecialchars($cohort['name'] . ($cohort['term'] !== '' ? ' — ' . $cohort['term'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="roster">Roster</label>
            <textarea id="roster" name="roster" spellcheck="false"
placeholder="Ama, 8-10, 2026-09-14
Kwesi, 11-12, 2026-09-14
Adjoa, 11-12, 2026-09-15, learning_and_sponsor_updates"></textarea>
        </div>

        <button class="button-big" type="submit">Create all accounts</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
