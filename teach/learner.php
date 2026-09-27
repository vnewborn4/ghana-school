<?php
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/onboarding.php';
require_once __DIR__ . '/../includes/student_sites.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');
$learnerId = (int)($_GET['id'] ?? 0);

$load = function (int $id) {
    $stmt = db()->prepare(
        'SELECT l.*, c.name AS cohort_name, ss.slug AS site_slug, ss.status AS site_status,
                j.first_name AS journey_name, j.public_code AS journey_code
         FROM learners l
         LEFT JOIN cohorts c ON c.id=l.cohort_id
         LEFT JOIN student_sites ss ON ss.learner_id=l.id
         LEFT JOIN student_journeys j ON j.id=l.student_journey_id
         WHERE l.id=?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
};

$learner = $load($learnerId);
if (!$learner) { http_response_code(404); exit('That learner was not found.'); }

$newPin = null; $flash = null; $flashKind = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    switch ($_POST['action'] ?? '') {
        case 'reset_pin':
            $newPin = reset_learner_pin($learnerId, (int)$user['id']);
            $flash = 'A new first-time PIN has been issued. It is shown once, below.';
            break;

        case 'unlock':
            db()->prepare('UPDATE learners SET failed_attempts=0, locked_until=NULL WHERE id=?')->execute([$learnerId]);
            audit('learner.unlock', ['actor_user_id' => (int)$user['id'], 'subject_type' => 'learner', 'subject_id' => $learnerId]);
            $flash = 'Account unlocked.';
            break;

        case 'set_active':
            $active = (int)($_POST['active'] ?? 0) === 1 ? 1 : 0;
            db()->prepare('UPDATE learners SET active=? WHERE id=?')->execute([$active, $learnerId]);
            audit($active ? 'learner.reactivate' : 'learner.deactivate',
                  ['actor_user_id' => (int)$user['id'], 'subject_type' => 'learner', 'subject_id' => $learnerId]);
            $flash = $active ? 'Account reactivated.' : 'Account deactivated. The learner can no longer sign in and their page is offline.';
            break;

        case 'set_consent':
            $scope = ($_POST['consent_scope'] ?? '') === 'learning_and_sponsor_updates'
                ? 'learning_and_sponsor_updates' : 'learning_only';
            $date = trim((string)($_POST['guardian_consent_on'] ?? ''));
            $date = ($date !== '' && strtotime($date) !== false) ? $date : null;
            db()->prepare('UPDATE learners SET consent_scope=?, guardian_consent_on=? WHERE id=?')
                ->execute([$scope, $date, $learnerId]);
            audit('learner.consent_update', [
                'actor_user_id' => (int)$user['id'], 'subject_type' => 'learner', 'subject_id' => $learnerId,
                'detail' => 'scope=' . $scope . ' date=' . ($date ?? 'none'),
            ]);
            $flash = 'Consent record updated.';
            break;

        case 'set_journey':
            // The link between a real child and their pseudonymous public
            // profile is the most sensitive relationship in the system, so
            // only an administrator may set or clear it.
            if (!is_admin_user($user)) { http_response_code(403); exit('Only an administrator can link a learner to a sponsor journey.'); }
            $journeyId = (int)($_POST['student_journey_id'] ?? 0) ?: null;
            if ($journeyId !== null) {
                $taken = db()->prepare('SELECT display_name FROM learners WHERE student_journey_id=? AND id<>?');
                $taken->execute([$journeyId, $learnerId]);
                $other = $taken->fetchColumn();
                if ($other !== false) {
                    $flash = 'That journey is already linked to ' . $other . '. Unlink it there first.';
                    $flashKind = 'bad';
                    break;
                }
            }
            db()->prepare('UPDATE learners SET student_journey_id=? WHERE id=?')->execute([$journeyId, $learnerId]);
            audit('learner.journey_link', [
                'actor_user_id' => (int)$user['id'], 'subject_type' => 'learner',
                'subject_id' => $learnerId, 'detail' => 'journey=' . ($journeyId ?? 'none'),
            ]);
            $flash = $journeyId ? 'Linked to a sponsor journey.' : 'Unlinked from the sponsor journey.';
            break;

        case 'erase':
            if (!is_admin_user($user)) { http_response_code(403); exit('Only an administrator can erase a learner record.'); }
            $slug = $learner['username'];
            db()->prepare('DELETE FROM learners WHERE id=?')->execute([$learnerId]);
            site_erase($slug);
            audit('learner.erase', [
                'actor_user_id' => (int)$user['id'], 'subject_type' => 'learner',
                'subject_id' => $learnerId, 'detail' => 'username=' . $slug,
            ]);
            header('Location: ' . app_url('teach/index.php'));
            exit;
    }
    $learner = $load($learnerId);
}

