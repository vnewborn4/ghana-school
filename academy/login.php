<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/audit.php';

learner_session_start();
if (current_learner_id() !== null) { header('Location: ' . app_url('academy/index.php')); exit; }

$error = null;
$timedOut = !empty($_SESSION['timed_out']);
unset($_SESSION['timed_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_learner_csrf();
    if (!rate_limit('learner_login', $_SERVER['REMOTE_ADDR'] ?? 'cli', 20, 300)) {
        $error = 'common.problem';
    } else {
        $reason = null;
        $learner = attempt_learner_login((string)($_POST['username'] ?? ''), (string)($_POST['pin'] ?? ''), $reason);
        if ($learner) {
            login_learner((int)$learner['id']);
            award_badge((int)$learner['id'], 'first-sign-in');
            audit('learner.login', [
                'actor_learner_id' => (int)$learner['id'],
                'subject_type' => 'learner',
                'subject_id' => (int)$learner['id'],
            ]);
            header('Location: ' . app_url('academy/index.php'));
            exit;
        }
        $error = $reason ?? 'login.error';
    }
}

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = t('login.title');
$navItems   = [];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-signin">
    <div class="academy-head">
        <h1><?= e('login.title') ?></h1>
        <p><?= e('login.intro') ?></p>
    </div>

    <?php if ($timedOut): ?>
        <div class="notice notice-info"><?= e('login.shared_computer') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice notice-bad"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="academy-card">
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
            <div class="field">
                <label for="username"><?= e('login.username') ?></label>
                <input id="username" name="username" type="text" required autofocus
                       inputmode="text" autocapitalize="none" spellcheck="false"
                       maxlength="40" value="<?= htmlspecialchars((string)($_POST['username'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="pin"><?= e('login.pin') ?></label>
                <input id="pin" name="pin" type="password" required inputmode="numeric"
                       class="pin-input" maxlength="12" autocomplete="off">
            </div>
            <button class="button-big" type="submit"><?= e('login.submit') ?></button>
        </form>
    </div>

    <p class="field-hint"><?= e('login.help_note') ?></p>
    <p class="field-hint"><?= e('login.shared_computer') ?></p>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
