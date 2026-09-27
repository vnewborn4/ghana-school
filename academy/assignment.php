<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/academy_nav.php';
require_once __DIR__ . '/../includes/student_sites.php';
require_once __DIR__ . '/../includes/audit.php';

learner_session_start();
$learner = require_learner();

$assignmentId = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.*, m.title AS module_title, m.summary AS module_summary, m.tool, m.tool_url
     FROM assignments a JOIN modules m ON m.id=a.module_id
     WHERE a.id=? AND m.published=1'
);
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();
if (!$assignment) { http_response_code(404); exit('That lesson was not found.'); }

$stmt = db()->prepare('SELECT * FROM submissions WHERE assignment_id=? AND learner_id=?');
$stmt->execute([$assignmentId, $learner['id']]);
$submission = $stmt->fetch() ?: null;

$flash = null; $flashKind = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_learner_csrf();
    $action = ($_POST['action'] ?? '') === 'submit' ? 'submit' : 'save_draft';
    $body   = trim((string)($_POST['body'] ?? ''));

    $filePath = $submission['file_path'] ?? null;
    $origName = $submission['original_name'] ?? null;
    if (!empty($_FILES['upload']['name'])) {
        $uploadError = null;
        $stored = submission_store_upload((int)$learner['id'], $_FILES['upload'], $uploadError);
        if ($uploadError !== null) { $flash = $uploadError; $flashKind = 'bad'; }
        elseif ($stored !== null) {
            $filePath = $stored;
            $origName = mb_substr((string)$_FILES['upload']['name'], 0, 160);
        }
    }

    if ($flashKind !== 'bad') {
        $status = $action === 'submit' ? 'submitted' : 'draft';
        $submittedAt = $action === 'submit'
            ? date('Y-m-d H:i:s')
            : ($submission['submitted_at'] ?? null);

        if ($submission) {
            $upd = db()->prepare(
                'UPDATE submissions
                    SET body=?, file_path=?, original_name=?, status=?, submitted_at=?
                  WHERE id=?'
            );
            $upd->execute([$body, $filePath, $origName, $status, $submittedAt, $submission['id']]);
        } else {
            $ins = db()->prepare(
                'INSERT INTO submissions
                    (assignment_id, learner_id, body, file_path, original_name, status, submitted_at)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $ins->execute([$assignmentId, $learner['id'], $body, $filePath, $origName, $status, $submittedAt]);
        }

        if ($action === 'submit') {
            audit('submission.sent', [
                'actor_learner_id' => (int)$learner['id'],
                'subject_type' => 'assignment', 'subject_id' => $assignmentId,
            ]);
            $flash = 'asg.submitted';
        } else {
            $flash = 'asg.draft_saved';
        }

        $stmt = db()->prepare('SELECT * FROM submissions WHERE assignment_id=? AND learner_id=?');
        $stmt->execute([$assignmentId, $learner['id']]);
        $submission = $stmt->fetch() ?: null;
    }
}

$status = $submission['status'] ?? 'draft';
$isSite = $assignment['submission_type'] === 'site';
$wantsFile = in_array($assignment['submission_type'], ['file','screenshot'], true);
$wantsText = in_array($assignment['submission_type'], ['reflection','code'], true) || $isSite;

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = $assignment['title'];
$navItems   = student_nav();
$signOut    = ['action' => app_url('academy/logout.php'), 'csrf' => learner_csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <p><a href="<?= app_url('academy/index.php') ?>">&larr; <?= e('common.back') ?></a></p>
    <h1><?= htmlspecialchars($assignment['title']) ?></h1>
    <p><?= htmlspecialchars($assignment['module_title']) ?></p>
</div>

<?php if ($flash): ?>
    <div class="notice notice-<?= htmlspecialchars($flashKind) ?>"><?= e($flash) ?></div>
<?php endif; ?>

<?php if ($status === 'reviewed'): ?>
    <div class="notice notice-ok"><?= e('dash.done') ?></div>
<?php elseif ($status === 'submitted'): ?>
    <div class="notice notice-info"><?= e('dash.waiting') ?></div>
<?php elseif ($status === 'needs_work'): ?>
    <div class="notice notice-warn"><?= e('dash.needs_work') ?></div>
<?php endif; ?>

<div class="academy-card">
    <h2><?= e('asg.brief') ?></h2>
    <p><?= nl2br(htmlspecialchars($assignment['brief'])) ?></p>
    <div class="button-row-wrap">
        <?php if (!empty($assignment['tool_url'])): ?>
            <a class="button-big gold" href="<?= htmlspecialchars(app_url($assignment['tool_url'])) ?>" target="_blank" rel="noopener"><?= e('asg.open_tool') ?></a>
        <?php endif; ?>
        <?php if ($isSite): ?>
            <a class="button-big gold" href="<?= app_url('academy/mysite.php') ?>"><?= e('site.title') ?></a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($submission['teacher_feedback'])): ?>
<div class="academy-card">
    <h2><?= e('asg.teacher_feedback') ?></h2>
    <p><?= nl2br(htmlspecialchars($submission['teacher_feedback'])) ?></p>
</div>
<?php endif; ?>

<div class="academy-card">
    <h2><?= e('asg.your_work') ?></h2>
    <?php if ($isSite): ?><p class="field-hint"><?= e('asg.site_hint') ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">

        <?php if ($wantsText): ?>
        <div class="field">
            <label for="body"><?= $assignment['submission_type'] === 'code' ? e('asg.code_hint') : e('asg.reflection_hint') ?></label>
            <textarea id="body" name="body"><?= htmlspecialchars((string)($submission['body'] ?? '')) ?></textarea>
        </div>
        <?php endif; ?>

        <?php if ($wantsFile): ?>
        <div class="field">
            <label for="upload"><?= e('asg.upload') ?></label>
            <input id="upload" name="upload" type="file"
                   accept=".png,.jpg,.jpeg,.gif,.webp,.sb3,.xml,.zip,.txt,.pdf">
            <p class="field-hint"><?= e('asg.upload_hint') ?></p>
            <?php if (!empty($submission['original_name'])): ?>
                <p class="field-hint"><?= e('common.saved') ?>: <?= htmlspecialchars($submission['original_name']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="button-row-wrap">
            <button class="button-big secondary" type="submit" name="action" value="save_draft"><?= e('asg.save_draft') ?></button>
            <button class="button-big" type="submit" name="action" value="submit">
                <?= $status === 'draft' ? e('asg.submit') : e('asg.resubmit') ?>
            </button>
        </div>
    </form>

    <?php if (!empty($submission['submitted_at'])): ?>
        <p class="field-hint"><?= e('asg.submitted_on', ['date' => date('j M Y', strtotime($submission['submitted_at']))]) ?></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
