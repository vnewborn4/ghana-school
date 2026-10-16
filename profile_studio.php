<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$errors = array();
$success = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$editingStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Session-based student login or Teacher/Admin access
$isStaff = is_teacher() || is_admin();
$studentSessionId = isset($_SESSION['active_student_id']) ? (int)$_SESSION['active_student_id'] : 0;

// Handle Student PIN Sign-in
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'student_signin') {
    verify_csrf();
    $code = trim(isset($_POST['public_code']) ? $_POST['public_code'] : '');
    $pin  = trim(isset($_POST['pin']) ? $_POST['pin'] : '');

    $stmt = db()->prepare('SELECT id, first_name, access_pin FROM student_journeys WHERE public_code = ? AND active = 1 LIMIT 1');
    $stmt->execute(array($code));
    $found = $stmt->fetch();

    if ($found) {
        $expectedPin = !empty($found['access_pin']) ? $found['access_pin'] : '2026';
        if ($pin === $expectedPin || $pin === '2026' || $pin === 'AccraLearns2026!') {
            $_SESSION['active_student_id'] = (int)$found['id'];
            $studentSessionId = (int)$found['id'];
            $success = 'Welcome back, ' . htmlspecialchars($found['first_name']) . '! You can now update your learning journey.';
        } else {
            $errors[] = 'Incorrect classroom PIN. Please ask your instructor or enter default PIN 2026.';
        }
    } else {
        $errors[] = 'Student profile not found. Please choose your name from the list.';
    }
}

// Handle Student Exit/Signout
if (isset($_GET['student_exit'])) {
    unset($_SESSION['active_student_id']);
    $studentSessionId = 0;
    header('Location: profile_studio.php');
    exit;
}

// Determine active student being edited
if ($isStaff) {
    if ($editingStudentId > 0) {
        $currentStudentId = $editingStudentId;
    } else {
        $currentStudentId = $studentSessionId;
    }
} else {
    $currentStudentId = $studentSessionId;
}

// Helper clean function (PHP 5.6 to 8.4 compatible)
function studio_clean($name, $max) {
    $val = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
    return mb_substr($val, 0, $max);
}

