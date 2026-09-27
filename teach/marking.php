<?php
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/student_sites.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');
$submissionId = (int)($_GET['id'] ?? 0);
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $submissionId = (int)($_POST['submission_id'] ?? 0);
    $decision = ($_POST['decision'] ?? '') === 'needs_work' ? 'needs_work' : 'reviewed';
    $feedback = trim((string)($_POST['teacher_feedback'] ?? ''));

    /* Consent is enforced here, in the query -- not in the form. A learner
       whose guardian agreed to learning only can never be marked shareable,
       whatever a teacher ticks. */
    $stmt = db()->prepare(
        'SELECT s.id, s.learner_id, l.consent_scope
         FROM submissions s JOIN learners l ON l.id=s.learner_id WHERE s.id=?'
    );
    $stmt->execute([$submissionId]);
    $target = $stmt->fetch();

    if ($target) {
        $shareable = (!empty($_POST['shareable'])
            && $target['consent_scope'] === 'learning_and_sponsor_updates') ? 1 : 0;

        $upd = db()->prepare(
            'UPDATE submissions
                SET status=?, teacher_feedback=?, reviewed_by=?, reviewed_at=NOW(), shareable=?
              WHERE id=?'
        );
        $upd->execute([$decision, $feedback !== '' ? $feedback : null, (int)$user['id'], $shareable, $submissionId]);

        audit('submission.reviewed', [
            'actor_user_id' => (int)$user['id'],
            'subject_type'  => 'submission',
            'subject_id'    => $submissionId,
            'detail'        => 'decision=' . $decision . ' shareable=' . $shareable,
        ]);

        if ($decision === 'reviewed') {
            require_once __DIR__ . '/../includes/learner_auth.php';
            $learnerId = (int)$target['learner_id'];
            award_badge($learnerId, 'first-project');
            $count = db()->prepare('SELECT COUNT(*) FROM submissions WHERE learner_id=? AND status=\'reviewed\'');
            $count->execute([$learnerId]);
            if ((int)$count->fetchColumn() >= 5) award_badge($learnerId, 'five-lessons');
        }
        $flash = 'Marked.';
    }
    $submissionId = 0;   // fall back to the queue after marking
}

$current = null;
if ($submissionId > 0) {
    $stmt = db()->prepare(
        'SELECT s.*, a.title AS assignment_title, a.brief, a.submission_type,
                m.title AS module_title,
                l.display_name, l.username, l.consent_scope, l.id AS learner_id
         FROM submissions s
         JOIN assignments a ON a.id=s.assignment_id
         JOIN modules m ON m.id=a.module_id
         JOIN learners l ON l.id=s.learner_id
         WHERE s.id=?'
    );
    $stmt->execute([$submissionId]);
    $current = $stmt->fetch() ?: null;
}

$queue = db()->query(
    'SELECT s.id, s.submitted_at, a.title AS assignment_title, m.title AS module_title,
            l.display_name, l.username
     FROM submissions s
     JOIN assignments a ON a.id=s.assignment_id
     JOIN modules m ON m.id=a.module_id
     JOIN learners l ON l.id=s.learner_id
     WHERE s.status=\'submitted\' AND l.active=1
     ORDER BY s.submitted_at'
)->fetchAll();

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Marking';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1>Marking</h1>
    <p><?= count($queue) ?> waiting.</p>
</div>

<?php if ($flash): ?><div class="notice notice-ok"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<?php if ($current): ?>
<div class="academy-card">
    <h2><?= htmlspecialchars($current['assignment_title']) ?></h2>
    <p class="field-hint"><?= htmlspecialchars($current['display_name']) ?>
       (<code><?= htmlspecialchars($current['username']) ?></code>)
       &middot; <?= htmlspecialchars($current['module_title']) ?></p>

    <p><strong>What was asked:</strong> <?= nl2br(htmlspecialchars($current['brief'])) ?></p>

    <?php if (!empty($current['body'])): ?>
        <h3>Their answer</h3>
        <?php if ($current['submission_type'] === 'code'): ?>
            <pre style="background:var(--cream);padding:14px;border-radius:12px;overflow:auto"><?= htmlspecialchars($current['body']) ?></pre>
        <?php else: ?>
            <p><?= nl2br(htmlspecialchars($current['body'])) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($current['file_path'])): ?>
        <p><a class="button-big secondary" href="<?= app_url('teach/download.php?id=' . (int)$current['id']) ?>">
            Download <?= htmlspecialchars($current['original_name'] ?? 'their file') ?></a></p>
    <?php endif; ?>

    <?php if ($current['submission_type'] === 'site'): ?>
        <p><a class="button-big secondary" href="<?= app_url('students/preview/' . $current['username'] . '/') ?>" target="_blank" rel="noopener">Open their page</a></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="submission_id" value="<?= (int)$current['id'] ?>">

        <div class="field">
            <label for="teacher_feedback">What you want to say to them</label>
            <textarea id="teacher_feedback" name="teacher_feedback" style="min-height:120px"><?= htmlspecialchars((string)($current['teacher_feedback'] ?? '')) ?></textarea>
            <p class="field-hint">Written for a child to read. Name one thing that worked and one thing to try next.</p>
        </div>

        <?php if ($current['consent_scope'] === 'learning_and_sponsor_updates'): ?>
        <div class="field">
            <label><input type="checkbox" name="shareable" value="1" style="width:auto"
                          <?= (int)$current['shareable'] === 1 ? 'checked' : '' ?>>
                   Propose this work for a sponsor update</label>
            <p class="field-hint">This only proposes it. An administrator still reviews and approves
               the wording before a sponsor sees anything.</p>
        </div>
        <?php else: ?>
        <p class="field-hint"><strong>Learning only:</strong> this learner's guardian did not agree
           to sponsor updates, so this work cannot be shared. Change that on the learner's page
           only if the guardian has signed a new form.</p>
        <?php endif; ?>

        <div class="button-row-wrap">
            <button class="button-big" type="submit" name="decision" value="reviewed">Mark as done</button>
            <button class="button-big secondary" type="submit" name="decision" value="needs_work">Ask them to try again</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="academy-card">
    <h2>Waiting to be marked</h2>
    <?php if (!$queue): ?>
        <p>Nothing is waiting. </p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="staff-table">
        <thead><tr><th>Student</th><th>Lesson</th><th>Sent</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($queue as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['display_name']) ?></td>
                <td><?= htmlspecialchars($item['assignment_title']) ?><br><small><?= htmlspecialchars($item['module_title']) ?></small></td>
                <td><?= $item['submitted_at'] ? htmlspecialchars(date('j M Y', strtotime($item['submitted_at']))) : '—' ?></td>
                <td><a href="<?= app_url('teach/marking.php?id=' . (int)$item['id']) ?>">Mark</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
