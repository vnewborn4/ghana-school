<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/academy_nav.php';

learner_session_start();
$learner = require_learner();

$stmt = db()->prepare(
    'SELECT b.*, lb.awarded_on FROM learner_badges lb
     JOIN badges b ON b.id=lb.badge_id WHERE lb.learner_id=? ORDER BY lb.awarded_on'
);
$stmt->execute([$learner['id']]);
$earned = $stmt->fetchAll();

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = t('badge.title');
$navItems   = student_nav();
$signOut    = ['action' => app_url('academy/logout.php'), 'csrf' => learner_csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head"><h1><?= e('badge.title') ?></h1></div>

<?php if (!$earned): ?>
    <div class="academy-card"><p><?= e('badge.none') ?></p></div>
<?php else: ?>
    <div class="academy-grid">
    <?php foreach ($earned as $badge): ?>
        <article class="lesson-card is-done">
            <span class="pill pill-done"><?= htmlspecialchars($badge['icon']) ?></span>
            <h3><?= htmlspecialchars($badge['title']) ?></h3>
            <p><?= htmlspecialchars($badge['criteria']) ?></p>
            <p class="field-hint"><?= e('badge.earned_on', ['date' => date('j M Y', strtotime($badge['awarded_on']))]) ?></p>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