$stmt = db()->prepare(
    'SELECT s.*, a.title AS assignment_title, m.title AS module_title
     FROM submissions s
     JOIN assignments a ON a.id=s.assignment_id
     JOIN modules m ON m.id=a.module_id
     WHERE s.learner_id=? ORDER BY s.updated_at DESC'
);
$stmt->execute([$learnerId]);
$submissions = $stmt->fetchAll();

$cohorts = db()->query('SELECT id, name, term FROM cohorts WHERE active=1 ORDER BY name')->fetchAll();
$locked  = !empty($learner['locked_until']) && strtotime($learner['locked_until']) > time();

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = $learner['display_name'];
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <p><a href="<?= app_url('teach/index.php') ?>">&larr; Roster</a></p>
    <h1><?= htmlspecialchars($learner['display_name']) ?></h1>
    <p><code><?= htmlspecialchars($learner['username']) ?></code>
       &middot; <?= htmlspecialchars($learner['cohort_name'] ?? 'no class') ?>
       &middot; <?= htmlspecialchars($learner['age_band'] ?: 'no age band') ?>
       &middot; <?= $learner['journey_name']
            ? 'journey: ' . htmlspecialchars($learner['journey_name'])
            : 'no sponsor journey' ?></p>
</div>

<?php if ($flash): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<?php if ($newPin !== null): ?>
<div class="academy-card">
    <h2>New first-time PIN</h2>
    <p style="font-size:2rem;font-family:ui-monospace,Menlo,monospace;letter-spacing:.3em"><?= htmlspecialchars($newPin) ?></p>
    <p class="field-hint">Write it down and hand it over in person. It is stored as a hash and
       cannot be shown again. The learner must choose their own PIN at next sign-in.</p>
</div>
<?php endif; ?>

<?php if ((int)$learner['active'] !== 1): ?>
    <div class="notice notice-warn">This account is deactivated. The learner cannot sign in and their page is offline.</div>
<?php endif; ?>
<?php if (empty($learner['guardian_consent_on'])): ?>
    <div class="notice notice-bad">No guardian consent date is recorded for this learner. Record it or deactivate the account.</div>
<?php endif; ?>

<div class="academy-card">
    <h2>Account</h2>
    <div class="button-row-wrap">
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset_pin">
            <button class="button-big secondary" type="submit">Reset PIN</button>
        </form>

        <?php if ($locked): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="unlock">
            <button class="button-big gold" type="submit">Unlock account</button>
        </form>
        <?php endif; ?>

        <form method="post" data-confirm="<?= (int)$learner['active'] === 1 ? 'Deactivate this account?' : 'Reactivate this account?' ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="set_active">
            <input type="hidden" name="active" value="<?= (int)$learner['active'] === 1 ? 0 : 1 ?>">
            <button class="button-big secondary" type="submit"><?= (int)$learner['active'] === 1 ? 'Deactivate' : 'Reactivate' ?></button>
        </form>

        <?php if (!empty($learner['site_slug'])): ?>
            <a class="button-big secondary" href="<?= app_url('students/preview/' . $learner['site_slug'] . '/') ?>" target="_blank" rel="noopener">View their page</a>
        <?php endif; ?>
    </div>
    <p class="field-hint" style="margin-top:12px">
        Last signed in: <?= $learner['last_login_at'] ? htmlspecialchars(date('j M Y H:i', strtotime($learner['last_login_at']))) : 'never' ?>
        <?php if ($locked): ?> &middot; locked until <?= htmlspecialchars(date('H:i', strtotime($learner['locked_until']))) ?><?php endif; ?>
    </p>
