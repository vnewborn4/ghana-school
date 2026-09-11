<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (is_logged_in()) {
    header('Location: ' . app_url('portal.php'));
    exit;
}

$submitted = false;
$devResetUrl = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, first_name FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);

                // Invalidate any previous reset requests for this user
                $del = db()->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $del->execute([(int)$user['id']]);

                // Store new hashed token with 1-hour expiration
                $ins = db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
                $ins->execute([(int)$user['id'], $tokenHash]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $resetUrl = $scheme . $host . app_url('reset_password.php?token=' . urlencode($rawToken));

                // Send reset email
                $subject = 'Reset your password - Mill Creek-AR Learning Center';
                $message = "Hello " . $user['first_name'] . ",\n\n"
                    . "We received a request to reset your password for your Mill Creek-AR Learning Center sponsor account.\n\n"
                    . "Click the link below to choose a new password (valid for 1 hour):\n"
                    . $resetUrl . "\n\n"
                    . "If you did not request this, you can safely ignore this email.\n\n"
                    . "Warmly,\nMill Creek-AR Learning Center Team";
                $headers = "From: no-reply@" . parse_url($scheme . $host, PHP_URL_HOST) . "\r\n"
                    . "X-Mailer: PHP/" . phpversion();

                $mailSent = @mail($email, $subject, $message, $headers);
                if (!$mailSent || in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
                    $devResetUrl = $resetUrl;
                }
            }
            $submitted = true;
        } catch (Throwable $e) {
            $error = 'Unable to process reset request. Please try again later.';
        }
    }
}

$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your password for the Mill Creek-AR Learning Center sponsor portal.';
include 'includes/header.php';
?>
<section class="section cream">
  <div class="wrap">
    <div class="form-card auth-card">
      <span class="eyebrow">Account Recovery</span>
      <h1>Reset your password</h1>
      
      <?php if ($submitted): ?>
        <div class="alert success" role="status">
          <strong>Check your email</strong>
          <span>If an account exists for <strong><?= htmlspecialchars($email) ?></strong>, we have sent instructions to reset your password. The link will remain active for 1 hour.</span>
        </div>
        <?php if ($devResetUrl): ?>
          <div class="draft-note" style="margin-top:16px;">
            <strong>Local Development Helper:</strong>
            <p style="margin:6px 0 0;font-size:0.88rem;word-break:break-all;">
              Mail server is not configured locally. You can test your reset link directly here:<br>
              <a href="<?= htmlspecialchars($devResetUrl) ?>"><strong><?= htmlspecialchars($devResetUrl) ?></strong></a>
            </p>
          </div>
        <?php endif; ?>
        <p class="account-prompt" style="margin-top:24px;">
          <a href="<?= app_url('login.php') ?>">Return to Sign In</a>
        </p>
      <?php else: ?>
        <p>Enter your account email address and we will send you a secure link to create a new password.</p>
        <?php if ($error): ?>
          <div class="alert error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <label>Email address
            <input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </label>
          <button class="button full" type="submit">Send reset link <span aria-hidden="true">→</span></button>
        </form>
        <p class="account-prompt">Remember your password? <a href="<?= app_url('login.php') ?>">Sign in</a></p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
