<?php
require_once 'includes/auth.php'; require_once 'includes/db.php';
if(is_logged_in()){ header('Location: '.app_url(safe_return_path())); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf(); $email=strtolower(trim((string)($_POST['email'] ?? ''))); $password=(string)($_POST['password'] ?? '');
  $stmt=db()->prepare('SELECT id,password_hash FROM users WHERE email=?'); $stmt->execute([$email]); $user=$stmt->fetch();
  if($user && password_verify($password,$user['password_hash'])){ login_user((int)$user['id']); header('Location: '.app_url(safe_return_path())); exit; }
  $error='Email or password was not recognized.';
}
$pageTitle='Sponsor Sign In'; $pageDescription='Sign in to view sponsorship status and student learning updates.'; include 'includes/header.php';
?>
<section class="section cream"><div class="wrap"><form class="form-card auth-card" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="return" value="<?= htmlspecialchars(safe_return_path()) ?>"><span class="eyebrow">Sponsor portal</span><h1>Welcome back.</h1><p>Sign in to see contribution status and the latest privacy-safe learning updates.</p>
<?php if(isset($_GET['reset']) && $_GET['reset']==='1'): ?><div class="alert success" role="status"><strong>Password updated!</strong><span>Your password has been reset successfully. Please sign in below.</span></div><?php endif; ?>
<?php if($error): ?><div class="alert error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<label>Email address<input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<div style="display:flex;justify-content:flex-end;margin:-8px 0 16px"><a href="<?= app_url('forgot_password.php') ?>" style="font-size:0.88rem;color:var(--forest-2);font-weight:700">Forgot password?</a></div>
<button class="button full" type="submit">Sign in <span aria-hidden="true">→</span></button><p class="account-prompt">New sponsor? <a href="<?= app_url('adopt.php') ?>">Choose a student journey</a></p></form></div></section>
<?php include 'includes/footer.php'; ?>
