<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$adminId = require_admin();

$errors = array();
$success = '';
$editingStudent = null;

function clean_field($name, $max) {
    $value = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
    return mb_substr($value, 0, $max);
}

// Check if admin clicked "Edit" on a specific student
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM student_journeys WHERE id = ?');
    $stmt->execute(array($editId));
    $editingStudent = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1. ADD NEW STUDENT
    if ($action === 'add_student') {
        $first = clean_field('first_name', 60);
        $age = clean_field('age_band', 40);
        $interest = clean_field('interest_area', 120);
        $focus = clean_field('current_focus', 160);
        $bio = clean_field('profile_summary', 1200);
        $subject = clean_field('favorite_subject', 120);
        $strengths = clean_field('strengths', 180);
        $aspirations = clean_field('aspirations', 180);
        $pin = clean_field('access_pin', 20);
        if ($pin === '') $pin = '2026';

        if (!preg_match("/^\p{L}[\p{L}\p{M}'-]{0,59}$/u", $first)) {
            $errors[] = 'Use one approved first name or nickname only—no spaces or surname.';
        }
        $validBands = array('Early primary', 'Upper primary', 'Early secondary', 'Middle secondary', 'Upper secondary');
        if (!in_array($age, $validBands, true)) {
            $errors[] = 'Choose a valid age band.';
        }
        if ($focus === '') $errors[] = 'Add the current learning focus.';
        if ($interest === '') $errors[] = 'Add the interest area.';
        if ($bio === '') $errors[] = 'Add the biography summary.';

        if (empty($errors)) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $first);
            $cleanAscii = preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii ? $ascii : 'student'));
            $slug = trim($cleanAscii, '-');
            $code = $slug . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 6);

            $stmt = db()->prepare('INSERT INTO student_journeys(public_code,first_name,display_name,age_band,interest_area,current_focus,profile_summary,favorite_subject,strengths,aspirations,access_pin,active) VALUES(?,?,?,?,?,?,?,?,?,?,?,1)');
            $stmt->execute(array($code, $first, $first, $age, $interest, $focus, $bio, $subject, $strengths, $aspirations, $pin));
            $success = $first . ' has been added and is now available for sponsorship.';
        }

    // 2. EDIT EXISTING STUDENT
    } elseif ($action === 'edit_student') {
        $studentId = (int)(isset($_POST['student_id']) ? $_POST['student_id'] : 0);
        $first = clean_field('first_name', 60);
        $age = clean_field('age_band', 40);
        $interest = clean_field('interest_area', 120);
        $focus = clean_field('current_focus', 160);
        $bio = clean_field('profile_summary', 1200);
        $subject = clean_field('favorite_subject', 120);
        $strengths = clean_field('strengths', 180);
        $aspirations = clean_field('aspirations', 180);
        $pin = clean_field('access_pin', 20);
        if ($pin === '') $pin = '2026';

        if (!preg_match("/^\p{L}[\p{L}\p{M}'-]{0,59}$/u", $first)) {
            $errors[] = 'Use one approved first name only (letters and hyphens).';
        }
        if ($focus === '') $errors[] = 'Add the current learning focus.';
        if ($interest === '') $errors[] = 'Add the interest area.';

        if (empty($errors)) {
            $stmt = db()->prepare('UPDATE student_journeys SET first_name=?, display_name=?, age_band=?, interest_area=?, current_focus=?, profile_summary=?, favorite_subject=?, strengths=?, aspirations=?, access_pin=? WHERE id=?');
            $stmt->execute(array($first, $first, $age, $interest, $focus, $bio, $subject, $strengths, $aspirations, $pin, $studentId));
            $success = 'Profile for ' . $first . ' has been updated successfully!';
            $editingStudent = null;
        }

    // 3. PUBLISH LEARNING UPDATE
    } elseif ($action === 'add_update') {
        $journeyId = (int)(isset($_POST['student_journey_id']) ? $_POST['student_journey_id'] : 0);
        $title = clean_field('title', 180);
        $summary = clean_field('summary', 1800);
        $milestone = clean_field('milestone', 120);

        $check = db()->prepare('SELECT first_name FROM student_journeys WHERE id=?');
        $check->execute(array($journeyId));
        $studentName = $check->fetchColumn();

        if (!$studentName) $errors[] = 'Choose a valid student.';
        if ($title === '' || $summary === '' || $milestone === '') $errors[] = 'Complete the update title, milestone, and summary.';

        if (empty($errors)) {
            $stmt = db()->prepare('INSERT INTO student_updates(student_journey_id,title,summary,milestone,is_sample,published_at,visible) VALUES(?,?,?,?,0,NOW(),1)');
            $stmt->execute(array($journeyId, $title, $summary, $milestone));
            $success = 'The reviewed update for ' . $studentName . ' is now visible to sponsors.';
        }

    // 4. TOGGLE ACTIVE STATUS
    } elseif ($action === 'toggle_student') {
        $journeyId = (int)(isset($_POST['student_journey_id']) ? $_POST['student_journey_id'] : 0);
        $active = (int)(isset($_POST['active']) ? $_POST['active'] : 0) === 1 ? 1 : 0;
        $stmt = db()->prepare('UPDATE student_journeys SET active=? WHERE id=?');
        $stmt->execute(array($active, $journeyId));
        $success = $active ? 'The student is now visible for sponsorship selection.' : 'The student has been hidden from new selection.';

    // 5. UPDATE SPONSORSHIP STATUS
    } elseif ($action === 'update_sponsorship') {
        $sponsorshipId = (int)(isset($_POST['sponsorship_id']) ? $_POST['sponsorship_id'] : 0);
        $status = isset($_POST['status']) ? (string)$_POST['status'] : '';
        $reference = clean_field('provider_reference', 255);
        $allowed = array('pending', 'active', 'paused', 'completed', 'cancelled', 'failed');

        if ($sponsorshipId < 1 || !in_array($status, $allowed, true)) {
            $errors[] = 'Choose a valid sponsorship status.';
        }
        if (in_array($status, array('active', 'completed'), true) && $reference === '') {
            $errors[] = 'Add the verified Zeffy or Stripe transaction reference before confirming.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare('UPDATE sponsorships SET status=?, provider_reference=NULLIF(?,\'\') WHERE id=?');
            $stmt->execute(array($status, $reference, $sponsorshipId));
            $success = 'The verified donation status has been updated.';
        }
    }
}

