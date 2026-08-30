<?php include 'includes/header.php'; ?>

<section class="py-5 bg-white text-center">
    <div class="container py-5">
        <div class="card shadow-lg p-5 max-w-700 mx-auto" style="max-width: 600px;">
            <div class="mb-4 text-primary">
                <!-- Simple credit card icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
                  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/>
                  <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z"/>
                </svg>
            </div>
            <h2 class="fw-bold mb-3">Stripe Checkout Integration</h2>
            <p class="lead text-muted">Contribution ID: #<?php echo htmlspecialchars($_GET['sub_id'] ?? ''); ?></p>
            
            <p class="display-6 fw-bold text-dark my-4">
                $<?php echo htmlspecialchars($_GET['amount'] ?? ''); ?> 
                <?php echo ($_GET['type'] ?? '') === 'recurring' ? '<span class="fs-4 text-muted">/ month</span>' : '<span class="fs-4 text-muted">(One-Time)</span>'; ?>
            </p>
            
            <div class="alert alert-warning text-start">
                <strong>Developer Note:</strong> To make this live, you need to:
                <ol class="mb-0 mt-2">
                    <li>Install the Stripe PHP SDK via Composer.</li>
                    <li>Add your Stripe Secret Key to <code>process_adoption.php</code>.</li>
                    <li>Create a Checkout Session (either `mode => 'payment'` or `mode => 'subscription'`) and redirect the user.</li>
                    <li>Set up a webhook to listen for completions.</li>
                </ol>
            </div>

            <a href="index.php" class="btn btn-outline-secondary mt-3">Return Home</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