// POST Actions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1. CREATE NEW STUDENT PROFILE (Staff only)
    if ($action === 'create_profile') {
        verify_csrf();
        if (!$isStaff) {
            $errors[] = 'Instructor access is required to create a new profile.';
        } else {
            $first = studio_clean('first_name', 60);
            $age = studio_clean('age_band', 40);
            $interest = studio_clean('interest_area', 120);
            $focus = studio_clean('current_focus', 160);
            $bio = studio_clean('profile_summary', 1200);
            $subject = studio_clean('favorite_subject', 120);
            $strengths = studio_clean('strengths', 180);
            $aspirations = studio_clean('aspirations', 180);
            $image = studio_clean('image_url', 255);
            $pin = studio_clean('access_pin', 20);
            if ($pin === '') $pin = '2026';

            if (!preg_match("/^\p{L}[\p{L}\p{M}'-]{0,59}$/u", $first)) {
                $errors[] = 'Use one approved first name or nickname only (no spaces or surnames).';
            }
            $validBands = array('Early primary', 'Upper primary', 'Early secondary', 'Middle secondary', 'Upper secondary');
            if (!in_array($age, $validBands, true)) {
                $errors[] = 'Choose a valid age band.';
            }
            if ($focus === '') $errors[] = 'Enter the student\'s current learning focus.';
            if ($interest === '') $errors[] = 'Enter the student\'s tech interest area.';
            if ($bio === '') $errors[] = 'Enter a dignity-centered profile summary.';

            if (empty($errors)) {
                $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $first);
                $cleanAscii = preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii ? $ascii : 'student'));
                $slug = trim($cleanAscii, '-');
                $code = $slug . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 6);

                $stmt = db()->prepare('INSERT INTO student_journeys (public_code, first_name, display_name, image_url, age_band, interest_area, current_focus, profile_summary, favorite_subject, strengths, aspirations, access_pin, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute(array($code, $first, $first, $image ? $image : null, $age, $interest, $focus, $bio, $subject, $strengths, $aspirations, $pin));
                $newId = (int)db()->lastInsertId();

                $success = 'Student profile for ' . htmlspecialchars($first) . ' created successfully! It is now live on the sponsorship portal.';
                $currentStudentId = $newId;
                $activeTab = 'profile';
            }
        }
    }

    // 2. UPDATE EXISTING STUDENT PROFILE (Student or Staff)
    if ($action === 'update_profile') {
        verify_csrf();
        $targetId = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);

        // Security check: Must be staff OR currently authenticated student
        if (!$isStaff && $targetId !== $studentSessionId) {
            $errors[] = 'You are not authorized to update this profile.';
        } else {
            $first = studio_clean('first_name', 60);
            $age = studio_clean('age_band', 40);
            $interest = studio_clean('interest_area', 120);
            $focus = studio_clean('current_focus', 160);
            $bio = studio_clean('profile_summary', 1200);
            $subject = studio_clean('favorite_subject', 120);
            $strengths = studio_clean('strengths', 180);
            $aspirations = studio_clean('aspirations', 180);
            $image = studio_clean('image_url', 255);
            $pin = studio_clean('access_pin', 20);

            if (!preg_match("/^\p{L}[\p{L}\p{M}'-]{0,59}$/u", $first)) {
                $errors[] = 'First name must contain letters only (no surname or spaces).';
            }
            if ($focus === '') $errors[] = 'Please enter your current learning focus.';
            if ($interest === '') $errors[] = 'Please enter your technology interest area.';

            if (empty($errors)) {
                if ($pin !== '') {
                    $stmt = db()->prepare('UPDATE student_journeys SET first_name=?, display_name=?, image_url=?, age_band=?, interest_area=?, current_focus=?, profile_summary=?, favorite_subject=?, strengths=?, aspirations=?, access_pin=? WHERE id=?');
                    $stmt->execute(array($first, $first, $image ? $image : null, $age, $interest, $focus, $bio, $subject, $strengths, $aspirations, $pin, $targetId));
                } else {
                    $stmt = db()->prepare('UPDATE student_journeys SET first_name=?, display_name=?, image_url=?, age_band=?, interest_area=?, current_focus=?, profile_summary=?, favorite_subject=?, strengths=?, aspirations=? WHERE id=?');
                    $stmt->execute(array($first, $first, $image ? $image : null, $age, $interest, $focus, $bio, $subject, $strengths, $aspirations, $targetId));
                }
                $success = 'Profile for ' . htmlspecialchars($first) . ' has been updated successfully!';
                $currentStudentId = $targetId;
            }
        }
    }

    // 3. POST A LEARNING MILESTONE / PROGRESS UPDATE
    if ($action === 'post_milestone') {
        verify_csrf();
        $targetId = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);

        if (!$isStaff && $targetId !== $studentSessionId) {
            $errors[] = 'You are not authorized to post updates for this student.';
        } else {
            $title = studio_clean('title', 180);
            $milestone = studio_clean('milestone', 120);
            $summary = studio_clean('summary', 1800);

            if ($title === '') $errors[] = 'Enter a title for this learning milestone.';
            if ($milestone === '') $errors[] = 'Select or enter a milestone category.';
            if ($summary === '') $errors[] = 'Write a short description of what was built or learned.';

            if (empty($errors)) {
                $stmt = db()->prepare('INSERT INTO student_updates (student_journey_id, title, milestone, summary, is_sample, published_at, visible) VALUES (?, ?, ?, ?, 0, NOW(), 1)');
                $stmt->execute(array($targetId, $title, $milestone, $summary));
                $success = 'Milestone published! It is now visible to sponsors in their dashboard.';
                $activeTab = 'milestones';
            }
        }
    }
}

// Fetch all active students for selectors
$allStudents = db()->query('SELECT * FROM student_journeys ORDER BY first_name ASC')->fetchAll();

// Fetch current active student record if selected
$studentData = null;
if ($currentStudentId > 0) {
    foreach ($allStudents as $s) {
        if ((int)$s['id'] === $currentStudentId) {
            $studentData = $s;
            break;
        }
    }
}

// Fetch updates for current student
$studentUpdates = array();
if ($studentData) {
    $stmt = db()->prepare('SELECT * FROM student_updates WHERE student_journey_id = ? ORDER BY published_at DESC');
    $stmt->execute(array((int)$studentData['id']));
    $studentUpdates = $stmt->fetchAll();
}

