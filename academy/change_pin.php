<?php
require_once __DIR__ . '/../includes/learner_auth.php';
require_once __DIR__ . '/../includes/academy_nav.php';
require_once __DIR__ . '/../includes/audit.php';

learner_session_start();
$learner = require_learner();

$error = null; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_learner_csrf();
    $new = trim((string)($_POST['pin'] ?? ''));
    $confirm = trim((string)($_POST['pin_confirm'] ?? ''));

    if (strlen($new) < 4)                          $error = 'pin.too_short';
    elseif ($new !== $confirm)                     $error = 'pin.mismatch';
    elseif (in_array($new, ['1234','0000','1111','123456'], true)
            || preg_match('/^(\d)\1+$/', $new))    $error = 'pin.too_simple';

    if ($error === null) {
        $stmt = db()->prepare('UPDATE learners SET pin_hash=?, must_change_pin=0 WHERE id=?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $learner['id']]);
        audit('learner.pin_self_change', [
            'actor_learner_id' => (int)$learner['id'],
            'subject_type' => 'learner', 'subject_id' => (int)$learner['id'],
        ]);
        $done = true;
    }
}

$portalKind = 'student';
$portalName = t('app.academy');
$pageTitle  = t('pin.title');
$navItems   = [];
$signOut    = ['action' => app_url('academy/logout.php'), 'csrf' => learner_csrf_token()];
require __DIR__ . '/../includes/academy_header.php';
?>
<div class="academy-signin">
    <div class="academy-head">
        <h1><?= e('pin.title') ?></h1>
        <p><?= e('pin.intro') ?></p>
    </div>

    <?php if ($done): ?>
        <div class="notice notice-ok"><?= e('pin.changed') ?></div>
        <a class="button-big" href="<?= app_url('academy/index.php') ?>"><?= e('common.continue') ?></a>
    <?php else: ?>
        <?php if ($error): ?><div class="notice notice-bad"><?= e($error) ?></div><?php endif; ?>
        <div class="academy-card">
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(learner_csrf_token()) ?>">
                <div class="field">
                    <label for="pin"><?= e('pin.new') ?></label>
                    <input id="pin" name="pin" type="password" class="pin-input" required
                           inputmode="numeric" minlength="4" maxlength="12" autofocus>
                </div>
                <div class="field">
                    <label for="pin_confirm"><?= e('pin.confirm') ?></label>
                    <input id="pin_confirm" name="pin_confirm" type="password" class="pin-input" required
                           inputmode="numeric" minlength="4" maxlength="12">
                </div>
                <button class="button-big" type="submit"><?= e('pin.submit') ?></button>
            </form>
        </div>
        <p class="field-hint"><?= e('login.help_note') ?></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/academy_footer.php'; ?>
