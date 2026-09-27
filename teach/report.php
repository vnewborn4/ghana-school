<?php
/**
 * Programme report for grant applications and funder reporting.
 *
 * Every figure here is an aggregate. No learner is named, so the page can be
 * printed and sent to a funder as it stands. See docs/FUNDING_AND_FREE_RESOURCES.md.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/academy_nav.php';
require_once __DIR__ . '/../includes/centre_activity.php';

$user = require_role('teacher');

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-3 months'));
$to   = $_GET['to']   ?? date('Y-m-d');
if (strtotime($from) === false) $from = date('Y-m-d', strtotime('-3 months'));
if (strtotime($to)   === false) $to   = date('Y-m-d');
$toEnd = $to . ' 23:59:59';

$one = function (string $sql, array $params = []) {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
};

$enrolled       = $one('SELECT COUNT(*) FROM learners WHERE created_at <= ?', [$toEnd]);
$activeAccounts = $one('SELECT COUNT(*) FROM learners WHERE active=1');
$newThisPeriod  = $one('SELECT COUNT(*) FROM learners WHERE created_at BETWEEN ? AND ?', [$from, $toEnd]);
$signedIn       = $one('SELECT COUNT(*) FROM learners WHERE last_login_at BETWEEN ? AND ?', [$from, $toEnd]);

$submitted = $one('SELECT COUNT(*) FROM submissions WHERE submitted_at BETWEEN ? AND ?', [$from, $toEnd]);
$reviewed  = $one('SELECT COUNT(*) FROM submissions WHERE status=\'reviewed\' AND reviewed_at BETWEEN ? AND ?', [$from, $toEnd]);
$published = $one('SELECT COUNT(*) FROM student_sites WHERE status=\'published\' AND published_at <= ?', [$toEnd]);
$badges    = $one('SELECT COUNT(*) FROM learner_badges WHERE awarded_on BETWEEN ? AND ?', [$from, $toEnd]);

$centre = centre_totals($from, $to);

$stmt = db()->prepare('SELECT gender, COUNT(*) AS n FROM learners WHERE created_at <= ? GROUP BY gender');
$stmt->execute([$toEnd]);
$byGender = [];
foreach ($stmt->fetchAll() as $row) $byGender[$row['gender']] = (int)$row['n'];

$stmt = db()->prepare('SELECT age_band, COUNT(*) AS n FROM learners WHERE created_at <= ? GROUP BY age_band ORDER BY age_band');
$stmt->execute([$toEnd]);
$byAge = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT m.title, m.ges_curriculum_ref, COUNT(s.id) AS completions
     FROM modules m
     JOIN assignments a ON a.module_id=m.id
     LEFT JOIN submissions s ON s.assignment_id=a.id AND s.status=\'reviewed\'
          AND s.reviewed_at BETWEEN ? AND ?
     GROUP BY m.id, m.title, m.ges_curriculum_ref
     ORDER BY m.sort_order'
);
$stmt->execute([$from, $toEnd]);
$byModule = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT c.name, c.term, COUNT(DISTINCT l.id) AS learners,
            COUNT(DISTINCT CASE WHEN s.status=\'reviewed\' THEN s.id END) AS completions
     FROM cohorts c
     LEFT JOIN learners l ON l.cohort_id=c.id
     LEFT JOIN submissions s ON s.learner_id=l.id AND s.reviewed_at BETWEEN ? AND ?
     GROUP BY c.id, c.name, c.term ORDER BY c.name'
);
$stmt->execute([$from, $toEnd]);
$byCohort = $stmt->fetchAll();

$girls = $byGender['girl'] ?? 0;
$recorded = $enrolled - ($byGender['not_recorded'] ?? 0);
$girlShare = $recorded > 0 ? round($girls / $recorded * 100) : null;

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Programme report';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Programme report</h1>
    <p><?= htmlspecialchars(date('j F Y', strtotime($from))) ?> to <?= htmlspecialchars(date('j F Y', strtotime($to))) ?>
       &middot; Mill Creek-AR Learning Center, Accra</p>
</div>

<div class="academy-card no-print">
    <form method="get" class="button-row-wrap">
        <div><label for="from">From</label><input id="from" name="from" type="date" value="<?= htmlspecialchars($from) ?>"></div>
        <div><label for="to">To</label><input id="to" name="to" type="date" value="<?= htmlspecialchars($to) ?>"></div>
        <button class="button-big secondary" type="submit">Update</button>
        <button class="button-big" type="button" id="print-cards">Print</button>
    </form>
    <p class="field-hint">Every figure below is an aggregate. No learner is named, so this page
       can be sent to a funder exactly as it prints.</p>
</div>

<div class="academy-card">
    <h2>Participation</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <tbody>
            <tr><td>Learners enrolled to date</td><td><strong><?= $enrolled ?></strong></td></tr>
            <tr><td>Accounts currently active</td><td><strong><?= $activeAccounts ?></strong></td></tr>
            <tr><td>New learners this period</td><td><strong><?= $newThisPeriod ?></strong></td></tr>
            <tr><td>Learners who attended (signed in) this period</td><td><strong><?= $signedIn ?></strong></td></tr>
            <?php if ($girlShare !== null): ?>
            <tr><td>Girls, as a share of learners whose gender is recorded</td>
                <td><strong><?= $girlShare ?>%</strong> (<?= $girls ?> of <?= $recorded ?>)</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="academy-card">
    <h2>Learning delivered</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <tbody>
            <tr><td>Assignments submitted this period</td><td><strong><?= $submitted ?></strong></td></tr>
            <tr><td>Assignments completed and reviewed by a teacher</td><td><strong><?= $reviewed ?></strong></td></tr>
            <tr><td>Student web pages published to date</td><td><strong><?= $published ?></strong></td></tr>
            <tr><td>Badges awarded this period</td><td><strong><?= $badges ?></strong></td></tr>
        </tbody>
    </table>
    </div>
</div>

<?php if ((int)$centre['days_open'] > 0): ?>
<div class="academy-card">
    <h2>At the learning centre</h2>
    <p class="field-hint">From the centre's own Kolibri server in Accra, which runs whether or
       not there is an internet connection.</p>
    <div class="table-scroll">
    <table class="staff-table">
        <tbody>
            <tr><td>Days the centre was in use</td><td><strong><?= (int)$centre['days_open'] ?></strong></td></tr>
            <tr><td>Learners who used it</td><td><strong><?= (int)$centre['learners'] ?></strong></td></tr>
            <tr><td>Sessions</td><td><strong><?= (int)$centre['sessions'] ?></strong></td></tr>
            <tr><td>Activities completed</td><td><strong><?= (int)$centre['completed'] ?></strong></td></tr>
            <tr><td>Learning time</td><td><strong><?= number_format((int)$centre['minutes'] / 60, 1) ?> hours</strong></td></tr>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($byAge): ?>
<div class="academy-card">
    <h2>By age band</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Age band</th><th>Learners</th></tr></thead>
        <tbody>
        <?php foreach ($byAge as $row): ?>
            <tr><td><?= htmlspecialchars($row['age_band'] ?: 'not recorded') ?></td><td><?= (int)$row['n'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($byCohort): ?>
<div class="academy-card">
    <h2>By class</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Class</th><th>Term</th><th>Learners</th><th>Completions this period</th></tr></thead>
        <tbody>
        <?php foreach ($byCohort as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['term']) ?></td>
                <td><?= (int)$row['learners'] ?></td>
                <td><?= (int)$row['completions'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<div class="academy-card">
    <h2>By module</h2>
    <p class="field-hint">Add the Ghana Education Service curriculum reference to each module so
       this table shows, line by line, which part of the national curriculum you are delivering.</p>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Module</th><th>GES curriculum reference</th><th>Completions this period</th></tr></thead>
        <tbody>
        <?php foreach ($byModule as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['ges_curriculum_ref'] ? htmlspecialchars($row['ges_curriculum_ref']) : '<span class="pill pill-off">not mapped</span>' ?></td>
                <td><?= (int)$row['completions'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script defer src="<?= asset_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