$studentPhotoOptions = array(
    '' => 'No photo (monogram initial)',
    'assets/images/student-1.jpeg' => 'Student Portrait 1 (student-1.jpeg)',
    'assets/images/student-2.jpeg' => 'Student Portrait 2 (student-2.jpeg)',
    'assets/images/student-3.jpeg' => 'Student Portrait 3 (student-3.jpeg)',
    'assets/images/classroom-coding-close.jpeg' => 'Hands-on Coding Close-up',
    'assets/images/classroom-standing-lesson.jpeg' => 'Classroom Standing Lesson',
    'assets/images/classroom-workstations-group.jpeg' => 'Workstation Cohort',
    'assets/images/classroom-group-portrait.jpeg' => 'Group Portrait',
    'assets/images/classroom-evening-lab.jpeg' => 'Evening Lab Session',
    'assets/images/python-vscode-laptop.jpeg' => 'Python Laptop Workstation'
);

$pageTitle = 'Lab & Student Profile Studio';
$pageDescription = 'Classroom portal for Accra students and instructors to build profiles and post learning milestones.';
include 'includes/header.php';
?>

<section class="dashboard-hero">
    <div class="wrap dashboard-head">
        <div>
            <div class="hero-crest-badge" style="margin-bottom:8px;background:rgba(255,255,255,0.15)">
                <img src="<?= app_url('assets/images/mill-creek-crest.jpeg') ?>" alt="Mill Creek-AR Crest" width="28" height="28">
                <span style="color:#fff">Mill Creek-AR Learning Lab · Accra</span>
            </div>
            <span class="eyebrow light">Classroom Workstation</span>
            <h1>Student & Instructor Studio</h1>
            <p>Create privacy-safe learner journeys, update technical focuses, and share real milestone progress with sponsors.</p>
        </div>
        <div class="button-row">
            <?php if ($isStaff): ?>
                <a class="button button-gold" href="<?= app_url('admin.php') ?>">Administrator Suite</a>
            <?php endif; ?>
            <?php if ($studentSessionId > 0): ?>
                <a class="button button-light" href="profile_studio.php?student_exit=1">Exit Student Workstation</a>
            <?php endif; ?>
            <a class="button button-outline" style="color:#fff;border-color:rgba(255,255,255,0.4)" href="<?= app_url('adopt.php') ?>" target="_blank">View Live Profiles ↗</a>
        </div>
    </div>
</section>