// Fetch lists
$students = db()->query('SELECT j.*, (SELECT COUNT(*) FROM sponsorships s WHERE s.student_journey_id=j.id) as sponsor_count, (SELECT COUNT(*) FROM student_updates u WHERE u.student_journey_id=j.id AND u.visible=1) as update_count FROM student_journeys j ORDER BY j.active DESC, j.first_name ASC')->fetchAll();

$sponsorships = db()->query('SELECT s.*, u.first_name as donor_first, u.last_name as donor_last, u.email, j.first_name as student_first FROM sponsorships s JOIN users u ON u.id=s.user_id JOIN student_journeys j ON j.id=s.student_journey_id ORDER BY s.created_at DESC LIMIT 100')->fetchAll();

// System stats
$totalStudents = count($students);
$activeSponsors = (int)db()->query("SELECT COUNT(*) FROM sponsorships WHERE status='active'")->fetchColumn();
$totalUpdates = (int)db()->query("SELECT COUNT(*) FROM student_updates")->fetchColumn();
$totalUsers = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();

$pageTitle = 'Administrator Suite';
$pageDescription = 'Central administration for Mill Creek-AR Learning Center student profiles, instructors, and sponsorships.';
include 'includes/header.php';
?>

<section class="dashboard-hero">
    <div class="wrap dashboard-head">
        <div>
            <div class="hero-crest-badge" style="margin-bottom:8px;background:rgba(255,255,255,0.15)">
                <img src="<?= app_url('assets/images/mill-creek-crest.jpeg') ?>" alt="Mill Creek-AR Crest" width="28" height="28">
                <span style="color:#fff">Mill Creek-AR Administration</span>
            </div>
            <span class="eyebrow light">Master Control</span>
            <h1>Central Administration</h1>
            <p>Manage Accra student profiles, review classroom learning milestones, and reconcile donor sponsorships.</p>
        </div>
        <div class="button-row">
            <a class="button button-gold" href="<?= app_url('profile_studio.php') ?>">Open Lab & Student Studio ↗</a>
            <a class="button button-light" href="<?= app_url('portal.php') ?>">Donor Dashboard</a>
        </div>
    </div>
</section>