</div>

<div class="academy-card">
    <h2>Guardian consent</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="set_consent">
        <div class="field">
            <label for="guardian_consent_on">Date on the signed form</label>
            <input id="guardian_consent_on" name="guardian_consent_on" type="date" max="<?= date('Y-m-d') ?>"
                   value="<?= htmlspecialchars((string)$learner['guardian_consent_on']) ?>">
        </div>
        <div class="field">
            <label for="consent_scope">What the guardian agreed to</label>
            <select id="consent_scope" name="consent_scope">
                <option value="learning_only" <?= $learner['consent_scope'] === 'learning_only' ? 'selected' : '' ?>>Learning only</option>
                <option value="learning_and_sponsor_updates" <?= $learner['consent_scope'] === 'learning_and_sponsor_updates' ? 'selected' : '' ?>>Learning, and reviewed work may become a sponsor update</option>
            </select>
        </div>
        <button class="button-big secondary" type="submit">Save consent record</button>
    </form>
</div>

<?php if (is_admin_user($user)): ?>
<div class="academy-card">
    <h2>Sponsor journey</h2>
    <p class="field-hint">Links this learner to the pseudonymous public profile a sponsor
       follows. Without it their work can never become a sponsor update, however the
       consent record reads. One journey, one learner.</p>
    <?php
    $journeys = db()->query(
        'SELECT j.id, j.first_name, j.public_code,
                (SELECT COUNT(*) FROM learners l2 WHERE l2.student_journey_id=j.id) AS linked
         FROM student_journeys j WHERE j.active=1 ORDER BY j.first_name'
    )->fetchAll();
    ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="set_journey">
        <div class="field">
            <label for="student_journey_id">Journey</label>
            <select id="student_journey_id" name="student_journey_id">
                <option value="0">Not linked</option>
                <?php foreach ($journeys as $journey): ?>
                    <?php $isMine = (int)$journey['id'] === (int)$learner['student_journey_id']; ?>
                    <option value="<?= (int)$journey['id'] ?>" <?= $isMine ? 'selected' : '' ?>
                            <?= (!$isMine && (int)$journey['linked'] > 0) ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($journey['first_name'] . ' (' . $journey['public_code'] . ')') ?><?= (!$isMine && (int)$journey['linked'] > 0) ? ' — already linked' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button-big secondary" type="submit">Save journey link</button>
    </form>
    <?php if ($learner['consent_scope'] !== 'learning_and_sponsor_updates'): ?>
        <p class="field-hint" style="margin-top:12px"><strong>Note:</strong> this learner's guardian
           agreed to learning only, so no sponsor update can be created even once a journey is linked.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="academy-card">
    <h2>Work</h2>
    <?php if (!$submissions): ?>
        <p>Nothing submitted yet.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Lesson</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($submissions as $submission): ?>
            <tr>
                <td><?= htmlspecialchars($submission['assignment_title']) ?><br><small><?= htmlspecialchars($submission['module_title']) ?></small></td>
                <td><span class="pill <?= $submission['status'] === 'reviewed' ? 'pill-done' : ($submission['status'] === 'submitted' ? 'pill-waiting' : 'pill-off') ?>"><?= htmlspecialchars(str_replace('_', ' ', $submission['status'])) ?></span></td>
                <td><?= htmlspecialchars(date('j M Y', strtotime($submission['updated_at']))) ?></td>
                <td><a href="<?= app_url('teach/marking.php?id=' . (int)$submission['id']) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if (is_admin_user($user)): ?>
<div class="academy-card">
    <h2>Erase this learner</h2>
    <p class="field-hint">Removes the account, every submission, the web page, and all saved
       revisions. This cannot be undone. Use it for a guardian's erasure request. The audit
       log keeps a record that an erasure happened, without the learner's content.</p>
    <form method="post" data-confirm="Permanently erase this learner and all of their work? This cannot be undone.">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="erase">
        <button class="button-big" type="submit" style="background:#8d3c1c">Erase permanently</button>
    </form>
</div>
<?php endif; ?>

<script defer src="<?= app_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
