<?php
$pageTitle='Secure Checkout Setup';
$provider = in_array($_GET['provider'] ?? '', ['stripe','paypal'], true) ? $_GET['provider'] : 'payment provider';
$frequency = ($_GET['frequency'] ?? '') === 'monthly' ? 'monthly' : 'one-time';
$amount = filter_var($_GET['amount'] ?? null, FILTER_VALIDATE_FLOAT);
include 'includes/header.php';
?>
<section class="section cream"><div class="wrap"><div class="checkout-card"><div class="checkout-icon" aria-hidden="true">✓</div><span class="eyebrow">No charge was made</span><h1 style="font-size:3rem">Your sponsorship choice is ready for secure checkout.</h1><p class="lede" style="margin-inline:auto">You selected a <?= htmlspecialchars($frequency) ?> gift of <strong>$<?= htmlspecialchars(number_format((float)$amount, 2)) ?></strong> through <?= htmlspecialchars(ucfirst($provider)) ?>.</p><div class="integration-note" style="text-align:left"><strong>This site is in pre-launch mode.</strong> The verified hosted payment link for this option has not been connected, so no financial information was requested and no donation was processed.</div><div class="button-row" style="justify-content:center;margin-top:25px"><a class="button" href="adopt.php">Choose another option</a><a class="button button-outline" href="index.php">Return home</a></div></div></div></section>
<?php include 'includes/footer.php'; ?>