<section class="section cream">
    <div class="wrap">

        <!-- Stat Tiles -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px">
            <div style="background:#fff;padding:22px 26px;border-radius:16px;box-shadow:var(--shadow);border-left:4px solid var(--forest)">
                <span class="eyebrow" style="margin-bottom:4px">Students Enrolled</span>
                <strong style="font-size:2.2rem;display:block;color:var(--forest)"><?= $totalStudents ?></strong>
                <span style="color:var(--muted);font-size:0.85rem">Active learning journeys</span>
            </div>
            <div style="background:#fff;padding:22px 26px;border-radius:16px;box-shadow:var(--shadow);border-left:4px solid var(--gold)">
                <span class="eyebrow" style="margin-bottom:4px">Active Sponsors</span>
                <strong style="font-size:2.2rem;display:block;color:var(--ink)"><?= $activeSponsors ?></strong>
                <span style="color:var(--muted);font-size:0.85rem">Confirmed monthly/one-time</span>
            </div>
            <div style="background:#fff;padding:22px 26px;border-radius:16px;box-shadow:var(--shadow);border-left:4px solid var(--terracotta)">
                <span class="eyebrow" style="margin-bottom:4px">Milestones Published</span>
                <strong style="font-size:2.2rem;display:block;color:var(--ink)"><?= $totalUpdates ?></strong>
                <span style="color:var(--muted);font-size:0.85rem">Classroom progress posts</span>
            </div>
            <div style="background:#fff;padding:22px 26px;border-radius:16px;box-shadow:var(--shadow);border-left:4px solid #5a738e">
                <span class="eyebrow" style="margin-bottom:4px">Registered Users</span>
                <strong style="font-size:2.2rem;display:block;color:var(--ink)"><?= $totalUsers ?></strong>
                <span style="color:var(--muted);font-size:0.85rem">Admin, teachers & donors</span>
            </div>
        </div>

        <!-- Safeguarding Warning -->
        <div class="admin-warning">
            <div>
                <strong>Safeguarding Directives</strong>
                <small style="display:block;color:var(--muted);margin-top:4px">Child Dignity Standard</small>
            </div>
            <div>
                Use approved first names or nicknames only. Never enter surnames, birthdates, school locations, home communities, phone numbers, or family hardship. All donor communications are reviewed and non-identifying.
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success" role="status"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert error" role="alert">
                <strong>Please review:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- EDIT STUDENT FORM (When an edit is requested) -->
        <?php if ($editingStudent): ?>
            <section class="form-card" style="margin-bottom:40px;border:2px solid var(--gold)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
                    <div>
                        <span class="eyebrow">Editing Student Journey</span>
                        <h2 style="margin:4px 0 0">Update Profile: <?= htmlspecialchars($editingStudent['first_name']) ?></h2>
                    </div>
                    <a class="button button-outline" href="admin.php">Cancel Editing ✕</a>
                </div>

                <form method="post" action="admin.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" name="student_id" value="<?= (int)$editingStudent['id'] ?>">

                    <div class="form-grid">
                        <label>
                            Public First Name or Nickname
                            <input name="first_name" maxlength="60" required value="<?= htmlspecialchars($editingStudent['first_name']) ?>">
                            <small>No spaces or surname</small>
                        </label>
                        <label>
                            Age Band
                            <select name="age_band" class="select-input" required>
                                <?php foreach (array('Early primary', 'Upper primary', 'Early secondary', 'Middle secondary', 'Upper secondary') as $band): ?>
                                    <option value="<?= $band ?>" <?= ($editingStudent['age_band'] === $band) ? 'selected' : '' ?>><?= $band ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="wide">
                            Short Approved Biography
                            <textarea name="profile_summary" maxlength="1200" rows="5" required><?= htmlspecialchars($editingStudent['profile_summary']) ?></textarea>
                        </label>
                        <label>
                            Interest Area
                            <input name="interest_area" maxlength="120" required value="<?= htmlspecialchars($editingStudent['interest_area']) ?>">
                        </label>
                        <label>
                            Favorite Subject
                            <input name="favorite_subject" maxlength="120" value="<?= htmlspecialchars($editingStudent['favorite_subject']) ?>">
                        </label>
                        <label class="wide">
                            Current Learning Focus
                            <input name="current_focus" maxlength="160" required value="<?= htmlspecialchars($editingStudent['current_focus']) ?>">
                        </label>
                        <label class="wide">
                            Strengths
                            <input name="strengths" maxlength="180" value="<?= htmlspecialchars($editingStudent['strengths']) ?>">
                        </label>
                        <label class="wide">
                            Aspirations
                            <input name="aspirations" maxlength="180" value="<?= htmlspecialchars($editingStudent['aspirations']) ?>">
                        </label>
                        <label>
                            Classroom Access PIN
                            <input name="access_pin" maxlength="20" value="<?= htmlspecialchars(!empty($editingStudent['access_pin']) ? $editingStudent['access_pin'] : '2026') ?>">
                            <small>PIN for student self-update in lab</small>
                        </label>
                    </div>
                    <button class="button button-gold full" type="submit" style="margin-top:16px">Save Changes to <?= htmlspecialchars($editingStudent['first_name']) ?> <span aria-hidden="true">✓</span></button>
                </form>
            </section>
        <?php endif; ?>

        <!-- CREATE STUDENT & PUBLISH UPDATE FORMS -->
        <div class="admin-grid">
            <!-- Add New Student -->
            <section class="form-card">
                <span class="eyebrow">Add a Student</span>
                <h2>Create a Selectable Profile</h2>
                <form method="post" action="admin.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_student">
                    <div class="form-grid">
                        <label>
                            Public First Name or Nickname
                            <input name="first_name" maxlength="60" required placeholder="First name only">
                            <small>No spaces or surname</small>
                        </label>
                        <label>
                            Age Band
                            <select name="age_band" class="select-input" required>
                                <option value="">Choose one</option>
                                <option>Early primary</option>
                                <option>Upper primary</option>
                                <option>Early secondary</option>
                                <option>Middle secondary</option>
                                <option>Upper secondary</option>
                            </select>
                        </label>
                        <label class="wide">
                            Short Approved Biography
                            <textarea name="profile_summary" maxlength="1200" rows="4" required placeholder="Describe personality, learning interests, and growth in dignity-centered language."></textarea>
                        </label>
                        <label>
                            Interest Area
                            <input name="interest_area" maxlength="120" required placeholder="Creative coding and design">
                        </label>
                        <label>
                            Favorite Subject
                            <input name="favorite_subject" maxlength="120" placeholder="Mathematics">
                        </label>
                        <label class="wide">
                            Current Learning Focus
                            <input name="current_focus" maxlength="160" required placeholder="Building a first web page">
                        </label>
                        <label class="wide">
                            Strengths
                            <input name="strengths" maxlength="180" placeholder="Curiosity, teamwork, and persistence">
                        </label>
                        <label class="wide">
                            Aspirations
                            <input name="aspirations" maxlength="180" placeholder="Create a useful community technology project">
                        </label>
                        <label>
                            Classroom Login PIN
                            <input name="access_pin" maxlength="20" value="2026" placeholder="2026">
                            <small>PIN for student workstation login</small>
                        </label>
                    </div>
                    <button class="button full" type="submit" style="margin-top:14px">Add Student Profile</button>
                </form>
            </section>

            <!-- Publish Milestone Update -->
            <section class="form-card">
                <span class="eyebrow">Publish an Update</span>
                <h2>Add a Learning Milestone</h2>
                <form method="post" action="admin.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_update">
                    <label style="display:block;margin-bottom:14px">
                        Student
                        <select name="student_journey_id" class="select-input" required style="width:100%">
                            <option value="">Choose a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['first_name']) ?> (<?= htmlspecialchars($student['age_band']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="display:block;margin-bottom:14px">
                        Milestone Badge
                        <input name="milestone" maxlength="120" required placeholder="e.g. Project Completed or Module Passed" style="width:100%">
                    </label>
                    <label style="display:block;margin-bottom:14px">
                        Update Title
                        <input name="title" maxlength="180" required placeholder="e.g. A first web page takes shape" style="width:100%">
                    </label>
                    <label style="display:block;margin-bottom:16px">
                        Reviewed Update Summary
                        <textarea name="summary" maxlength="1800" rows="6" required placeholder="Share verified learning progress without private or identifying details." style="width:100%"></textarea>
                    </label>
                    <button class="button full" type="submit">Publish Sponsor Update</button>
                </form>
            </section>
        </div>

        <!-- CURRENT STUDENT PROFILES LIST -->
        <section class="admin-list">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">
                <div>
                    <span class="eyebrow">Current Profiles</span>
                    <h2 style="margin:4px 0 0">Students Available to Sponsors</h2>
                </div>
                <a class="button button-outline" href="<?= app_url('adopt.php') ?>" target="_blank">View Live Public Catalog ↗</a>
            </div>

            <div class="admin-students">
                <?php foreach ($students as $student): ?>
                    <article style="border: 1px solid var(--line);box-shadow:var(--shadow)">
                        <div class="admin-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($student['first_name'], 0, 1))) ?></div>
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <span class="journey-band"><?= htmlspecialchars($student['age_band']) ?></span>
                                <span class="status-pill <?= ((int)$student['active'] === 1) ? 'status-active' : 'status-paused' ?>" style="font-size:0.75rem">
                                    <?= ((int)$student['active'] === 1) ? 'Selectable' : 'Hidden' ?>
                                </span>
                            </div>
                            <h3 style="margin:0 0 6px"><?= htmlspecialchars($student['first_name']) ?></h3>
                            <p style="margin:0 0 6px"><?= htmlspecialchars($student['profile_summary']) ?></p>
                            <small style="color:var(--forest-2);font-weight:700">
                                Focus: <?= htmlspecialchars($student['current_focus']) ?> · PIN: <?= htmlspecialchars(!empty($student['access_pin']) ? $student['access_pin'] : '2026') ?> · <?= (int)$student['sponsor_count'] ?> sponsorships · <?= (int)$student['update_count'] ?> updates
                            </small>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            <a class="button button-gold" style="padding:8px 16px;font-size:0.85rem;text-align:center" href="admin.php?edit=<?= (int)$student['id'] ?>#main-content">Edit Profile</a>
                            <form method="post" action="admin.php" style="margin:0">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_student">
                                <input type="hidden" name="student_journey_id" value="<?= (int)$student['id'] ?>">
                                <input type="hidden" name="active" value="<?= ((int)$student['active'] === 1) ? 0 : 1 ?>">
                                <button class="button <?= ((int)$student['active'] === 1) ? 'button-outline' : '' ?>" style="padding:8px 16px;font-size:0.85rem;width:100%" type="submit">
                                    <?= ((int)$student['active'] === 1) ? 'Hide' : 'Show' ?>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- DONATION & SPONSORSHIP RECONCILIATION -->
        <section class="admin-list" style="margin-top:50px">
            <div>
                <span class="eyebrow">Donation Reconciliation</span>
                <h2>Verify Sponsorship Payments</h2>
                <p>Confirm each gift in the Zeffy dashboard before changing it from pending. Never rely on a donor's browser redirect as payment proof.</p>
            </div>
            <div class="admin-students">
                <?php if (empty($sponsorships)): ?>
                    <p style="background:#fff;padding:24px;border-radius:14px;border:1px solid var(--line);color:var(--muted)">No sponsorship records yet.</p>
                <?php endif; ?>
                <?php foreach ($sponsorships as $item): ?>
                    <article>
                        <div class="admin-avatar" aria-hidden="true">$</div>
                        <div>
                            <span class="journey-band"><?= htmlspecialchars(ucfirst($item['payment_provider'])) ?> · <?= htmlspecialchars(ucfirst($item['status'])) ?></span>
                            <h3><?= htmlspecialchars($item['donor_first'] . ' ' . $item['donor_last']) ?></h3>
                            <p><?= htmlspecialchars($item['email']) ?> · Supporting <?= htmlspecialchars($item['student_first']) ?> · $<?= number_format((float)$item['amount'], 2) ?> <?= ($item['frequency'] === 'monthly') ? '/ month' : 'one time' ?></p>
                        </div>
                        <form method="post" action="admin.php" class="reconcile-form">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_sponsorship">
                            <input type="hidden" name="sponsorship_id" value="<?= (int)$item['id'] ?>">
                            <label>
                                Verified provider reference
                                <input name="provider_reference" maxlength="255" value="<?= htmlspecialchars(!empty($item['provider_reference']) ? $item['provider_reference'] : '') ?>" placeholder="Zeffy transaction ID">
                            </label>
                            <label>
                                Status
                                <select name="status" class="select-input">
                                    <?php foreach (array('pending', 'active', 'paused', 'completed', 'cancelled', 'failed') as $status): ?>
                                        <option value="<?= $status ?>" <?= ($item['status'] === $status) ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button class="button" type="submit">Save</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
