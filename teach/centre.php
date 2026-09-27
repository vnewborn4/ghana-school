<?php
/**
 * The learning centre's Kolibri server.
 *
 * Kolibri runs at the centre in Accra, on its own local network, and teaches
 * whether or not the internet is up. This page is the two places the website
 * meets it:
 *
 *   out  a roster file in Kolibri's import format, so a learner onboarded
 *        here gets the same username at the centre
 *   in   the activity file the centre produces, uploaded from a USB stick
 *        (or posted automatically by api/kolibri_sync.php when there is a line)
 *
 * See docs/KOLIBRI_CENTRE_SETUP.md for the centre side.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/centre_activity.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');
$flash = null; $flashKind = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'upload') {
        $file = $_FILES['payload'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $flash = 'No file was uploaded, or it was too large for the server.';
            $flashKind = 'bad';
        } elseif (($file['size'] ?? 0) > CENTRE_PAYLOAD_MAX_BYTES) {
            $flash = 'That file is too large. Sync a shorter window at the centre.';
            $flashKind = 'bad';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $flash = 'That upload was not accepted.';
            $flashKind = 'bad';
        } else {
            $raw = (string)file_get_contents($file['tmp_name']);
            $payload = json_decode($raw, true);
            $problem = is_array($payload) ? centre_validate_payload($payload) : 'That file is not valid JSON.';
            if ($problem !== null) {
                $flash = $problem;
                $flashKind = 'bad';
            } else {
                try {
                    $result = centre_ingest($payload, 'upload', (int)$user['id']);
                    $flash = sprintf(
                        'Recorded %d row%s from the centre, %d matched to a learner account.',
                        $result['received'], $result['received'] === 1 ? '' : 's', $result['matched']
                    );
                } catch (Throwable $e) {
                    error_log('centre upload: ' . $e->getMessage());
                    $flash = 'That file could not be recorded.';
                    $flashKind = 'bad';
                }
            }
        }
    }
}

$to    = date('Y-m-d');
$from  = date('Y-m-d', strtotime('-30 days'));
$totals = centre_totals($from, $to);
$daily  = centre_daily_totals($from, $to);
$syncs  = centre_recent_syncs(8);
$cohorts = db()->query('SELECT id, name, term FROM cohorts WHERE active=1 ORDER BY name')->fetchAll();

$lastSync = $syncs[0]['created_at'] ?? null;
$staleDays = $lastSync ? (int)floor((time() - strtotime($lastSync)) / 86400) : null;

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Learning centre';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Learning centre</h1>
    <p>Kolibri runs on the centre's own server in Accra and works with no internet.
       This page exchanges files with it.</p>
</div>

<?php if ($flash): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<?php if ($staleDays === null): ?>
    <div class="notice notice-info">The centre has not reported yet. Follow
        <code>docs/KOLIBRI_CENTRE_SETUP.md</code> to set up the server, then upload its
        first activity file below.</div>
<?php elseif ($staleDays > 14): ?>
    <div class="notice notice-warn">The centre last reported <?= (int)$staleDays ?> days ago.
        That is normal if it has been offline, but worth a check.</div>
<?php endif; ?>

<div class="academy-card">
    <h2>Last 30 days at the centre</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <tbody>
            <tr><td>Days the centre was in use</td><td><strong><?= (int)$totals['days_open'] ?></strong></td></tr>
            <tr><td>Learners who used Kolibri</td><td><strong><?= (int)$totals['learners'] ?></strong></td></tr>
            <tr><td>Sessions</td><td><strong><?= (int)$totals['sessions'] ?></strong></td></tr>
            <tr><td>Activities completed</td><td><strong><?= (int)$totals['completed'] ?></strong></td></tr>
            <tr><td>Learning time</td><td><strong><?= number_format((int)$totals['minutes'] / 60, 1) ?> hours</strong></td></tr>
        </tbody>
    </table>
    </div>
    <p class="field-hint">These feed the programme report, which is what funders ask for.</p>
</div>

<div class="academy-card">
    <h2>Send the roster to the centre</h2>
    <p class="field-hint">Downloads a file in Kolibri's own import format. Copy it to the centre
       server and run:<br>
       <code>kolibri manage bulkimportusers roster.csv</code></p>
    <p class="field-hint">Learners already at the centre are reported as <em>Username is
       duplicated</em> and skipped &mdash; nothing is changed or reset, so the file is safe to
       re-run. Never add <code>--delete</code>: it removes everyone missing from the file.</p>
    <form method="get" action="<?= app_url('teach/kolibri_roster.php') ?>" class="button-row-wrap">
        <div>
            <label for="cohort">Class</label>
            <select id="cohort" name="cohort" style="width:auto">
                <option value="0">Everyone</option>
                <?php foreach ($cohorts as $cohort): ?>
                    <option value="<?= (int)$cohort['id'] ?>"><?= htmlspecialchars($cohort['name'] . ($cohort['term'] !== '' ? ' — ' . $cohort['term'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button-big" type="submit">Download roster for Kolibri</button>
    </form>
</div>

<div class="academy-card">
    <h2>Bring activity back from the centre</h2>
    <p class="field-hint">On the centre server, with a USB stick plugged in:<br>
       <code>kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json</code><br>
       Then upload that file here. Uploading the same window twice is safe &mdash; rows are
       keyed on the day and the learner, so nothing is counted twice.</p>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <div class="field">
            <label for="payload">Activity file from the centre</label>
            <input id="payload" name="payload" type="file" accept=".json,application/json" required>
        </div>
        <button class="button-big" type="submit">Upload activity</button>
    </form>
</div>

<?php if ($daily): ?>
<div class="academy-card">
    <h2>Day by day</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Date</th><th>Learners</th><th>Sessions</th><th>Completed</th><th>Hours</th></tr></thead>
        <tbody>
        <?php foreach ($daily as $day): ?>
            <tr>
                <td><?= htmlspecialchars(date('D j M Y', strtotime($day['activity_date']))) ?></td>
                <td><?= (int)$day['learners_active'] ?></td>
                <td><?= (int)$day['sessions'] ?></td>
                <td><?= (int)$day['completed'] ?></td>
                <td><?= number_format((int)$day['minutes'] / 60, 1) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($syncs): ?>
<div class="academy-card">
    <h2>Recent syncs</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>When</th><th>How</th><th>Device</th><th>Window</th><th>Rows</th><th>Matched</th></tr></thead>
        <tbody>
        <?php foreach ($syncs as $sync): ?>
            <tr>
                <td><?= htmlspecialchars(date('j M Y H:i', strtotime($sync['created_at']))) ?></td>
                <td><?= $sync['method'] === 'http' ? 'Automatic' : 'Uploaded' ?>
                    <?php if ($sync['first_name']): ?><br><small>by <?= htmlspecialchars($sync['first_name']) ?></small><?php endif; ?></td>
                <td><code><?= htmlspecialchars($sync['device'] ?: '—') ?></code></td>
                <td><?= $sync['window_from'] ? htmlspecialchars($sync['window_from'] . ' → ' . $sync['window_to']) : '—' ?></td>
                <td><?= (int)$sync['rows_received'] ?></td>
                <td><?= (int)$sync['rows_matched'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
