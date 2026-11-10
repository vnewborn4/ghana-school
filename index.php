<?php
$pageTitle = 'Opportunity Changes Everything';
$pageDescription = 'Help young people in Accra build technology skills, confidence, and pathways to opportunity.';
include 'includes/header.php';
?>
<section class="hero">
  <div class="hero-copy">
    <div class="hero-crest-badge">
      <img src="<?= app_url('assets/images/mill-creek-crest.jpeg') ?>" alt="Mill Creek-AR Crest" width="40" height="40">
      <span>Mill Creek-AR Learning Center · Accra</span>
    </div>
    <span class="eyebrow">From Mill Creek to Accra</span>
    <h1>Talent is everywhere. <em>Opportunity is not.</em></h1>
    <p>Mill Creek-AR Learning Center gives young people in Accra access to technology, practical instruction, and people who believe in what they can become.</p>
    <div class="button-row"><a class="button button-gold" href="adopt.php">Give any amount <span aria-hidden="true">→</span></a><a class="button button-outline" href="#lab-showcase">Watch classroom in action</a></div>
  </div>
  <div class="hero-media">
    <img src="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>" alt="Young learners in purple Mill Creek-AR polo uniforms coding on laptops in Accra" width="1280" height="720">
    <div class="hero-note"><strong>Access. Belief. Training. Opportunity.</strong><br><span>Four ingredients that can change the direction of a life.</span></div>
  </div>
</section>

<!-- Accra Lab Pulse & Campaign Goal Bar -->
<section class="lab-pulse-bar" aria-label="Classroom status and fundraising progress">
  <div class="wrap lab-pulse-grid">
    <div class="pulse-telemetry">
      <div class="pulse-indicator">
        <span class="pulse-dot"></span>
        <strong>LAB PULSE · ACCRA, GHANA</strong>
      </div>
      <div class="pulse-details">
        <span id="accra-clock" class="pulse-clock" data-tz="UTC">--:--:-- GMT</span>
        <span class="pulse-status">Classroom Active · Python 3 & Web Dev</span>
      </div>
    </div>
    <div class="pulse-progress">
      <div class="progress-labels">
        <span><strong>Semester Operations Goal:</strong> $5,000</span>
        <span class="progress-pct"><strong>$2,450 Raised</strong> (49% funded)</span>
      </div>
      <div class="progress-track" role="progressbar" aria-valuenow="49" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-fill" style="width:49%"></div>
      </div>
      <div class="progress-sub">
        <span>38 Backers · 12 Workstations Powered</span>
        <span>0% Platform Fees via Zeffy</span>
      </div>
    </div>
  </div>
</section>

<section class="trust-strip" aria-label="Program commitments"><div class="trust-grid wrap"><div><span>Our promise</span><strong>Long-term learning, not a one-day giveaway</strong></div><div><span>Location</span><strong>Accra, Ghana</strong></div><div><span>Focus</span><strong>Universal technology skills</strong></div><div><span>Partnership</span><strong>AD2 Alumni Foundation</strong></div></div></section>

<section id="mission" class="section"><div class="wrap statement"><span class="eyebrow">The mission</span><h2>A computer can become more than a tool. It can become a <span>doorway.</span></h2><p class="lede" style="margin-inline:auto">We are building a place where curiosity becomes capability—where young people can learn digital fundamentals, practice problem-solving, create projects, and begin to see themselves in the future of work.</p></div></section>

