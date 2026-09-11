<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (is_logged_in()) {
    header('Location: ' . app_url('portal.php'));
    exit;
}

$rawToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenValid = false;
$user = null;
$error = '';
$errors = [];

if ($rawToken !== '') {
    try {
        $tokenHash = hash('sha256', $rawToken);
        $stmt = db()->prepare('SELECT pr.id, pr.user_id, pr.expires_at, u.first_name, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ?');
        $stmt->execute([$tokenHash]);
        $record = $stmt->fetch();

        if ($record) {
            if (strtotime($record['expires_at']) >= time()) {
                $tokenValid = true;
                $user = $record;
            } else {
                $error = 'This password reset link has expired. Please request a new one.';
            }
        } else {
            $error = 'This password reset link is invalid or has already been used.';
        }
    } catch (Throwable $e) {
        $error = 'Database error checking reset token.';
    }
} else {
    $error = 'No reset token was provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');

    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$newHash, (int)$user['user_id']]);

            // Clear reset tokens
            $del = db()->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $del->execute([(int)$user['user_id']]);

            header('Location: ' . app_url('login.php?reset=1'));
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Unable to update password. Please try again.';
        }
    }
}

$pageTitle = 'Choose New Password';
$pageDescription = 'Set a new secure password for your Mill Creek-AR Learning Center sponsor account.';
include 'includes/header.php';
?>
<section class="section cream">
  <div class="wrap">
    <div class="form-card auth-card">
      <span class="eyebrow">Account Security</span>
      <h1>Create new password</h1>

      <?php if (!$tokenValid): ?>
        <div class="alert error" role="alert">
          <strong>Invalid Reset Link</strong>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
        <p class="account-prompt" style="margin-top:20px;">
          <a class="button" href="<?= app_url('forgot_password.php') ?>">Request a new reset link</a>
        </p>
      <?php else: ?>
        <p>Hello <strong><?= htmlspecialchars($user['first_name']) ?></strong>, enter your new password below (at least 10 characters).</p>

        <?php if ($errors): ?>
          <div class="alert error" role="alert">
            <strong>Please review:</strong>
            <ul>
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">
          <label>New password
            <input type="password" name="password" minlength="10" autocomplete="new-password" required>
            <small>At least 10 characters</small>
          </label>
          <label>Confirm new password
            <input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required>
          </label>
          <button class="button full" type="submit">Update password <span aria-hidden="true">→</span></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
