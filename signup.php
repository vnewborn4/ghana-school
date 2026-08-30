<?php
require_once 'includes/auth.php'; require_once 'includes/db.php'; require_once 'includes/payment_config.php';
$code=(string)($_POST['journey'] ?? $_GET['journey'] ?? '');
$stmt=db()->prepare("SELECT * FROM student_journeys WHERE public_code=? AND active=1"); $stmt->execute([$code]); $journey=$stmt->fetch();
if(!$journey){ header('Location: adopt.php'); exit; }
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $amount=filter_var($_POST['amount'] ?? null,FILTER_VALIDATE_FLOAT); $frequency=$_POST['frequency'] ?? ''; $provider=$_POST['payment_method'] ?? '';
  if($amount===false || $amount<5 || $amount>100000)$errors[]='Enter a contribution amount of at least $5.';
  if(!in_array($frequency,['monthly','one-time'],true))$errors[]='Choose a contribution frequency.';
  if(!in_array($provider,['stripe','paypal'],true))$errors[]='Choose a payment provider.';
  $userId=is_logged_in()?(int)$_SESSION['user_id']:0;
  if(!$userId){
    $first=trim((string)($_POST['first_name'] ?? '')); $last=trim((string)($_POST['last_name'] ?? '')); $email=strtolower(trim((string)($_POST['email'] ?? ''))); $password=(string)($_POST['password'] ?? '');
    if($first==='' || mb_strlen($first)>80)$errors[]='Enter your first name.';
    if($last==='' || mb_strlen($last)>80)$errors[]='Enter your last name.';
    if(!filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>255)$errors[]='Enter a valid email address.';
    if(strlen($password)<10)$errors[]='Use a password with at least 10 characters.';
    if($password!==($_POST['password_confirm'] ?? ''))$errors[]='The passwords do not match.';
    if(!$errors){ $check=db()->prepare('SELECT id FROM users WHERE email=?'); $check->execute([$email]); if($check->fetch())$errors[]='An account already uses this email. Sign in, then choose the journey again.'; }
  }
  if(!$errors){
    $pdo=db(); $pdo->beginTransaction();
    try{
      if(!$userId){ $insert=$pdo->prepare('INSERT INTO users(first_name,last_name,email,password_hash) VALUES(?,?,?,?)'); $insert->execute([$first,$last,$email,password_hash($password,PASSWORD_DEFAULT)]); $userId=(int)$pdo->lastInsertId(); }
      $insert=$pdo->prepare('INSERT INTO sponsorships(user_id,student_journey_id,amount,frequency,payment_provider,status) VALUES(?,?,?,?,?,\'pending\')');
      $insert->execute([$userId,$journey['id'],$amount,$frequency,$provider]); $sponsorshipId=(int)$pdo->lastInsertId(); $pdo->commit(); login_user($userId);
      $link=payment_link($provider,$frequency,(float)$amount); if($link){ header('Location: '.$link,true,303); exit; }
      header('Location: checkout_pending.php?sponsorship_id='.$sponsorshipId,true,303); exit;
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $errors[]='We could not create the sponsorship. Please try again.'; }
  }
}
$pageTitle='Create Your Sponsor Account'; $pageDescription='Create a secure donor account and begin supporting a student learning journey.'; include 'includes/header.php';
?>
<section class="section cream"><div class="wrap signup-layout"><aside class="selected-journey"><span class="eyebrow">Your selection</span><div class="journey-visual compact" aria-hidden="true"><span><?= htmlspecialchars(substr($journey['display_name'],8,1)) ?></span><small>Learning journey</small></div><h2><?= htmlspecialchars($journey['display_name']) ?></h2><p><?= htmlspecialchars($journey['current_focus']) ?></p><a href="adopt.php">← Choose a different journey</a></aside>
<form class="form-card" method="post" novalidate><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="journey" value="<?= htmlspecialchars($code) ?>"><span class="eyebrow">Step 2 of 2</span><h1><?= is_logged_in()?'Confirm your sponsorship':'Create your sponsor account' ?></h1>
<?php if($errors): ?><div class="alert error" role="alert"><strong>Please review:</strong><ul><?php foreach($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if(!is_logged_in()): ?><div class="form-grid"><label>First name<input name="first_name" maxlength="80" autocomplete="given-name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"></label><label>Last name<input name="last_name" maxlength="80" autocomplete="family-name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"></label><label class="wide">Email address<input type="email" name="email" maxlength="255" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></label><label>Password<input type="password" name="password" minlength="10" autocomplete="new-password" required><small>At least 10 characters</small></label><label>Confirm password<input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required></label></div><p class="account-prompt">Already have an account? <a href="login.php?return=adopt.php">Sign in</a></p><?php endif; ?>
<hr><div class="field-group"><span class="field-label">Frequency</span><div class="segmented"><label><input type="radio" name="frequency" value="monthly" checked><span>Monthly</span></label><label><input type="radio" name="frequency" value="one-time"><span>One time</span></label></div></div><div class="field-group"><label class="field-label" for="amount">Contribution amount (USD)</label><div class="amount-grid"><button class="amount-btn" type="button" data-amount="25">$25</button><button class="amount-btn selected" type="button" data-amount="50">$50</button><button class="amount-btn" type="button" data-amount="100">$100</button></div><div class="amount-input"><span>$</span><input id="amount" name="amount" type="number" value="<?= htmlspecialchars($_POST['amount'] ?? '50') ?>" min="5" step="1" required></div></div><label class="field-label" for="payment_method">Payment option</label><select id="payment_method" name="payment_method" class="select-input"><option value="stripe">Card / digital wallet via Stripe</option><option value="paypal">PayPal</option></select><button class="button full" type="submit">Create sponsorship <span aria-hidden="true">→</span></button><p class="form-footnote">No card information is collected on this site. Until secure payment links are configured, the sponsorship appears as pending and no charge is made.</p></form></div></section>
<?php include 'includes/footer.php'; ?>