<!-- Inside the Lab: Real Video & Photo Showcase -->
<section id="lab-showcase" class="section cream">
  <div class="wrap">
    <div class="statement">
      <span class="eyebrow">Inside the Lab</span>
      <h2>See our students coding in Accra.</h2>
      <p class="lede" style="margin-inline:auto">From Python syntax to computer architecture, our students put their hands on the keys every single day. See the focus, dignity, and real skills taking shape.</p>
      <div class="lab-pills" style="justify-content:center">
        <span class="lab-pill">Python 3 Fundamentals</span>
        <span class="lab-pill">VS Code Development</span>
        <span class="lab-pill">Algorithm Logic</span>
        <span class="lab-pill">Keyboard Fluency & IT Basics</span>
      </div>
    </div>

    <!-- Video Showcase Player with Multi-Clip Playlist Switcher -->
    <div class="video-showcase-card">
      <div class="video-wrapper">
        <video id="main-showcase-video" controls playsinline preload="metadata" poster="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>">
          <source id="video-source" src="<?= app_url('assets/videos/classroom-showcase-1.mp4') ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      </div>

      <!-- Playlist Clip Switcher Tabs -->
      <div class="video-playlist-tabs" role="tablist" aria-label="Classroom video playlist">
        <button class="playlist-btn active" type="button" role="tab" aria-selected="true"
                data-src="<?= app_url('assets/videos/classroom-showcase-1.mp4') ?>"
                data-poster="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>"
                data-title="Live Classroom Session · Accra, Ghana"
                data-desc="Watch our students actively engaged in their technology lesson, working through programming problems together.">
          <span class="clip-icon">▶</span>
          <span class="clip-text"><strong>Session 1: Classroom Overview</strong><small>Lab Energy & Collaboration</small></span>
        </button>
        <button class="playlist-btn" type="button" role="tab" aria-selected="false"
                data-src="<?= app_url('assets/videos/classroom-showcase-2.mp4') ?>"
                data-poster="<?= app_url('assets/images/classroom-standing-lesson.jpeg') ?>"
                data-title="Instructor Guidance & Code Review · Accra"
                data-desc="Our instructor walking learners through Python control structures and algorithmic logic on the screen.">
          <span class="clip-icon">▶</span>
          <span class="clip-text"><strong>Session 2: Guided Instruction</strong><small>Syntax & Algorithmic Logic</small></span>
        </button>
        <button class="playlist-btn" type="button" role="tab" aria-selected="false"
                data-src="<?= app_url('assets/videos/IMG_3694.MOV') ?>"
                data-poster="<?= app_url('assets/images/python-vscode-laptop.jpeg') ?>"
                data-title="Focused Hands-on Coding & Execution"
                data-desc="Close-up of student hands on the keyboard writing syntax, debugging errors, and executing terminal commands.">
          <span class="clip-icon">▶</span>
          <span class="clip-text"><strong>Session 3: Hands on Keys</strong><small>Individual Code Execution</small></span>
        </button>
      </div>

      <div class="video-meta">
        <div>
          <h3 id="video-meta-title">Live Classroom Session · Accra, Ghana</h3>
          <p id="video-meta-desc">Watch our students actively engaged in their technology lesson, working through programming problems together.</p>
        </div>
        <a class="button button-gold" href="adopt.php">Support this classroom <span aria-hidden="true">→</span></a>
      </div>
    </div>

    <!-- Real Photo Gallery with Lightbox Inspection -->
    <div class="gallery-grid">
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-workstations-group.jpeg') ?>" data-lightbox-title="Computer Programming & AI Lab" data-lightbox-desc="Students assembled at dedicated laptop workstations ready for daily software sessions.">
        <img src="<?= app_url('assets/images/classroom-workstations-group.jpeg') ?>" alt="Students in Mill Creek-AR Computer Programming & AI uniform at workstations" loading="lazy">
        <div class="gallery-caption">
          <strong>Computer Programming & AI Lab</strong>
          <span>Students assembled at dedicated laptop workstations ready for daily software sessions.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-coding-close.jpeg') ?>" data-lightbox-title="Hands-On Software Development" data-lightbox-desc="Students write, execute, and debug code directly on individual laptops.">
        <img src="<?= app_url('assets/images/classroom-coding-close.jpeg') ?>" alt="Students coding on laptops in classroom" loading="lazy">
        <div class="gallery-caption">
          <strong>Hands-On Software Development</strong>
          <span>Students write, execute, and debug code directly on individual laptops.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/python-lesson-projection.jpeg') ?>" data-lightbox-title="Python Programming Fundamentals" data-lightbox-desc="Variables, loops, and data types projected for guided classroom instruction.">
        <img src="<?= app_url('assets/images/python-lesson-projection.jpeg') ?>" alt="Projector showing Python programming lesson" loading="lazy">
        <div class="gallery-caption">
          <strong>Python Programming Fundamentals</strong>
          <span>Variables, loops, and data types projected for guided classroom instruction.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-group-portrait.jpeg') ?>" data-lightbox-title="Pride, Dignity & High Standards" data-lightbox-desc="Our instructor and students in their official Mill Creek-AR polo uniforms.">
        <img src="<?= app_url('assets/images/classroom-group-portrait.jpeg') ?>" alt="Students with instructor smiling proudly in uniform" loading="lazy">
        <div class="gallery-caption">
          <strong>Pride, Dignity & High Standards</strong>
          <span>Our instructor and students in their official Mill Creek-AR polo uniforms.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/python-vscode-laptop.jpeg') ?>" data-lightbox-title="Individual Fluency & Practice" data-lightbox-desc="Transforming digital curiosity into practical problem-solving capability.">
        <img src="<?= app_url('assets/images/python-vscode-laptop.jpeg') ?>" alt="Close up of student typing Python in VS Code" loading="lazy">
        <div class="gallery-caption">
          <strong>Individual Fluency & Practice</strong>
          <span>Transforming digital curiosity into practical problem-solving capability.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-standing-lesson.jpeg') ?>" data-lightbox-title="Mentorship & Structure" data-lightbox-desc="Fostering discipline, confidence, and mutual respect among peers.">
        <img src="<?= app_url('assets/images/classroom-standing-lesson.jpeg') ?>" alt="Students participating attentively in lesson" loading="lazy">
        <div class="gallery-caption">
          <strong>Mentorship & Structure</strong>
          <span>Fostering discipline, confidence, and mutual respect among peers.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-evening-lab.jpeg') ?>" data-lightbox-title="Collaborative Problem Solving" data-lightbox-desc="Working through challenges together and celebrating each breakthrough.">
        <img src="<?= app_url('assets/images/classroom-evening-lab.jpeg') ?>" alt="Collaborative evening lab session" loading="lazy">
        <div class="gallery-caption">
          <strong>Collaborative Problem Solving</strong>
          <span>Working through challenges together and celebrating each breakthrough.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/python-loop-code-projection.jpeg') ?>" data-lightbox-title="Interactive Code Review" data-lightbox-desc="Live debugging, list iteration, and practical coding patterns projected in class.">
        <img src="<?= app_url('assets/images/python-loop-code-projection.jpeg') ?>" alt="Classroom projection demonstrating Python iteration and data structures" loading="lazy">
        <div class="gallery-caption">
          <strong>Interactive Code Review</strong>
          <span>Live debugging, list iteration, and practical coding patterns projected in class.</span>
        </div>
      </article>
      <article class="gallery-item" data-lightbox-src="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>" data-lightbox-title="Open Technology Environment" data-lightbox-desc="A high-trust classroom providing the connectivity and tools needed to succeed.">
        <img src="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>" alt="Open learning lab with students collaborating" loading="lazy">
        <div class="gallery-caption">
          <strong>Open Technology Environment</strong>
          <span>A high-trust classroom providing the connectivity and tools needed to succeed.</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- Lightbox Modal -->
