<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/academy_nav.php';

learner_session_start();
$learner = require_learner();

/* Lessons come from the learner's cohort where one is set; otherwise every
   published module, so a learner is never left with an empty dashboard. */
$sql = 'SELECT m.*,
               a.id AS assignment_id, a.title AS assignment_title, a.submission_type,
               s.status AS submission_status, s.teacher_feedback
        FROM modules m
        LEFT JOIN assignments a ON a.module_id = m.id
        LEFT JOIN submissions s ON s.assignment_id = a.id AND s.learner_id = :learner
        WHERE m.published = 1 ';
$params = ['learner' => $learner['id']];
if (!empty($learner['cohort_id'])) {
    $sql .= ' AND (EXISTS (SELECT 1 FROM cohort_modules cm WHERE cm.module_id=m.id AND cm.cohort_id=:cohort)
                   OR NOT EXISTS (SELECT 1 FROM cohort_modules cm2 WHERE cm2.cohort_id=:cohort2)) ';
    $params['cohort'] = $learner['cohort_id'];
    $params['cohort2'] = $learner['cohort_id'];
}
$sql .= ' ORDER BY m.sort_order, m.id, a.sort_order, a.id';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$badgeCount = db()->prepare('SELECT COUNT(*) FROM learner_badges WHERE learner_id=?');
$badgeCount->execute([$learner['id']]);
$badges = (int)$badgeCount->fetchColumn();

function lesson_state(?string $status): array {
    return match ($status) {
        'reviewed'   => ['is-done',       'pill-done',       'dash.done'],
        'submitted'  => ['is-waiting',    'pill-waiting',    'dash.waiting'],
        'needs_work' => ['is-needs-work', 'pill-needs-work', 'dash.needs_work'],
        default      => ['',              '',                'dash.to_do'],
    };
}

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = t('dash.my_lessons');
$navItems   = student_nav();
$signOut    = ['action' => app_url('academy/logout.php'), 'csrf' => learner_csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head">
    <h1><?= e('dash.greeting', ['name' => $learner['display_name']]) ?></h1>
    <p><?= e('dash.subtitle') ?></p>
</div>

<?php if (!$rows): ?>
    <div class="academy-card"><p><?= e('dash.no_lessons') ?></p></div>
<?php else: ?>
    <div class="academy-grid">
    <?php foreach ($rows as $row): ?>
        <?php [$cardClass, $pillClass, $labelKey] = lesson_state($row['submission_status'] ?? null); ?>
        <article class="lesson-card <?= $cardClass ?>">
            <div class="button-row-wrap">
                <span class="pill <?= $pillClass ?>"><?= e($labelKey) ?></span>
                <?php if (!empty($row['teacher_feedback'])): ?>
                    <span class="pill pill-waiting"><?= e('dash.has_feedback') ?></span>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p><?= htmlspecialchars($row['summary']) ?></p>
            <div class="lesson-actions button-row-wrap">
                <?php if (!empty($row['assignment_id'])): ?>
                    <a class="button-big" href="<?= app_url('academy/assignment.php?id=' . (int)$row['assignment_id']) ?>"><?= e('dash.open') ?></a>
                <?php elseif (!empty($row['tool_url'])): ?>
                    <a class="button-big" href="<?= htmlspecialchars(app_url($row['tool_url'])) ?>" target="_blank" rel="noopener"><?= e('asg.open_tool') ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="academy-card" style="margin-top:22px">
    <h2><?= e('badge.title') ?></h2>
    <p><?= $badges > 0
        ? htmlspecialchars((string)$badges) . ' &middot; <a href="' . app_url('academy/badges.php') . '">' . e('dash.open') . '</a>'
        : e('badge.none') ?></p>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