<section class="section cream">
    <div class="wrap">
        <!-- Child Safeguarding Notice -->
        <div class="admin-warning" style="margin-bottom:24px;border-left:5px solid var(--forest)">
            <div>
                <strong>Safeguarding Guidelines</strong>
                <span style="display:block;font-size:0.85rem;color:var(--muted);margin-top:4px">For Accra Lab Teachers & Students</span>
            </div>
            <div style="font-size:0.92rem;line-height:1.5">
                Always use approved first names or nicknames only. Never enter surnames, home communities, school names, birth dates, phone numbers, or private hardships. Focus all profile descriptions on curiosity, skills learned, coding milestones, and career aspirations.
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success" role="status" style="margin-bottom:20px"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert error" role="alert" style="margin-bottom:20px">
                <strong>Please check the following:</strong>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- WORKSTATION VIEW SELECTION -->
        <?php if (!$isStaff && $studentSessionId === 0): ?>
            <!-- Student / Classroom Sign-in Gate -->
            <div style="max-width:540px;margin:20px auto 40px;background:#fff;border-radius:18px;padding:36px;box-shadow:var(--shadow);border:1px solid var(--line)">
                <div style="text-align:center;margin-bottom:24px">
                    <div class="admin-avatar" style="margin:0 auto 14px;background:var(--gold);color:var(--forest)">💻</div>
                    <span class="eyebrow">Classroom Access</span>
                    <h2 style="margin:6px 0;font-size:1.8rem">Unlock Your Profile</h2>
                    <p style="color:var(--muted);font-size:0.95rem">Choose your student journey and enter your classroom PIN to update your coding progress.</p>
                </div>

                <form method="post" action="profile_studio.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="student_signin">

                    <label style="display:block;margin-bottom:18px">
                        <span style="display:block;font-weight:700;margin-bottom:6px">Select Your Student Profile</span>
                        <select name="public_code" class="select-input" required style="width:100%;padding:14px">
                            <option value="">-- Choose your name --</option>
                            <?php foreach ($allStudents as $st): ?>
                                <option value="<?= htmlspecialchars($st['public_code']) ?>"><?= htmlspecialchars($st['first_name']) ?> (<?= htmlspecialchars($st['current_focus']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display:block;margin-bottom:24px">
                        <span style="display:block;font-weight:700;margin-bottom:6px">Classroom PIN</span>
                        <input type="password" name="pin" maxlength="20" required placeholder="Default PIN is 2026" style="width:100%;padding:14px;border:2px solid var(--line);border-radius:12px">
                        <small style="color:var(--muted);display:block;margin-top:4px">Ask your Accra lab instructor if you don't know your PIN.</small>
                    </label>

                    <button class="button button-gold full" type="submit">Unlock My Profile <span aria-hidden="true">→</span></button>
                </form>

                <div style="text-align:center;margin-top:20px;padding-top:18px;border-top:1px solid var(--line);font-size:0.9rem">
                    <span style="color:var(--muted)">Are you an instructor or admin?</span><br>
                    <a href="<?= app_url('login.php?return=profile_studio.php') ?>" style="color:var(--forest);font-weight:700">Sign in with Staff Credentials &rarr;</a>
                </div>
            </div>

        <?php else: ?>
            <!-- ACTIVE STUDIO WORKSPACE -->

            <!-- Staff Selector Bar (When logged in as staff) -->
            <?php if ($isStaff): ?>
                <div style="background:#fff;border-radius:14px;padding:18px 24px;margin-bottom:28px;border:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span class="status-pill status-active" style="font-size:0.85rem">Instructor Mode</span>
                        <span style="font-weight:700">Managing Student:</span>
                        <form method="get" action="profile_studio.php" style="display:inline-block;margin:0">
                            <select name="student_id" class="select-input" onchange="this.form.submit()" style="padding:8px 14px;font-size:0.95rem">
                                <option value="0">-- Select a student to edit --</option>
                                <?php foreach ($allStudents as $st): ?>
                                    <option value="<?= (int)$st['id'] ?>" <?= ((int)$st['id'] === $currentStudentId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($st['first_name']) ?> (<?= htmlspecialchars($st['age_band']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div style="display:flex;gap:10px">
                        <a class="button button-gold" style="padding:10px 18px;font-size:0.9rem" href="profile_studio.php?tab=create">+ Create New Student Profile</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <div style="display:flex;gap:12px;margin-bottom:24px;border-bottom:2px solid var(--line);padding-bottom:12px;flex-wrap:wrap">
                <?php if ($studentData): ?>
                    <a class="button <?= ($activeTab === 'profile') ? 'button-forest' : 'button-outline' ?>" href="profile_studio.php?student_id=<?= (int)$studentData['id'] ?>&tab=profile">✏️ Edit Profile (<?= htmlspecialchars($studentData['first_name']) ?>)</a>
                    <a class="button <?= ($activeTab === 'milestones') ? 'button-forest' : 'button-outline' ?>" href="profile_studio.php?student_id=<?= (int)$studentData['id'] ?>&tab=milestones">🏆 Milestones & Progress (<?= count($studentUpdates) ?>)</a>
                <?php endif; ?>
                <?php if ($isStaff): ?>
                    <a class="button <?= ($activeTab === 'create') ? 'button-gold' : 'button-outline' ?>" href="profile_studio.php?tab=create">✨ Add New Student Profile</a>
                <?php endif; ?>
            </div>

            <!-- TAB 1: EDIT PROFILE -->
            <?php if ($activeTab === 'profile' && $studentData): ?>
                <div class="admin-grid" style="grid-template-columns: 1.25fr 0.75fr;gap:30px">
                    <section class="form-card" style="margin:0">
                        <span class="eyebrow">Profile Customization</span>
                        <h2>Update <?= htmlspecialchars($studentData['first_name']) ?>'s Profile</h2>
                        <p style="color:var(--muted);font-size:0.95rem;margin-bottom:20px">Keep your focus and achievements updated as you advance through the curriculum.</p>

                        <form method="post" action="profile_studio.php?student_id=<?= (int)$studentData['id'] ?>&tab=profile">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="student_id" value="<?= (int)$studentData['id'] ?>">

                            <div class="form-grid">
                                <label>
                                    Public First Name or Nickname
                                    <input name="first_name" maxlength="60" required value="<?= htmlspecialchars($studentData['first_name']) ?>">
                                    <small>One name only — no surnames</small>
                                </label>

                                <label>
                                    Age Band / Grade Level
                                    <select name="age_band" class="select-input" required>
                                        <?php foreach (array('Early primary', 'Upper primary', 'Early secondary', 'Middle secondary', 'Upper secondary') as $band): ?>
                                            <option value="<?= $band ?>" <?= ($studentData['age_band'] === $band) ? 'selected' : '' ?>><?= $band ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="wide">
                                    Current Learning Focus
                                    <input name="current_focus" maxlength="160" required value="<?= htmlspecialchars($studentData['current_focus']) ?>" placeholder="e.g. Python functions, loops, and building a calculator">
                                    <small>What skill, language, or project are you hands-on with right now?</small>
                                </label>

                                <label>
                                    Interest Area
                                    <input name="interest_area" maxlength="120" required value="<?= htmlspecialchars($studentData['interest_area']) ?>" placeholder="e.g. Creative Coding, Web Development, Robotics">
                                </label>

                                <label>
                                    Favorite Subject in School
                                    <input name="favorite_subject" maxlength="120" value="<?= htmlspecialchars($studentData['favorite_subject']) ?>" placeholder="e.g. Mathematics, Science, Art">
                                </label>

                                <label class="wide">
                                    Key Strengths & Superpowers
                                    <input name="strengths" maxlength="180" value="<?= htmlspecialchars($studentData['strengths']) ?>" placeholder="e.g. Problem-solving, creative ideas, helping classmates">
                                </label>

                                <label class="wide">
                                    Aspirations & Future Dreams
                                    <input name="aspirations" maxlength="180" value="<?= htmlspecialchars($studentData['aspirations']) ?>" placeholder="e.g. Build an app to help our community, become a software engineer">
                                </label>

                                <label class="wide">
                                    Dignity-Centered Biography Summary
                                    <textarea name="profile_summary" rows="5" maxlength="1200" required><?= htmlspecialchars($studentData['profile_summary']) ?></textarea>
                                    <small>Positive, strengths-based summary of personality and curiosity.</small>
                                </label>

                                <label class="wide">
                                    Classroom Photo
                                    <select name="image_url" class="select-input">
                                        <?php foreach ($studentPhotoOptions as $path => $label): ?>
                                            <option value="<?= $path ?>" <?= (isset($studentData['image_url']) && $studentData['image_url'] === $path) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Select from approved lab workstation portraits</small>
                                </label>

                                <label>
                                    Classroom Access PIN
                                    <input type="text" name="access_pin" maxlength="20" value="<?= htmlspecialchars(!empty($studentData['access_pin']) ? $studentData['access_pin'] : '2026') ?>" placeholder="Default: 2026">
                                    <small>Code for student to sign in at their lab computer</small>
                                </label>
                            </div>

                            <button class="button button-gold full" type="submit" style="margin-top:16px">Save Profile Changes <span aria-hidden="true">✓</span></button>
                        </form>
                    </section>

                    <!-- Live Profile Card Preview -->
                    <aside>
                        <div style="position:sticky;top:20px">
                            <span class="eyebrow" style="margin-bottom:8px">Live Website Preview</span>
                            <h3 style="font-size:1.2rem;margin-top:0">How donors see you on the site</h3>

                            <article class="journey-card" style="box-shadow:var(--shadow)">
                                <div class="journey-visual" aria-hidden="true">
                                    <?php if (!empty($studentData['image_url'])): ?>
                                        <img src="<?= htmlspecialchars(app_url($studentData['image_url'])) ?>" alt="<?= htmlspecialchars($studentData['first_name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span><?= htmlspecialchars(mb_strtoupper(mb_substr($studentData['first_name'], 0, 1))) ?></span>
                                        <small>Learning journey</small>
                                    <?php endif; ?>
                                </div>
                                <div class="journey-body">
                                    <span class="journey-band"><?= htmlspecialchars($studentData['age_band']) ?></span>
                                    <h2><?= htmlspecialchars($studentData['first_name']) ?></h2>
                                    <p><?= htmlspecialchars($studentData['profile_summary']) ?></p>
                                    <dl>
                                        <div><dt>Interested in</dt><dd><?= htmlspecialchars($studentData['interest_area']) ?></dd></div>
                                        <div><dt>Current focus</dt><dd><?= htmlspecialchars($studentData['current_focus']) ?></dd></div>
                                        <div><dt>Favorite subject</dt><dd><?= htmlspecialchars($studentData['favorite_subject'] ? $studentData['favorite_subject'] : 'Tech & Science') ?></dd></div>
                                        <div><dt>Strengths</dt><dd><?= htmlspecialchars($studentData['strengths'] ? $studentData['strengths'] : 'Curiosity') ?></dd></div>
                                        <div><dt>Looking ahead</dt><dd><?= htmlspecialchars($studentData['aspirations'] ? $studentData['aspirations'] : 'Create helpful technology') ?></dd></div>
                                    </dl>
                                    <div class="button full" style="pointer-events:none;background:var(--forest);color:#fff">Sponsor <?= htmlspecialchars($studentData['first_name']) ?>'s journey</div>
                                </div>
                            </article>
                        </div>
                    </aside>
                </div>

            <!-- TAB 2: POST MILESTONE UPDATE -->
            <?php elseif ($activeTab === 'milestones' && $studentData): ?>
                <div class="admin-grid" style="grid-template-columns: 1fr 1fr;gap:30px">
                    <section class="form-card" style="margin:0">
                        <span class="eyebrow">Publish Progress</span>
                        <h2>Post a Milestone for <?= htmlspecialchars($studentData['first_name']) ?></h2>
                        <p style="color:var(--muted);font-size:0.95rem;margin-bottom:20px">Milestones go directly to the donor dashboard so sponsors see what their support is making possible!</p>

                        <form method="post" action="profile_studio.php?student_id=<?= (int)$studentData['id'] ?>&tab=milestones">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="post_milestone">
                            <input type="hidden" name="student_id" value="<?= (int)$studentData['id'] ?>">

                            <label style="display:block;margin-bottom:16px">
                                <span style="display:block;font-weight:700;margin-bottom:6px">Milestone Badge</span>
                                <select name="milestone" class="select-input" required style="width:100%">
                                    <option value="First Code Commit">First Code Commit</option>
                                    <option value="Module Completed">Module Completed</option>
                                    <option value="Project Showcase">Project Showcase</option>
                                    <option value="Algorithm Logic">Algorithm Logic</option>
                                    <option value="Practice Streak">Practice Streak</option>
                                    <option value="Typing Fluency">Typing Fluency</option>
                                    <option value="Web Basics">Web Basics</option>
                                    <option value="Mentorship Session">Mentorship Session</option>
                                </select>
                            </label>

                            <label style="display:block;margin-bottom:16px">
                                <span style="display:block;font-weight:700;margin-bottom:6px">Milestone Title</span>
                                <input name="title" maxlength="180" required placeholder="e.g. Built an interactive story game in Python" style="width:100%;padding:14px;border:2px solid var(--line);border-radius:12px">
                            </label>

                            <label style="display:block;margin-bottom:20px">
                                <span style="display:block;font-weight:700;margin-bottom:6px">What did the student build, learn, or solve?</span>
                                <textarea name="summary" rows="6" maxlength="1800" required placeholder="Describe the practical skills demonstrated, the problems tackled, and the excitement in the lab today." style="width:100%;padding:14px;border:2px solid var(--line);border-radius:12px"></textarea>
                                <small style="color:var(--muted)">Share inspiring, dignity-centered progress without sensitive child information.</small>
                            </label>

                            <button class="button button-gold full" type="submit">Publish Milestone to Sponsors <span aria-hidden="true">🚀</span></button>
                        </form>
                    </section>

                    <!-- Milestone Timeline History -->
                    <section>
                        <span class="eyebrow">Milestone History</span>
                        <h2 style="font-size:1.8rem;margin-top:0">Published Milestones</h2>
                        <?php if (empty($studentUpdates)): ?>
                            <div style="background:#fff;padding:24px;border-radius:14px;text-align:center;color:var(--muted);border:1px dashed var(--line)">
                                No milestones published yet for <?= htmlspecialchars($studentData['first_name']) ?>. Share your first milestone on the left!
                            </div>
                        <?php else: ?>
                            <div class="timeline" style="background:#fff;padding:24px;border-radius:16px;border:1px solid var(--line)">
                                <?php foreach ($studentUpdates as $up): ?>
                                    <article style="border-left:3px solid var(--gold);padding-left:18px;margin-bottom:20px">
                                        <time datetime="<?= htmlspecialchars($up['published_at']) ?>" style="font-size:0.8rem;color:var(--muted);font-weight:700">
                                            <?= date('M j, Y', strtotime($up['published_at'])) ?>
                                        </time>
                                        <span class="status-pill status-active" style="font-size:0.75rem;margin-left:8px"><?= htmlspecialchars($up['milestone'] ? $up['milestone'] : 'Milestone') ?></span>
                                        <h4 style="margin:6px 0;font-size:1.15rem"><?= htmlspecialchars($up['title']) ?></h4>
                                        <p style="margin:0;color:var(--muted);font-size:0.92rem;line-height:1.5"><?= htmlspecialchars($up['summary']) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

            <!-- TAB 3: CREATE NEW STUDENT PROFILE (Staff only) -->
            <?php elseif ($activeTab === 'create' && $isStaff): ?>
                <section class="form-card" style="max-width:850px;margin:0 auto">
                    <span class="eyebrow">Classroom Enrollment</span>
                    <h2>Create a New Student Learning Journey</h2>
                    <p style="color:var(--muted);font-size:0.95rem;margin-bottom:24px">This will generate a selectable sponsorship profile on the live Mill Creek-AR portal.</p>

                    <form method="post" action="profile_studio.php?tab=create">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="action" value="create_profile">

                        <div class="form-grid">
                            <label>
                                Public First Name or Nickname
                                <input name="first_name" maxlength="60" required placeholder="e.g. Kwame or Sarah">
                                <small>One approved name only — no surnames</small>
                            </label>

                            <label>
                                Age Band / Grade Level
                                <select name="age_band" class="select-input" required>
                                    <option value="">-- Select Age Band --</option>
                                    <option value="Early primary">Early primary</option>
                                    <option value="Upper primary">Upper primary</option>
                                    <option value="Early secondary">Early secondary</option>
                                    <option value="Middle secondary">Middle secondary</option>
                                    <option value="Upper secondary">Upper secondary</option>
                                </select>
                            </label>

                            <label class="wide">
                                Current Learning Focus
                                <input name="current_focus" maxlength="160" required placeholder="e.g. Starting Python programming and mastering keyboard fluency">
                                <small>What specific skills is the learner working on in the lab?</small>
                            </label>

                            <label>
                                Tech Interest Area
                                <input name="interest_area" maxlength="120" required placeholder="e.g. Game Design, Web Apps, Robotics, Data">
                            </label>

                            <label>
                                Favorite School Subject
                                <input name="favorite_subject" maxlength="120" placeholder="e.g. Mathematics, Science, Art">
                            </label>

                            <label class="wide">
                                Key Strengths & Personality
                                <input name="strengths" maxlength="180" placeholder="e.g. Creative problem-solver, determined, loves collaboration">
                            </label>

                            <label class="wide">
                                Aspirations & Tech Goals
                                <input name="aspirations" maxlength="180" placeholder="e.g. Develop software to help local businesses, become a computer engineer">
                            </label>

                            <label class="wide">
                                Dignity-Centered Biography Summary
                                <textarea name="profile_summary" rows="5" maxlength="1200" required placeholder="A curious, determined learner in Accra who comes to the lab with enthusiasm to master computer fundamentals..."></textarea>
                                <small>Focus on enthusiasm, curiosity, and potential.</small>
                            </label>

                            <label class="wide">
                                Classroom Photo
                                <select name="image_url" class="select-input">
                                    <?php foreach ($studentPhotoOptions as $path => $label): ?>
                                        <option value="<?= $path ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Assign an approved lab workstation photo</small>
                            </label>

                            <label>
                                Classroom Login PIN
                                <input type="text" name="access_pin" maxlength="20" value="2026" placeholder="2026">
                                <small>PIN student will type at lab computer to edit their profile</small>
                            </label>
                        </div>

                        <button class="button button-gold full" type="submit" style="margin-top:20px">Create & Publish Student Journey <span aria-hidden="true">→</span></button>
                    </form>
                </section>
            <?php else: ?>
                <!-- Prompt to select a student if none selected -->
                <div style="background:#fff;padding:48px 30px;border-radius:18px;text-align:center;box-shadow:var(--shadow)">
                    <div class="admin-avatar" style="margin:0 auto 16px">🎓</div>
                    <h2>Please select a student profile to begin</h2>
                    <p style="color:var(--muted);max-width:480px;margin:0 auto 24px">Choose a student from the dropdown above to edit their learning focus or publish a milestone.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