<div id="gallery-lightbox" class="lightbox-modal" aria-hidden="true" role="dialog" aria-label="Photo inspection modal">
  <div class="lightbox-overlay" data-lightbox-close></div>
  <div class="lightbox-content">
    <button class="lightbox-close" type="button" aria-label="Close modal" data-lightbox-close>&times;</button>
    <img id="lightbox-img" src="" alt="Enlarged classroom preview">
    <div class="lightbox-caption">
      <h3 id="lightbox-title"></h3>
      <p id="lightbox-desc"></p>
    </div>
  </div>
</div>
</section>

<section class="section"><div class="wrap grid-2">
  <div><span class="eyebrow">One story, two places</span><h2>From humble beginnings in Mill Creek to a classroom in Accra.</h2><div class="route-line"><div class="route-stop"><small>Mill Creek, Arkansas</small><h3>A beginning shaped by limited resources and a strong community.</h3><p>Founder Van Newborn’s path from rural Arkansas to engineering showed what education, persistence, and a few people willing to believe can make possible.</p></div><div class="route-stop"><small>Accra, Ghana</small><h3>A commitment to extend that same bridge.</h3><p>The learning center exists so a young person’s starting point does not have to determine how far they can go.</p></div></div><a class="button button-outline" href="about.php">Read the origin story</a></div>
  <div class="photo-card"><img src="<?= app_url('assets/images/classroom-group-portrait.jpeg') ?>" alt="Instructor and students in Mill Creek-AR learning center uniform" width="1280" height="720" loading="lazy"><div class="photo-caption">“Invest in a journey, not just a classroom.”</div></div>
