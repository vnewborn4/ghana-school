<?php
/**
 * Sponsor update approval — administrators only.
 *
 * The one place where something a child did becomes something a sponsor
 * reads. Every draft is rewritten and approved by a person here; nothing
 * reaches a sponsor automatically.
 *
 * Sponsor-facing copy is English. Donors read English, and the Ghanaian
 * language support elsewhere in the site is for learners.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/sponsor_bridge.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('admin');
$flash = null; $flashKind = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action   = (string)($_POST['action'] ?? '');
    $updateId = (int)($_POST['update_id'] ?? 0);
    $why = null;

    if ($action === 'approve') {
        $ok = bridge_approve($updateId, (int)$user['id'], [
            'title'     => (string)($_POST['title'] ?? ''),
            'summary'   => (string)($_POST['summary'] ?? ''),
            'milestone' => (string)($_POST['milestone'] ?? ''),
        ], $why);
        $flash = $ok ? 'Published to the sponsor dashboard.' : ($why ?? 'That draft could not be approved.');
        $flashKind = $ok ? 'ok' : 'bad';
    } elseif ($action === 'discard') {
        $flash = bridge_discard($updateId, (int)$user['id'])
            ? 'Draft discarded. That work will not be drafted again.'
            : 'That draft was not found.';
    } elseif ($action === 'withdraw') {
        $flash = bridge_withdraw($updateId, (int)$user['id'])
            ? 'Taken off the sponsor dashboard.'
            : 'That update was not found.';
        $flashKind = 'warn';
    }
}

$drafts  = bridge_pending_drafts();
$blocked = bridge_blocked_work();

$live = db()->query(
    'SELECT u.*, j.first_name AS journey_name
     FROM student_updates u JOIN student_journeys j ON j.id=u.student_journey_id
     WHERE u.status=\'approved\'
       AND (u.source_submission_id IS NOT NULL OR u.source_site_id IS NOT NULL)
     ORDER BY u.approved_at DESC LIMIT 20'
)->fetchAll();

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Sponsor updates';
$navItems   = staff_nav(true);
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Sponsor updates</h1>
    <p><?= count($drafts) ?> draft<?= count($drafts) === 1 ? '' : 's' ?> waiting.
       Nothing here is visible to a sponsor until you approve it.</p>
</div>

<?php if ($flash): ?><div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="notice notice-info">
    <strong>Rewrite before you approve.</strong> The draft text is assembled from
    facts about the lesson, not from anything the learner wrote. It is correct but
    plain. Sponsors respond to a specific, human sentence about what the child
    actually did &mdash; and never to a full name, a school, or a face.
</div>

<?php if (!$drafts): ?>
    <div class="academy-card"><p>No drafts are waiting. Drafts appear here when a teacher
       marks work as done and proposes it for sponsors, or when a student's page is
       published for the first time.</p></div>
<?php endif; ?>

<?php foreach ($drafts as $draft): ?>
<div class="academy-card">
    <div class="button-row-wrap">
        <span class="pill pill-waiting">Draft</span>
        <strong><?= htmlspecialchars($draft['journey_name']) ?></strong>
        <code><?= htmlspecialchars($draft['public_code']) ?></code>
        <?php if (!empty($draft['assignment_title'])): ?>
            <span class="field-hint">from &ldquo;<?= htmlspecialchars($draft['assignment_title']) ?>&rdquo;</span>
        <?php elseif (!empty($draft['site_slug'])): ?>
            <span class="field-hint">from their published page</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($draft['learner_words']) || !empty($draft['teacher_feedback'])): ?>
    <div class="notice notice-warn" style="margin-top:14px">
        <strong>Context only &mdash; not published.</strong>
        <?php if (!empty($draft['learner_words'])): ?>
            <p style="margin:8px 0 0"><em>The learner wrote:</em>
               &ldquo;<?= htmlspecialchars(mb_substr($draft['learner_words'], 0, 600)) ?>&rdquo;</p>
        <?php endif; ?>
        <?php if (!empty($draft['teacher_feedback'])): ?>
            <p style="margin:8px 0 0"><em>Their teacher wrote:</em>
               &ldquo;<?= htmlspecialchars(mb_substr($draft['teacher_feedback'], 0, 600)) ?>&rdquo;</p>
        <?php endif; ?>
        <p style="margin:8px 0 0">Draw on this if it helps, in your own words. Do not paste it
           in: a child writing freely may have named themselves, their school, or where they live.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($draft['site_slug'])): ?>
        <p style="margin-top:12px"><a class="button-big secondary"
           href="<?= app_url('students/' . $draft['site_slug'] . '/') ?>" target="_blank" rel="noopener">
           Read the page they published</a></p>
    <?php endif; ?>

    <form method="post" style="margin-top:14px">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="update_id" value="<?= (int)$draft['id'] ?>">

        <div class="field">
            <label for="title-<?= (int)$draft['id'] ?>">Title</label>
            <input id="title-<?= (int)$draft['id'] ?>" name="title" type="text" maxlength="180"
                   value="<?= htmlspecialchars($draft['title']) ?>" required>
        </div>
        <div class="field">
            <label for="milestone-<?= (int)$draft['id'] ?>">Milestone</label>
            <input id="milestone-<?= (int)$draft['id'] ?>" name="milestone" type="text" maxlength="120"
                   value="<?= htmlspecialchars((string)$draft['milestone']) ?>">
        </div>
        <div class="field">
            <label for="summary-<?= (int)$draft['id'] ?>">What the sponsor will read</label>
            <textarea id="summary-<?= (int)$draft['id'] ?>" name="summary" required
                      style="min-height:130px"><?= htmlspecialchars($draft['summary']) ?></textarea>
            <p class="field-hint">First names or the journey name only. No surnames, no school,
               no family details, no contact information.</p>
        </div>

        <div class="button-row-wrap">
            <button class="button-big" type="submit" name="action" value="approve">Approve and publish</button>
            <button class="button-big secondary" type="submit" name="action" value="discard"
                    formnovalidate data-confirm-button="Discard this draft? It will not be created again.">Discard</button>
        </div>
    </form>
</div>
<?php endforeach; ?>

<?php if ($blocked): ?>
<div class="academy-card">
    <h2>Proposed for sponsors, but held back</h2>
    <p class="field-hint">A teacher marked this work shareable, but it cannot become a sponsor
       update yet. Fix the cause on the learner's page, then mark the work again.</p>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Learner</th><th>Work</th><th>What is missing</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($blocked as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['display_name']) ?></td>
                <td><?= htmlspecialchars($item['assignment_title']) ?></td>
                <td>
                    <?php if ($item['consent_scope'] !== 'learning_and_sponsor_updates'): ?>
                        <span class="pill pill-needs-work">Guardian agreed to learning only</span>
                    <?php else: ?>
                        <span class="pill pill-waiting">Not linked to a sponsor journey</span>
                    <?php endif; ?>
                </td>
                <td><a href="<?= app_url('teach/learner.php?id=' . (int)$item['learner_id']) ?>">Open learner</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($live): ?>
<div class="academy-card">
    <h2>Recently published to sponsors</h2>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Journey</th><th>Title</th><th>Approved</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($live as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['journey_name']) ?></td>
                <td><?= htmlspecialchars($item['title']) ?></td>
                <td><?= $item['approved_at'] ? htmlspecialchars(date('j M Y', strtotime($item['approved_at']))) : '—' ?></td>
                <td>
                    <form method="post" data-confirm="Take this update off the sponsor dashboard?">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="update_id" value="<?= (int)$item['id'] ?>">
                        <button class="button-big secondary" type="submit" name="action" value="withdraw"
                                style="padding:6px 14px;font-size:.85rem">Take down</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<script defer src="<?= app_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
