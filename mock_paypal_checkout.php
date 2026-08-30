<?php include 'includes/header.php'; ?>

<section class="py-5 bg-white text-center">
    <div class="container py-5">
        <div class="card shadow-lg p-5 max-w-700 mx-auto" style="max-width: 600px;">
            <div class="mb-4 text-info">
                <!-- Simple Paypal icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-paypal" viewBox="0 0 16 16">
                  <path d="M14.06 3.713c.12-1.071-.464-1.796-1.57-1.933-1.12-.136-3.26-.114-4.59.103-1.25.2-1.92.936-2.103 1.95L4.933 9.475c-.062.435.25.823.682.823h2.387c.237 0 .445-.164.484-.396l.488-3.328c.036-.25.251-.433.504-.433h1.36c2.096 0 3.633-1.127 3.906-3.414.15-.99.167-1.637.167-1.637zm-2.73 4.88c-.68 2.062-2.585 2.583-4.576 2.583h-1.6c-.237 0-.445.164-.484.396l-.995 6.77c-.06.417.26.792.684.792h2.554c.237 0 .445-.164.484-.396l.492-3.35c.036-.25.251-.433.504-.433h1.12c1.776 0 3.327-.852 3.674-2.83.15-.99.167-1.637.167-1.637z"/>
                </svg>
            </div>
            <h2 class="fw-bold mb-3">PayPal Checkout Integration</h2>
            <p class="lead text-muted">Contribution ID: #<?php echo htmlspecialchars($_GET['sub_id'] ?? ''); ?></p>
            
            <p class="display-6 fw-bold text-dark my-4">
                $<?php echo htmlspecialchars($_GET['amount'] ?? ''); ?> 
                <?php echo ($_GET['type'] ?? '') === 'recurring' ? '<span class="fs-4 text-muted">/ month</span>' : '<span class="fs-4 text-muted">(One-Time)</span>'; ?>
            </p>
            
            <div class="alert alert-info text-start">
                <strong>Developer Note:</strong> To make this live, you need to:
                <ol class="mb-0 mt-2">
                    <li>Create a PayPal Developer account and get your Client ID.</li>
                    <li>Add the PayPal JavaScript SDK to your site.</li>
                    <li>Configure the PayPal buttons (using `createOrder` for one-time or `createSubscription` for recurring).</li>
                    <li>Use a webhook or the <code>onApprove</code> callback to update the database.</li>
                </ol>
            </div>

            <a href="index.php" class="btn btn-outline-secondary mt-3">Return Home</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
