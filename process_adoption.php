<?php
require __DIR__ . '/includes/payment_config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: adopt.php'); exit; }
$frequency = $_POST['frequency'] ?? '';
$provider = $_POST['payment_method'] ?? '';
$amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
if (!in_array($frequency, ['monthly', 'one-time'], true) || !in_array($provider, ['stripe', 'paypal'], true) || $amount === false || $amount < 5 || $amount > 100000) {
    header('Location: adopt.php?error=invalid'); exit;
}
$link = payment_link($provider, $frequency, (float)$amount);
if ($link) { header('Location: ' . $link, true, 303); exit; }
$query = http_build_query(['provider'=>$provider,'frequency'=>$frequency,'amount'=>number_format((float)$amount,2,'.','')]);
header('Location: checkout_pending.php?' . $query, true, 303); exit;
