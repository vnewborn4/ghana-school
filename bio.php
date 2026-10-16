<?php
$pageTitle = 'Van Newborn — Founder';
$pageDescription = 'Meet Van Newborn, the engineer whose journey from Mill Creek, Arkansas inspired a technology learning center in Accra.';
$pageImage = 'assets/images/van-newborn-square.jpeg';
include 'includes/header.php';
?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Meet the founder</span><h1>Van Newborn</h1><p class="lede">Engineering a bridge for the next generation—from a life shaped in Mill Creek, Arkansas to a commitment carried forward in Accra.</p></div></section>
<section class="section"><div class="wrap grid-2">
    <div class="founder-card">
        <picture>
            <source srcset="<?= app_url('assets/images/van-newborn.webp') ?>" type="image/webp">
            <img class="founder-photo" src="<?= app_url('assets/images/van-newborn.jpeg') ?>" alt="Van Newborn, Founder of Mill Creek-AR Learning Center" width="768" height="1024" loading="eager">
        </picture>
        <div class="founder-badge-tag">
            <div>
                <strong>Van Newborn</strong>
                <span>Founder &bull; Systems Engineer</span>
            </div>
            <img class="founder-crest-mini" src="<?= app_url('assets/images/mill-creek-crest.jpeg') ?>" alt="Mill Creek-AR Crest">
        </div>
    </div>
    <div>
        <h2>Making a way—and leaving the way wider.</h2>
        <p>I am Van Newborn. I grew up in Mill Creek, Arkansas, where resources could be limited but the strength of community was not. Education and technology gave me a path to become an engineer. Just as important were the people who saw possibility in me and encouraged me to keep going.</p>
        <p>That journey taught me that talent does not belong to one place, one income level, or one set of circumstances. What is unevenly distributed is access: access to tools, instruction, networks, and the chance to practice until confidence takes root.</p>
        <p>Mill Creek-AR Learning Center is my way of reaching back and reaching across. It connects the perseverance I learned in Arkansas with the creativity and ambition I see in young people in Accra.</p>
        <div class="pull-quote">“Success means more when it becomes a bridge someone else can cross.”</div>
    </div>
</div></section>
<section class="section cream"><div class="wrap grid-2"><div><span class="eyebrow">Lifting as we climb</span><h2>A tradition of education, self-determination, and community.</h2></div><div><p class="lede">Across the African diaspora, generations have used knowledge, mutual support, and persistence to expand what is possible. This project honors that tradition without turning struggle into spectacle.</p><p>The goal is not to prescribe a future for students. It is to equip them to shape their own—with practical skills, mentorship, and a community that takes their ambitions seriously.</p><a class="button button-outline" href="adopt.php">Help widen the bridge</a></div></div></section>
<?php include 'includes/footer.php'; ?>
