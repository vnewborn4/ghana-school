<?php
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/academy_nav.php';
require_once __DIR__ . '/../includes/sponsor_bridge.php';

$user = require_role('teacher');

$cohortFilter = (int)($_GET['cohort'] ?? 0);
$sql = 'SELECT l.*, c.name AS cohort_name, ss.slug AS site_slug, ss.status AS site_status,
               (SELECT COUNT(*) FROM submissions s WHERE s.learner_id=l.id AND s.status=\'submitted\') AS awaiting,
               (SELECT COUNT(*) FROM submissions s WHERE s.learner_id=l.id AND s.status=\'reviewed\')  AS completed
        FROM learners l
        LEFT JOIN cohorts c ON c.id=l.cohort_id
        LEFT JOIN student_sites ss ON ss.learner_id=l.id';
$params = [];
if ($cohortFilter > 0) { $sql .= ' WHERE l.cohort_id=?'; $params[] = $cohortFilter; }
$sql .= ' ORDER BY l.active DESC, l.display_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$learners = $stmt->fetchAll();

$cohorts = db()->query('SELECT id, name, term FROM cohorts ORDER BY active DESC, name')->fetchAll();
$pendingPages = (int)db()->query('SELECT COUNT(*) FROM student_sites WHERE status=\'pending_review\'')->fetchColumn();
$pendingWork  = (int)db()->query('SELECT COUNT(*) FROM submissions WHERE status=\'submitted\'')->fetchColumn();
$pendingDrafts = is_admin_user($user) ? bridge_pending_count() : 0;

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Roster';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Roster</h1>
    <p><?= count($learners) ?> learner<?= count($learners) === 1 ? '' : 's' ?>
       &middot; <?= $pendingWork ?> piece<?= $pendingWork === 1 ? '' : 's' ?> of work to mark
       &middot; <?= $pendingPages ?> page<?= $pendingPages === 1 ? '' : 's' ?> to check</p>
</div>

<div class="button-row-wrap" style="margin-bottom:18px">
    <a class="button-big" href="<?= app_url('teach/onboard.php') ?>">Onboard students</a>
    <?php if ($pendingWork): ?><a class="button-big gold" href="<?= app_url('teach/marking.php') ?>">Mark <?= $pendingWork ?> submission<?= $pendingWork === 1 ? '' : 's' ?></a><?php endif; ?>
    <?php if ($pendingPages): ?><a class="button-big gold" href="<?= app_url('teach/pages.php') ?>">Check <?= $pendingPages ?> page<?= $pendingPages === 1 ? '' : 's' ?></a><?php endif; ?>
    <?php if ($pendingDrafts): ?><a class="button-big gold" href="<?= app_url('teach/updates.php') ?>">Approve <?= $pendingDrafts ?> sponsor update<?= $pendingDrafts === 1 ? '' : 's' ?></a><?php endif; ?>
</div>

<?php if ($cohorts): ?>
<div class="academy-card">
    <form method="get" class="button-row-wrap">
        <label for="cohort" style="margin:0">Class</label>
        <select id="cohort" name="cohort" style="width:auto">
            <option value="0">All classes</option>
            <?php foreach ($cohorts as $cohort): ?>
                <option value="<?= (int)$cohort['id'] ?>" <?= $cohortFilter === (int)$cohort['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cohort['name'] . ($cohort['term'] !== '' ? ' — ' . $cohort['term'] : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button-big secondary" type="submit">Show</button>
    </form>
</div>
<?php endif; ?>

<div class="academy-card">
    <?php if (!$learners): ?>
        <p>No learners yet. <a href="<?= app_url('teach/onboard.php') ?>">Onboard your first student</a>.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="staff-table">
        <thead>
            <tr>
                <th>Name</th><th>Username</th><th>Class</th><th>Age</th>
                <th>Work</th><th>Page</th><th>Consent</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($learners as $learner): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($learner['display_name']) ?></strong>
                    <?php if ((int)$learner['active'] !== 1): ?><br><span class="pill pill-off">Inactive</span><?php endif; ?>
                    <?php if (!empty($learner['locked_until']) && strtotime($learner['locked_until']) > time()): ?>
                        <br><span class="pill pill-needs-work">Locked</span>
                    <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($learner['username']) ?></code></td>
                <td><?= htmlspecialchars($learner['cohort_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($learner['age_band'] ?: '—') ?></td>
                <td>
                    <?= (int)$learner['completed'] ?> done
                    <?php if ((int)$learner['awaiting'] > 0): ?>
                        <br><span class="pill pill-waiting"><?= (int)$learner['awaiting'] ?> to mark</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $siteStatus = $learner['site_status'] ?? 'draft'; ?>
                    <span class="pill <?= $siteStatus === 'published' ? 'pill-live' : ($siteStatus === 'pending_review' ? 'pill-waiting' : 'pill-off') ?>">
                        <?= htmlspecialchars(str_replace('_', ' ', $siteStatus)) ?>
                    </span>
                </td>
                <td>
                    <?= $learner['guardian_consent_on'] ? htmlspecialchars($learner['guardian_consent_on']) : '<span class="pill pill-needs-work">missing</span>' ?>
                    <br><small><?= $learner['consent_scope'] === 'learning_and_sponsor_updates' ? 'sponsor updates ok' : 'learning only' ?></small>
                </td>
                <td><a href="<?= app_url('teach/learner.php?id=' . (int)$learner['id']) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
