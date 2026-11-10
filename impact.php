<?php $pageTitle='Opportunity Through Technology'; $pageDescription='A dignity-centered look at the barriers young people face and the pathways technology education can open in Ghana.'; include 'includes/header.php'; ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow">Why technology, why now</span><h1>Skills that can travel across industries and borders.</h1><p class="lede">Technology education does not guarantee a particular outcome. It does expand the set of problems a young person can solve, the ideas they can create, and the opportunities they can pursue.</p></div></section>
<section class="section"><div class="wrap grid-2"><div><span class="eyebrow">The challenge</span><h2>Talent can be abundant while access remains uneven.</h2><p>Young people in Ghana navigate a changing economy where digital fluency increasingly matters. Yet access to dependable devices, connectivity, current instruction, and professional networks can vary widely.</p><p>Those barriers do not reflect a lack of ambition or ability. They reflect a gap in opportunity. A dignity-centered response starts by listening, investing in local instruction, and building consistent places to learn and practice.</p></div><div class="challenge-list"><div class="challenge-item"><strong>Reliable access</strong><p>Shared or unavailable devices can limit the repeated practice real learning requires.</p></div><div class="challenge-item"><strong>From use to creation</strong><p>Knowing how to consume digital content is different from learning to build, analyze, design, and solve.</p></div><div class="challenge-item"><strong>Pathways and networks</strong><p>Mentors, project experience, and exposure to possible careers help turn skills into informed next steps.</p></div></div></div></section>
<section class="section forest"><div class="wrap"><span class="eyebrow light">Pathways, not promises</span><h2>What a sustained learning journey can build.</h2><div class="pathway" style="margin-top:42px"><div><strong>1. Access</strong><p>Time with tools and a stable learning environment.</p></div><div><strong>2. Practice</strong><p>Repeated work that turns unfamiliar tasks into usable skills.</p></div><div><strong>3. Projects</strong><p>Visible evidence of problem-solving, creativity, and growth.</p></div><div><strong>4. Possibility</strong><p>A stronger foundation for further education, work, or entrepreneurship.</p></div></div></div></section>
<section class="section"><div class="wrap grid-2">
  <div class="photo-card"><img src="<?= app_url('assets/images/classroom-evening-lab.jpeg') ?>" alt="Students engaged in evening technology lab" width="1280" height="720" loading="lazy"><div class="photo-caption">“A young person with code can lift an entire family.”</div></div>
  <div><span class="eyebrow">Skills That Travel</span><h2>From keyboard fluency to algorithmic thinking.</h2><p class="lede">Our students begin with foundational typing and computer mechanics, advancing rapidly to Python scripting, web structure, and logical problem solving.</p><p>By removing the cost and hardware barriers, we empower Ghanaian youth to participate as creators and builders in the global digital economy.</p><a class="button button-gold" href="adopt.php">Support technology learning <span aria-hidden="true">→</span></a></div>
</div></section>

<section class="section cream"><div class="wrap"><div class="statement"><span class="eyebrow">Family and community trajectory</span><h2>The value of opportunity can move outward.</h2><p class="lede" style="margin-inline:auto">When a young person gains confidence and useful skills, the benefits can extend beyond the individual: knowledge can be shared, younger learners can see a model, and new income pathways may strengthen a household. We speak of that possibility with hope—not guarantees—and measure progress honestly.</p></div><div class="grid-3" style="margin-top:45px"><article class="opportunity-card"><h3>Education</h3><p>A stronger base for advanced study, certifications, and independent learning.</p></article><article class="opportunity-card"><h3>Employment</h3><p>Preparation for digital tasks used across local and global workplaces.</p></article><article class="opportunity-card"><h3>Entrepreneurship</h3><p>Tools to communicate, design, manage, and test ideas that serve real needs.</p></article></div></div></section>

<!-- Interactive Student Code Sandbox -->
<section id="code-sandbox" class="section" style="background:#0a1e19;color:#fff">
  <div class="wrap">
    <div class="statement">
      <span class="eyebrow light" style="color:var(--gold)">Interactive Proof of Work</span>
      <h2 style="color:#fff">Run real Python code written by our students in Accra.</h2>
      <p class="lede" style="color:#d7e3df;margin-inline:auto">Select a student program below and click <strong>Run Python Script</strong> to execute their actual classroom coursework live.</p>
    </div>

    <div class="sandbox-container" style="max-width:880px;margin:36px auto 0;background:#112a23;border:1.5px solid rgba(240,184,75,0.3);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.5)">
      <div class="sandbox-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:#081814;border-bottom:1px solid rgba(255,255,255,0.1);flex-wrap:wrap;gap:10px">
        <div class="sandbox-tabs" style="display:flex;gap:8px">
          <button class="sandbox-tab-btn active" type="button" data-student="kwame" style="padding:6px 14px;border-radius:6px;border:none;background:var(--forest);color:#fff;font-size:0.82rem;font-weight:700;cursor:pointer">Kwame's Solar Tool</button>
          <button class="sandbox-tab-btn" type="button" data-student="kofi" style="padding:6px 14px;border-radius:6px;border:none;background:rgba(255,255,255,0.1);color:#cbdad5;font-size:0.82rem;font-weight:700;cursor:pointer">Kofi's Hardware Audit</button>
          <button class="sandbox-tab-btn" type="button" data-student="ama" style="padding:6px 14px;border-radius:6px;border:none;background:rgba(255,255,255,0.1);color:#cbdad5;font-size:0.82rem;font-weight:700;cursor:pointer">Ama's STEM Tracker</button>
        </div>
        <button id="run-code-btn" type="button" class="button button-gold" style="padding:8px 20px;font-size:0.88rem;display:flex;align-items:center;gap:6px">
          <span>▶</span> Run Python Script
        </button>
      </div>

      <div class="sandbox-editor-wrapper" style="display:grid;grid-template-columns:1fr;background:#0d221c">
        <pre id="code-display" style="margin:0;padding:20px;font-family:Consolas,Monaco,'Andale Mono','Ubuntu Mono',monospace;font-size:0.88rem;line-height:1.5;color:#85e89d;overflow-x:auto;max-height:280px"></pre>
      </div>

      <div class="sandbox-terminal" style="padding:16px 20px;background:#050c0a;border-top:1px solid rgba(255,255,255,0.1);font-family:Consolas,Monaco,monospace;font-size:0.85rem">
        <div style="color:var(--gold);font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:6px">Terminal Output (Python 3.11 Runtime)</div>
        <pre id="terminal-output" style="margin:0;color:#e2ece8;white-space:pre-wrap;line-height:1.4">Click "Run Python Script" above to execute Kwame's code...</pre>
      </div>
    </div>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
