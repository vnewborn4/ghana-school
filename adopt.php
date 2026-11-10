<?php
$pageTitle='Support Technology Learning';
$pageDescription='Give any amount or choose a privacy-safe student learning journey to support in Accra.';
require_once 'includes/db.php';
$journeys=[]; $loadError=false;
try { $journeys=db()->query("SELECT * FROM student_journeys WHERE active=1 ORDER BY id")->fetchAll(); } catch (Exception $e) { $loadError=true; }
include 'includes/header.php';
?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Give or sponsor a learning journey</span><h1>Help keep technology learning within reach.</h1><p class="lede">Give any amount to support the learning center, or choose a privacy-safe student journey based on interests and goals. Profiles never publish full names, schools, family circumstances, or direct contact details.</p></div></section>
<section class="section"><div class="wrap">
<div class="zeffy-banner" style="background:#fff;border:2px solid var(--forest);border-radius:var(--radius);padding:26px 32px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:30px;box-shadow:var(--shadow);flex-wrap:wrap">
  <div>
    <span class="eyebrow" style="margin-bottom:6px">Every Gift Counts &bull; 100% Free for Nonprofits &bull; Cards, Apple Pay, Google Pay</span>
    <h3 style="margin:0;font-size:1.45rem">Support with a one-time gift of any amount</h3>
    <p style="margin:6px 0 0;color:var(--muted);max-width:640px">Whether it's $5, $15, $25, or whatever you can spare, no one is priced out. Every single dollar directly powers computers, broadband internet, and code instruction for young learners in Accra. Zero platform fees are deducted.</p>
  </div>
  <a class="button button-gold" href="https://www.zeffy.com/en-US/donation-form/donate-to-change-lives-through-it" target="_blank" rel="noopener">Give Any Amount on Zeffy <span aria-hidden="true">↗</span></a>
</div>

<!-- Institutional Giving & Corporate Match Strip -->
<div class="institutional-strip" style="background:#eef6f3;border:1px solid #c2dbd3;border-radius:var(--radius);padding:20px 24px;margin-bottom:36px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px">
  <div style="max-width:620px">
    <strong style="color:var(--forest);font-size:1.02rem;display:block;margin-bottom:4px">Double Your Impact With Employer Matching</strong>
    <span style="color:var(--forest-2);font-size:0.88rem;line-height:1.4;display:block">Many companies (Google, Microsoft, Apple, Cisco, AT&T) match employee donations 1:1 or 2:1. Search for <strong>AD2 Alumni Foundation</strong> in your Benevity, CyberGrants, or YourCause workplace giving portal.</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-family:monospace;font-size:0.92rem;font-weight:700;color:var(--forest);background:#fff;padding:8px 14px;border-radius:8px;border:1px solid var(--line)">EIN: 47-1468217</span>
    <button type="button" class="button button-gold copy-ein-btn" data-ein="47-1468217" style="padding:8px 16px;font-size:0.86rem">Copy EIN</button>
  </div>
</div>

<?php if ($loadError): ?>
<div class="alert note" style="margin-bottom:24px"><strong>Individual student profile sponsorships will activate shortly.</strong><span>In the meantime, you can make an immediate tax-deductible contribution to the learning center using the official Zeffy campaign above.</span></div>
<?php else: ?>
<div class="journey-grid">
<?php foreach($journeys as $index=>$journey): ?>
    <article class="journey-card">
        <div class="journey-visual" aria-hidden="true">
            <?php if (!empty($journey['image_url'])): ?>
                <img src="<?= htmlspecialchars(app_url($journey['image_url'])) ?>" alt="<?= htmlspecialchars($journey['first_name']) ?>" loading="lazy">
            <?php else: ?>
                <span><?= htmlspecialchars(mb_strtoupper(mb_substr($journey['first_name'],0,1))) ?></span>
                <small>Learning journey</small>
            <?php endif; ?>
        </div>
        <div class="journey-body">
            <span class="journey-band"><?= htmlspecialchars($journey['age_band']) ?></span>
            <h2><?= htmlspecialchars($journey['first_name']) ?></h2>
            <p><?= htmlspecialchars($journey['profile_summary']) ?></p>
            <dl>
                <div><dt>Interested in</dt><dd><?= htmlspecialchars($journey['interest_area']) ?></dd></div>
                <div><dt>Favorite subject</dt><dd><?= htmlspecialchars($journey['favorite_subject'] ?: 'Not yet added') ?></dd></div>
                <div><dt>Strengths</dt><dd><?= htmlspecialchars($journey['strengths'] ?: 'Not yet added') ?></dd></div>
                <div><dt>Current focus</dt><dd><?= htmlspecialchars($journey['current_focus']) ?></dd></div>
                <div><dt>Looking ahead</dt><dd><?= htmlspecialchars($journey['aspirations'] ?: 'Not yet added') ?></dd></div>
            </dl>
            <a class="button" href="signup.php?journey=<?= urlencode($journey['public_code']) ?>">Sponsor <?= htmlspecialchars($journey['first_name']) ?>’s journey <span aria-hidden="true">→</span></a>
        </div>
    </article>
<?php endforeach; ?>
</div><?php endif; ?>
<div class="privacy-note"><strong>Safeguarding by design:</strong> A sponsorship creates an educational connection, not private access to a child. Updates are reviewed, non-identifying, and shared through the donor dashboard.</div>
</div></section>
<?php include 'includes/footer.php'; ?>
