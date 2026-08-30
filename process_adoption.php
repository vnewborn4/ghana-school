<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $frequency = $_POST['frequency'] ?? 'recurring';
    $isRecurring = ($frequency === 'recurring') ? 1 : 0;
    
    $tier = $_POST['tier'] ?? 0;
    $customAmount = $_POST['custom_amount'] ?? 0;
    
    // Determine final amount and tier name
    if ($tier === 'custom') {
        $amount = (float)$customAmount;
        $tierName = 'Custom Amount';
    } else {
        $amount = (float)$tier;
        $tierNames = [
            '25' => 'Sponsor',
            '50' => 'Full Journey',
            '100' => 'Builder'
        ];
        $tierName = $tierNames[$tier] ?? 'Unknown';
    }

    // Ensure amount is valid
    if ($amount <= 0) {
        die("Invalid contribution amount. Please go back and try again.");
    }

    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'stripe';

    try {
        // 1. Check if donor exists or create new
        $stmt = $pdo->prepare("SELECT id FROM donors WHERE email = ?");
        $stmt->execute([$email]);
        $donor = $stmt->fetch();

        if ($donor) {
            $donorId = $donor['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO donors (first_name, last_name, email) VALUES (?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email]);
            $donorId = $pdo->lastInsertId();
        }

        // 2. Create pending contribution record
        $stmt = $pdo->prepare("INSERT INTO contributions (donor_id, tier_name, amount, is_recurring, payment_gateway, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$donorId, $tierName, $amount, $isRecurring, $paymentMethod]);
        $contributionId = $pdo->lastInsertId();

        // 3. Redirect to the appropriate gateway logic
        $redirectUrl = ($paymentMethod === 'stripe') ? "mock_stripe_checkout.php" : "mock_paypal_checkout.php";
        
        // Pass details to mock page
        $freqLabel = $isRecurring ? 'recurring' : 'one-time';
        header("Location: $redirectUrl?sub_id=$contributionId&amount=$amount&type=$freqLabel");
        exit;

    } catch (PDOException $e) {
        die("Error processing request: " . $e->getMessage());
    }
} else {
    header("Location: adopt.php");
    exit;
}
?>
