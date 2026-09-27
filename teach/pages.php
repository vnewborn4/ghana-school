<?php
/**
 * Student page review.
 *
 * Publishing is an administrator action; taking a page offline is available
 * to any staff member, because a safeguarding concern must never wait for
 * the right person to be online.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/student_sites.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');
$isAdmin = is_admin_user($user);
$flash = null; $flashKind = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $siteId = (int)($_POST['site_id'] ?? 0);

    $stmt = db()->prepare('SELECT ss.*, l.display_name FROM student_sites ss JOIN learners l ON l.id=ss.learner_id WHERE ss.id=?');
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();

    if (!$site) {
        $flash = 'That page was not found.'; $flashKind = 'bad';
    } elseif ($action === 'publish') {
        if (!$isAdmin) {
            http_response_code(403);
            exit('Only an administrator can publish a student page.');
        }
        if (site_publish_draft($site['slug'])) {
            db()->prepare('UPDATE student_sites SET status=?, published_at=NOW(), published_by=?, review_note=? WHERE id=?')
                ->execute(['published', (int)$user['id'], trim((string)($_POST['review_note'] ?? '')) ?: null, $siteId]);
            audit('site.publish', [
                'actor_user_id' => (int)$user['id'], 'subject_type' => 'student_site',
                'subject_id' => $siteId, 'detail' => 'slug=' . $site['slug'],
            ]);
            $flash = $site['display_name'] . "'s page is now online.";
        } else {
            $flash = 'That page could not be published. Check the storage directory is writable.';
            $flashKind = 'bad';
        }
    } elseif ($action === 'unpublish' || $action === 'suspend') {
        $status = $action === 'suspend' ? 'suspended' : 'unpublished';
        db()->prepare('UPDATE student_sites SET status=?, review_note=? WHERE id=?')
            ->execute([$status, trim((string)($_POST['review_note'] ?? '')) ?: null, $siteId]);
        audit('site.' . $action, [
            'actor_user_id' => (int)$user['id'], 'subject_type' => 'student_site',
            'subject_id' => $siteId, 'detail' => 'slug=' . $site['slug'],
        ]);
        $flash = $site['display_name'] . "'s page has been taken offline.";
    } elseif ($action === 'send_back') {
        db()->prepare('UPDATE student_sites SET status=?, review_note=? WHERE id=?')
            ->execute(['draft', trim((string)($_POST['review_note'] ?? '')) ?: null, $siteId]);
        audit('site.sent_back', [
            'actor_user_id' => (int)$user['id'], 'subject_type' => 'student_site', 'subject_id' => $siteId,
        ]);
        $flash = 'Sent back to ' . $site['display_name'] . ' with your note.';
    } elseif ($action === 'unpublish_all' && $isAdmin) {
        $count = db()->prepare('UPDATE student_sites SET status=? WHERE status=?');
        $count->execute(['unpublished', 'published']);
        audit('site.unpublish_all', [
            'actor_user_id' => (int)$user['id'], 'subject_type' => 'student_site',
            'detail' => 'count=' . $count->rowCount(),
        ]);
        $flash = 'Every student page has been taken offline.';
        $flashKind = 'warn';
    }
}

$sites = db()->query(
    'SELECT ss.*, l.display_name, l.active AS learner_active
     FROM student_sites ss JOIN learners l ON l.id=ss.learner_id
     ORDER BY FIELD(ss.status, \'pending_review\', \'published\', \'draft\', \'unpublished\', \'suspended\'),
              ss.last_edited_at DESC'
)->fetchAll();

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Student pages';
$navItems   = staff_nav($isAdmin);
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Student pages</h1>
    <p>Read every page before it goes online. The checks below are hints for you, not a decision.</p>
</div>

<?php if ($flash): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<?php if (!$isAdmin): ?>
    <div class="notice notice-info">You can review pages and take them offline. An administrator publishes.</div>
<?php endif; ?>

<?php foreach ($sites as $site): ?>
    <?php
    $warnings = in_array($site['status'], ['pending_review','published'], true)
        ? site_prepublish_warnings($site['slug'])
        : [];
    ?>
    <div class="academy-card">
        <div class="button-row-wrap">
            <span class="pill <?= $site['status'] === 'published' ? 'pill-live' : ($site['status'] === 'pending_review' ? 'pill-waiting' : 'pill-off') ?>">
                <?= htmlspecialchars(str_replace('_', ' ', $site['status'])) ?>
            </span>
            <strong><?= htmlspecialchars($site['display_name']) ?></strong>
            <code><?= htmlspecialchars($site['slug']) ?></code>
            <?php if ((int)$site['learner_active'] !== 1): ?><span class="pill pill-off">account inactive</span><?php endif; ?>
        </div>

        <p class="field-hint">
            <?= (int)$site['file_count'] ?> file<?= (int)$site['file_count'] === 1 ? '' : 's' ?>,
            <?= round((int)$site['bytes_used'] / 1024) ?> KB
            <?php if ($site['last_edited_at']): ?>&middot; last edited <?= htmlspecialchars(date('j M Y H:i', strtotime($site['last_edited_at']))) ?><?php endif; ?>
        </p>

        <?php if ($warnings): ?>
            <div class="notice notice-warn">
                <strong>Worth a look:</strong>
                <ul style="margin:6px 0 0"><?php foreach ($warnings as $warning): ?><li><?= htmlspecialchars($warning) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="button-row-wrap" style="margin-bottom:12px">
            <a class="button-big secondary" href="<?= app_url('students/preview/' . $site['slug'] . '/') ?>" target="_blank" rel="noopener">Read the draft</a>
            <?php if ($site['status'] === 'published'): ?>
                <a class="button-big secondary" href="<?= app_url('students/' . $site['slug'] . '/') ?>" target="_blank" rel="noopener">See it live</a>
            <?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="site_id" value="<?= (int)$site['id'] ?>">
            <div class="field">
                <label for="note-<?= (int)$site['id'] ?>">Note for the student (optional)</label>
                <input id="note-<?= (int)$site['id'] ?>" name="review_note" type="text" maxlength="255"
                       value="<?= htmlspecialchars((string)($site['review_note'] ?? '')) ?>">
            </div>
            <div class="button-row-wrap">
                <?php if ($isAdmin && $site['status'] !== 'published'): ?>
                    <button class="button-big" type="submit" name="action" value="publish">Publish</button>
                <?php endif; ?>
                <button class="button-big secondary" type="submit" name="action" value="send_back">Send back to the student</button>
                <?php if ($site['status'] === 'published'): ?>
                    <button class="button-big secondary" type="submit" name="action" value="unpublish">Take offline</button>
                <?php endif; ?>
                <button class="button-big secondary" type="submit" name="action" value="suspend" style="color:#8d3c1c;border-color:#8d3c1c">Suspend</button>
            </div>
        </form>
    </div>
<?php endforeach; ?>

<?php if (!$sites): ?>
    <div class="academy-card"><p>No student pages yet.</p></div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="academy-card">
    <h2>Take every page offline</h2>
    <p class="field-hint">One action, for when something needs to stop immediately. Drafts are
       untouched and nothing is deleted &mdash; pages can be published again afterwards.</p>
    <form method="post" data-confirm="Take EVERY published student page offline right now?">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button class="button-big" type="submit" name="action" value="unpublish_all" style="background:#8d3c1c">Take all pages offline</button>
    </form>
</div>
<?php endif; ?>

<script defer src="<?= app_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
