<?php
$pageTitle='Choose a Student Journey';
$pageDescription='Choose a privacy-safe student learning journey to support in Accra.';
require_once 'includes/db.php';
$journeys=[]; $loadError=false;
try { $journeys=db()->query("SELECT * FROM student_journeys WHERE active=1 ORDER BY id")->fetchAll(); } catch (Throwable $e) { $loadError=true; }
// Nothing on this page is personal, so it may be kept by the visitor's
// own browser and revalidated. See the cache policy in includes/header.php.
$publicPage = true;
include 'includes/header.php';
?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Step 1 of 2 · Choose a journey</span><h1>Support the learner. Protect the child.</h1><p class="lede">Choose a learning journey based on interests and goals. Profiles use privacy-safe labels—never full names, schools, family circumstances, or direct contact details.</p></div></section>
<section class="section"><div class="wrap">
<?php if ($loadError): ?><div class="alert error"><strong>The sponsorship portal needs its local database.</strong><span>Import <code>database.sql</code>, then refresh this page.</span></div>
<?php else: ?><div class="journey-grid">
<?php foreach($journeys as $index=>$journey): ?><article class="journey-card"><div class="journey-visual" aria-hidden="true"><span><?= htmlspecialchars(mb_strtoupper(mb_substr($journey['first_name'],0,1))) ?></span><small>Learning journey</small></div><div class="journey-body"><span class="journey-band"><?= htmlspecialchars($journey['age_band']) ?></span><h2><?= htmlspecialchars($journey['first_name']) ?></h2><p><?= htmlspecialchars($journey['profile_summary']) ?></p><dl><div><dt>Interested in</dt><dd><?= htmlspecialchars($journey['interest_area']) ?></dd></div><div><dt>Favorite subject</dt><dd><?= htmlspecialchars($journey['favorite_subject'] ?: 'Not yet added') ?></dd></div><div><dt>Strengths</dt><dd><?= htmlspecialchars($journey['strengths'] ?: 'Not yet added') ?></dd></div><div><dt>Current focus</dt><dd><?= htmlspecialchars($journey['current_focus']) ?></dd></div><div><dt>Looking ahead</dt><dd><?= htmlspecialchars($journey['aspirations'] ?: 'Not yet added') ?></dd></div></dl><a class="button" href="signup.php?journey=<?= urlencode($journey['public_code']) ?>">Sponsor <?= htmlspecialchars($journey['first_name']) ?>’s journey <span aria-hidden="true">→</span></a></div></article><?php endforeach; ?>
</div><?php endif; ?>
<div class="privacy-note"><strong>Safeguarding by design:</strong> A sponsorship creates an educational connection, not private access to a child. Updates are reviewed, non-identifying, and shared through the donor dashboard.</div>
</div></section>
<?php include 'includes/footer.php'; ?>
