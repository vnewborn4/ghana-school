<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/security.php';

$token = (string)($_GET['token'] ?? '');
$error = '';
$success = false;

if (!empty($token)) {
    $user_id = verify_email_token($token);
    if ($user_id) {
        // Mark email as verified in users table
        $stmt = db()->prepare('UPDATE users SET email_verified=1 WHERE id=?');
        $stmt->execute([$user_id]);
        $success = true;
    } else {
        $error = 'This verification link is invalid or has expired. Please create a new account or contact support.';
    }
} else {
    $error = 'No verification token provided.';
}

$pageTitle = 'Email Verification';
include 'includes/header.php';
?>

<section class="section cream" style="min-height: 60vh; display: flex; align-items: center;">
    <div class="wrap" style="max-width: 500px;">
        <?php if ($success): ?>
            <div style="text-align: center;">
                <h1 style="color: #133d34;">✓ Email Verified!</h1>
                <p style="font-size: 1.1rem; margin: 20px 0;">Your email address has been confirmed. You can now manage your sponsorship and receive learning updates.</p>
                <a href="<?= app_url('portal.php') ?>" class="button" style="display: inline-block; margin-top: 20px;">Go to Your Sponsor Portal</a>
            </div>
        <?php else: ?>
            <div style="text-align: center;">
                <h1 style="color: #d9534f;">Verification Failed</h1>
                <p style="font-size: 1.1rem; margin: 20px 0; color: #666;"><?= htmlspecialchars($error) ?></p>
                <div style="margin-top: 30px;">
                    <p><a href="<?= app_url('login.php') ?>">Sign In</a> if you already have an account.</p>
                    <p><a href="<?= app_url('adopt.php') ?>">Choose a Student</a> to create a new account.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
