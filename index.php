<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section text-center d-flex align-items-center" style="min-height: 80vh;">
    <div class="container">
        <h1 class="display-3 fw-bold mb-4 text-shadow">Technology: The Universal Equalizer.</h1>
        <p class="lead mb-5 fs-3 mx-auto text-shadow" style="max-width: 800px;">
            It doesn't matter where you were born. A computer doesn't care about your zip code or background. We are equipping the youth of Accra, Ghana with the universal language of technology—leveling the playing field and empowering them to engineer their own futures.
        </p>
        <a href="adopt.php" class="btn btn-primary btn-lg fw-bold px-5 py-3 shadow-lg fs-4">Become a Catalyst for Change</a>
    </div>
</section>

<!-- Mission Section -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4 display-5">Genius is Everywhere. <br><span class="text-primary">Opportunity is Not.</span></h2>
                <p class="lead text-dark fw-semibold">
                    Do you remember the story of <em>Akeelah and the Bee</em>? It taught us a profound truth: brilliance doesn't care about your zip code, your background, or how much money your parents make. When a child with drive is handed the right tools and a community that believes in them, they can achieve the impossible.
                </p>
                <p class="text-muted fs-5">
                    In the bustling neighborhoods of Accra, Ghana, there are thousands of children with that same unyielding spark. They are future software engineers, data scientists, and digital innovators. But without access to computers, internet, and mentorship, that potential remains locked away.
                </p>
                <p class="text-muted fs-5">
                    Partnering with the <strong>AD2 Alumni Foundation</strong>, we are changing that narrative. We aren't just teaching kids how to code; we are teaching them how to build, how to dream, and how to command their place in the global digital economy. Technology is the great equalizer, and we are handing them the keys.
                </p>
                <a href="about.php" class="btn btn-outline-primary btn-lg mt-3 fw-bold px-4">Read Our Origin Story</a>
            </div>
            <div class="col-lg-6">
                <!-- Using one of the uploaded WhatsApp images -->
                <img src="assets/images/student-2.jpeg" alt="Student learning at Mill Creek-AR Learning Center" class="img-fluid rounded shadow-lg border border-4 border-white">
            </div>
        </div>
    </div>
</section>

<!-- Impact Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-5">
        <h2 class="fw-bold mb-5 display-5">What Your Support Actually Does</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4">
                    <h1 class="display-1 fw-bold mb-3 opacity-50">1</h1>
                    <h3 class="fw-bold">Digital Literacy</h3>
                    <p class="fs-5">We take students who have never touched a keyboard and transform them into confident digital citizens, ready for the modern workforce.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <h1 class="display-1 fw-bold mb-3 opacity-50">2</h1>
                    <h3 class="fw-bold">Global Competitiveness</h3>
                    <p class="fs-5">By teaching universal programming languages and tech skills, we give these young minds the ability to compete for jobs on a global scale, right from Ghana.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <h1 class="display-1 fw-bold mb-3 opacity-50">3</h1>
                    <h3 class="fw-bold">Breaking the Cycle</h3>
                    <p class="fs-5">A single tech job can lift an entire family out of poverty. Your donation doesn't just buy a computer; it breaks generational cycles of hardship.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Showcase Gallery -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-5">Witness the Transformation</h2>
            <p class="lead text-muted mx-auto" style="max-width: 700px;">Every line of code written, every program successfully launched—these are the moments where confidence is born. See the faces of the next generation of innovators.</p>
        </div>
        <div class="row g-4">
            <?php 
            // Scan the images directory to dynamically load the gallery
            $dir = "assets/images/";
            $images = glob($dir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            foreach($images as $image): 
            ?>
            <div class="col-md-4">
                <img src="<?php echo $image; ?>" alt="Student Showcase" class="gallery-img shadow-sm w-100">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5" style="background: linear-gradient(135deg, #212529, #000); color: white;">
    <div class="container py-5 text-center">
        <h2 class="fw-bold mb-4 display-4">Don't Just Watch the Future. Shape It.</h2>
        <p class="lead mb-5 mx-auto fs-4" style="max-width: 800px;">
            The difference between a brilliant mind lost to circumstance and a brilliant mind that changes the world is often just one person deciding to care. Be that person today.
        </p>
        <a href="adopt.php" class="btn btn-primary btn-lg fw-bold px-5 py-3 shadow-lg fs-4">Adopt a Child's Journey Now</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
