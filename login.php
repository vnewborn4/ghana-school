<?php
require_once 'includes/auth.php'; require_once 'includes/db.php';
if(is_logged_in()){ header('Location: portal.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf(); $email=strtolower(trim((string)($_POST['email'] ?? ''))); $password=(string)($_POST['password'] ?? '');
  $stmt=db()->prepare('SELECT id,password_hash FROM users WHERE email=?'); $stmt->execute([$email]); $user=$stmt->fetch();
  if($user && password_verify($password,$user['password_hash'])){ login_user((int)$user['id']); header('Location: '.safe_return_path()); exit; }
  $error='Email or password was not recognized.';
}
$pageTitle='Sponsor Sign In'; $pageDescription='Sign in to view sponsorship status and student learning updates.'; include 'includes/header.php';
?>
<section class="section cream"><div class="wrap"><form class="form-card auth-card" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="return" value="<?= htmlspecialchars(safe_return_path()) ?>"><span class="eyebrow">Sponsor portal</span><h1>Welcome back.</h1><p>Sign in to see contribution status and the latest privacy-safe learning updates.</p><?php if($error): ?><div class="alert error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?><label>Email address<input type="email" name="email" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button full" type="submit">Sign in <span aria-hidden="true">→</span></button><p class="account-prompt">New sponsor? <a href="adopt.php">Choose a student journey</a></p></form></div></section>
<?php include 'includes/footer.php'; ?>
