<?php include 'includes/header.php'; ?>

<section class="py-5 bg-light text-center">
    <div class="container py-5">
        <h1 class="fw-bold mb-3">Support a Child's Educational Journey</h1>
        <p class="lead text-muted max-w-700 mx-auto">
            Your support ensures that a child in Accra has uninterrupted access to technology education, mentorship, and the tools they need to build a better future.
        </p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <form action="process_adoption.php" method="POST" id="adoptionForm">
            
            <h3 class="fw-bold mb-4 text-center">Step 1: Choose Your Contribution</h3>
            
            <!-- Frequency Selection -->
            <div class="d-flex justify-content-center gap-3 mb-5">
                <label class="cursor-pointer">
                    <input type="radio" name="frequency" value="recurring" class="d-none peer-radio-freq" checked>
                    <div class="btn btn-outline-primary px-4 py-2 freq-btn fw-bold">Monthly Recurring</div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="frequency" value="one-time" class="d-none peer-radio-freq">
                    <div class="btn btn-outline-primary px-4 py-2 freq-btn fw-bold">One-Time Donation</div>
                </label>
            </div>

            <!-- Tier Selection -->
            <div class="row justify-content-center mb-5">
                <!-- Tier 1 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <label class="w-100 cursor-pointer h-100">
                        <input type="radio" name="tier" value="25" class="d-none peer-radio" required>
                        <div class="card adoption-card h-100 p-4 text-center d-flex flex-column">
                            <h4 class="fw-bold mt-3">Sponsor</h4>
                            <div class="display-5 fw-bold text-primary my-3">$25<span class="fs-6 text-muted fw-normal freq-label">/mo</span></div>
                            <p class="text-muted mb-4 small">Provides basic learning materials and lab access.</p>
                            <div class="mt-auto btn btn-outline-primary select-btn">Select</div>
                        </div>
                    </label>
                </div>

                <!-- Tier 2 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <label class="w-100 cursor-pointer h-100">
                        <input type="radio" name="tier" value="50" class="d-none peer-radio">
                        <div class="card adoption-card h-100 p-4 text-center border-primary position-relative d-flex flex-column">
                            <div class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3 py-2">Most Popular</div>
                            <h4 class="fw-bold mt-3">Full Journey</h4>
                            <div class="display-5 fw-bold text-primary my-3">$50<span class="fs-6 text-muted fw-normal freq-label">/mo</span></div>
                            <p class="text-muted mb-4 small">Fully funds education, providing dedicated resources.</p>
                            <div class="mt-auto btn btn-outline-primary select-btn">Select</div>
                        </div>
                    </label>
                </div>

                <!-- Tier 3 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <label class="w-100 cursor-pointer h-100">
                        <input type="radio" name="tier" value="100" class="d-none peer-radio">
                        <div class="card adoption-card h-100 p-4 text-center d-flex flex-column">
                            <h4 class="fw-bold mt-3">Builder</h4>
                            <div class="display-5 fw-bold text-primary my-3">$100<span class="fs-6 text-muted fw-normal freq-label">/mo</span></div>
                            <p class="text-muted mb-4 small">Support multiple students and help expand infrastructure.</p>
                            <div class="mt-auto btn btn-outline-primary select-btn">Select</div>
                        </div>
                    </label>
                </div>

                <!-- Custom Amount -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <label class="w-100 cursor-pointer h-100">
                        <input type="radio" name="tier" value="custom" id="customTierRadio" class="d-none peer-radio">
                        <div class="card adoption-card h-100 p-4 text-center d-flex flex-column">
                            <h4 class="fw-bold mt-3">Custom</h4>
                            <div class="my-3">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="custom_amount" id="customAmountInput" class="form-control form-control-lg text-center fw-bold" placeholder="0" min="1">
                                </div>
                            </div>
                            <p class="text-muted mb-4 small">Give whatever you can to support the children.</p>
                            <div class="mt-auto btn btn-outline-primary select-btn">Select</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Step 2: Donor Info -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm p-4 mb-5">
                        <h3 class="fw-bold mb-4">Step 2: Your Information</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Payment Method -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm p-4 text-center">
                        <h3 class="fw-bold mb-4">Step 3: Payment Method</h3>
                        <div class="d-flex justify-content-center gap-4 mb-4 flex-wrap">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="stripe" class="d-none peer-radio-pay" required>
                                <div class="btn btn-outline-dark px-4 py-3 payment-btn">
                                    <strong>Credit Card (Stripe)</strong>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="paypal" class="d-none peer-radio-pay">
                                <div class="btn btn-outline-info px-4 py-3 payment-btn">
                                    <strong>PayPal</strong>
                                </div>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 py-3">Complete Contribution</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</section>

<style>
/* Custom styles for the selectable cards */
.cursor-pointer { cursor: pointer; }

/* Tier cards */
.peer-radio:checked + .adoption-card {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}
.peer-radio:checked + .adoption-card .select-btn {
    background-color: #0d6efd;
    color: white;
    content: "Selected";
}

/* Payment buttons */
.peer-radio-pay:checked + .payment-btn {
    background-color: #343a40;
    color: white;
}
input[value="paypal"].peer-radio-pay:checked + .payment-btn {
    background-color: #0dcaf0;
    color: black;
}

/* Frequency buttons */
.peer-radio-freq:checked + .freq-btn {
    background-color: #0d6efd;
    color: white;
}
</style>

<script>
// Change button text when tier is selected
document.querySelectorAll('input[name="tier"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.select-btn').forEach(btn => btn.textContent = 'Select');
        if (this.checked) {
            this.nextElementSibling.querySelector('.select-btn').textContent = 'Selected ✓';
        }
    });
});

// Select custom radio when input is focused
document.getElementById('customAmountInput').addEventListener('focus', function() {
    document.getElementById('customTierRadio').checked = true;
    document.getElementById('customTierRadio').dispatchEvent(new Event('change'));
});

// Update the "/mo" labels based on frequency selection
document.querySelectorAll('input[name="frequency"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const isRecurring = this.value === 'recurring';
        document.querySelectorAll('.freq-label').forEach(label => {
            label.style.display = isRecurring ? 'inline' : 'none';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