</div></section>

<section class="section cream"><div class="wrap"><div class="statement"><span class="eyebrow">What support makes possible</span><h2>A journey from first click to future-ready confidence.</h2></div><div class="grid-3" style="margin-top:50px"><article class="impact-card"><span class="number">01</span><h3>Access to tools</h3><p>Reliable time with computers, connectivity, and learning resources—the foundation for meaningful practice.</p></article><article class="impact-card"><span class="number">02</span><h3>Skills that travel</h3><p>Digital literacy, critical thinking, creation, and collaboration that can transfer across industries and borders.</p></article><article class="impact-card"><span class="number">03</span><h3>A wider horizon</h3><p>Mentorship and project experience that help students imagine—and prepare for—education, entrepreneurship, and work.</p></article></div></div></section>

<section class="section"><div class="wrap"><div class="statement"><span class="eyebrow">A bridge across generations</span><h2>Give back. Reach across. Help the next generation rise.</h2><p class="lede" style="margin-inline:auto">For some, supporting Mill Creek-AR is a meaningful way to reconnect with heritage and honor the generations whose strength carried us forward. For others, it reflects a shared belief that talent should never be limited by birthplace or circumstance.</p><p>Every gift reaches across borders to help young people in Accra gain the tools, instruction, and encouragement to show what they can become.</p><a class="button button-gold" href="adopt.php">Give or sponsor a learning journey <span aria-hidden="true">→</span></a></div></div></section>

<section id="students" class="section forest"><div class="wrap"><div class="grid-2"><div><span class="eyebrow light">Learning in motion</span><h2>Show the work. Protect the child.</h2></div><p class="lede" style="color:#d7e3df">Student stories center learning, effort, and growth. We do not publish full names, personal hardship details, or private contact information. Images reflect our students actively engaged in their learning journey.</p></div><div class="learning-grid" style="margin-top:45px"><article class="learning-card"><img src="<?= app_url('assets/images/classroom-coding-wide.jpeg') ?>" alt="A group of students practicing computer programming skills together" loading="lazy"><div class="overlay"><strong>Collaborative learning</strong><br><span>Students learn by trying, asking, helping, and building together.</span></div></article><article class="learning-card"><img src="<?= app_url('assets/images/classroom-coding-close.jpeg') ?>" alt="Learners receiving guidance during a computer lesson" loading="lazy"><div class="overlay"><strong>Guided practice</strong></div></article><article class="learning-card"><img src="<?= app_url('assets/images/classroom-workstations-group.jpeg') ?>" alt="Students in Mill Creek-AR Computer Programming & AI uniform at workstations" loading="lazy"><div class="overlay"><strong>Coding & AI Milestones</strong><br><span>Students practice real scripts, syntax debugging, and fundamental computing concepts.</span></div></article></div><div class="privacy-note"><strong>Safeguarding note:</strong> All student representations center dignity, academic accomplishment, and educational progress.</div></div></section>

<section class="section"><div class="wrap partnership" id="partnership"><span class="eyebrow light">Partnership and accountability</span><h2>Local learning. An alumni legacy of uplift.</h2><p class="lede">Mill Creek-AR Learning Center works in conjunction with the AD² Alumni Foundation. The AD² name carries the history of Alpha Delta Delta at Louisiana Tech University—a chapter shaped by scholarship, technical education, perseverance, mentoring, and service.</p><p>The partnership is designed to connect Van Newborn’s vision with alumni experience, responsible stewardship, and long-term educational support. The foundation page outlines the partnership, its current credentials, and its accountability commitments.</p><div class="button-row"><a class="button button-light" href="foundation.php">Learn about the AD² partnership</a><a class="button button-gold" href="adopt.php">Give or sponsor</a></div></div></section>
<?php include 'includes/footer.php'; ?>
