<?php
/**
 * Printable welcome cards. The one-time PINs are held in the session by
 * teach/onboard.php and cleared as soon as this page renders, so they exist
 * on paper and nowhere else.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/academy_nav.php';

$user = require_role('teacher');

$created = $_SESSION['onboarded'] ?? [];
unset($_SESSION['onboarded']);

$host = $_SERVER['HTTP_HOST'] ?? 'millcreek-ar-learning.com';
$prefix = $host . (BASE_PATH !== '' ? BASE_PATH : '');

$portalKind = 'staff';
$portalName = 'Teacher portal';
$pageTitle  = 'Welcome cards';
$navItems   = staff_nav(is_admin_user($user));
$signOut    = ['action' => app_url('logout.php'), 'csrf' => csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-head no-print">
    <h1><?= count($created) ?> account<?= count($created) === 1 ? '' : 's' ?> created</h1>
    <p>Print this page, cut the cards, and hand each student their own.</p>
</div>

<?php if (!$created): ?>
    <div class="academy-card"><p>There is nothing to print. PINs are shown once, immediately after onboarding.</p>
    <a class="button-big" href="<?= app_url('teach/onboard.php') ?>">Onboard students</a></div>
<?php else: ?>

<div class="notice notice-warn no-print">
    <strong>These PINs are shown once.</strong> They are stored only as a hash and cannot be
    displayed again. If a card is lost, reset the PIN from the roster.
</div>

<div class="button-row-wrap no-print" style="margin-bottom:18px">
    <button class="button-big" type="button" id="print-cards">Print</button>
    <a class="button-big secondary" href="<?= app_url('teach/index.php') ?>">Back to roster</a>
</div>

<div class="card-sheet">
    <?php foreach ($created as $learner): ?>
    <article class="welcome-card">
        <h3>Akwaaba, <?= htmlspecialchars($learner['display_name']) ?></h3>
        <dl>
            <dt>Sign in at</dt><dd><?= htmlspecialchars($prefix) ?>/academy</dd>
            <dt>Username</dt><dd><?= htmlspecialchars($learner['username']) ?></dd>
            <dt>First PIN</dt><dd><?= htmlspecialchars($learner['pin']) ?></dd>
            <dt>Your page</dt><dd><?= htmlspecialchars($prefix) ?>/students/<?= htmlspecialchars($learner['username']) ?></dd>
        </dl>
        <p class="card-note">
            You will choose your own PIN the first time you sign in. Keep this card safe and
            never share your PIN. Your page is private until your teacher checks it.
        </p>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script defer src="<?= asset_url('assets/js/academy.js') ?>"></script>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
