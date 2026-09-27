-- Student Academy: learner accounts, cohorts, modules, assignments,
-- submissions, student web spaces, badges, and an audit trail.
-- Run once on an existing database. See docs/ACADEMY_INTEGRATION.md.

-- ---------------------------------------------------------------------------
-- Staff roles. Sponsors and staff are both adults with email accounts, so they
-- share the users table. Children never do -- they live in `learners`.
-- ---------------------------------------------------------------------------
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('sponsor','teacher','admin') NOT NULL DEFAULT 'sponsor';
UPDATE users SET role='admin' WHERE is_admin=1 AND role='sponsor';
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- ---------------------------------------------------------------------------
-- Cohorts (a class or group with a coach)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cohorts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    term VARCHAR(40) NOT NULL DEFAULT '',
    coach_user_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cohort_coach FOREIGN KEY (coach_user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Learners. Children are never placed in the `users` table.
-- No email, no surname, no date of birth. Age band only.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS learners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_journey_id INT UNSIGNED NULL,
    username VARCHAR(40) NOT NULL UNIQUE,
    display_name VARCHAR(60) NOT NULL,
    age_band VARCHAR(40) NOT NULL DEFAULT '',
    preferred_lang VARCHAR(5) NOT NULL DEFAULT 'en',
    pin_hash VARCHAR(255) NOT NULL,
    must_change_pin TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    cohort_id INT UNSIGNED NULL,
    guardian_consent_on DATE NULL,
    consent_scope ENUM('learning_only','learning_and_sponsor_updates')
        NOT NULL DEFAULT 'learning_only',
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_learner_journey FOREIGN KEY (student_journey_id)
        REFERENCES student_journeys(id) ON DELETE SET NULL,
    CONSTRAINT fk_learner_cohort FOREIGN KEY (cohort_id)
        REFERENCES cohorts(id) ON DELETE SET NULL,
    CONSTRAINT fk_learner_creator FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_learner_cohort (cohort_id, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Curriculum
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    summary TEXT NOT NULL,
    tool ENUM('blockly','snap','scratch','webdev','python','h5p','unplugged') NOT NULL,
    tool_url VARCHAR(255) NULL,
    age_band VARCHAR(40) NOT NULL DEFAULT '8-14',
    ges_curriculum_ref VARCHAR(80) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    published TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    brief TEXT NOT NULL,
    submission_type ENUM('file','code','screenshot','reflection','site') NOT NULL
        DEFAULT 'reflection',
    sort_order INT NOT NULL DEFAULT 0,
    due_on DATE NULL,
    CONSTRAINT fk_assignment_module FOREIGN KEY (module_id)
        REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_assignment_module (module_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which modules a cohort is working through
CREATE TABLE IF NOT EXISTS cohort_modules (
    cohort_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (cohort_id, module_id),
    CONSTRAINT fk_cm_cohort FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    learner_id INT UNSIGNED NOT NULL,
    body MEDIUMTEXT NULL,
    file_path VARCHAR(255) NULL,
    original_name VARCHAR(160) NULL,
    status ENUM('draft','submitted','reviewed','needs_work') NOT NULL DEFAULT 'draft',
    teacher_feedback TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    shareable TINYINT(1) NOT NULL DEFAULT 0,
    shared_at DATETIME NULL,
    submitted_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_submission_assignment FOREIGN KEY (assignment_id)
        REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_learner FOREIGN KEY (learner_id)
        REFERENCES learners(id) ON DELETE CASCADE,
    CONSTRAINT fk_submission_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_submission (assignment_id, learner_id),
    INDEX idx_submission_learner (learner_id, status),
    INDEX idx_submission_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Student web spaces  (/students/<slug>/)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learner_id INT UNSIGNED NOT NULL UNIQUE,
    slug VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL DEFAULT 'My page',
    status ENUM('draft','pending_review','published','unpublished','suspended')
        NOT NULL DEFAULT 'draft',
    bytes_used INT UNSIGNED NOT NULL DEFAULT 0,
    file_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    review_note TEXT NULL,
    published_at DATETIME NULL,
    published_by INT UNSIGNED NULL,
    last_edited_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_site_learner FOREIGN KEY (learner_id)
        REFERENCES learners(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_publisher FOREIGN KEY (published_by)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_site_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Recognition
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(80) NOT NULL,
    icon VARCHAR(16) NOT NULL DEFAULT '*',
    criteria VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learner_badges (
    learner_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    awarded_on DATETIME NOT NULL,
    PRIMARY KEY (learner_id, badge_id),
    CONSTRAINT fk_lb_learner FOREIGN KEY (learner_id) REFERENCES learners(id) ON DELETE CASCADE,
    CONSTRAINT fk_lb_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Audit trail. Adults acting on children's records must leave a record.
-- Append-only: the application never updates or deletes rows here.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT UNSIGNED NULL,
    actor_learner_id INT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    subject_type VARCHAR(40) NOT NULL DEFAULT '',
    subject_id INT UNSIGNED NULL,
    detail VARCHAR(500) NULL,
    ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_subject (subject_type, subject_id),
    INDEX idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Starter curriculum for ages 8-14. Unpublished modules stay hidden from
-- learners until a teacher publishes them.
-- ---------------------------------------------------------------------------
INSERT INTO badges (slug,title,icon,criteria) VALUES
 ('first-sign-in','First sign in','1','Signed in to the academy for the first time'),
 ('first-project','First project','2','Completed a first reviewed assignment'),
 ('page-published','Page published','3','Published a web page reviewed by a teacher'),
 ('five-lessons','Five lessons','4','Completed five reviewed assignments'),
 ('helper','Helper','5','Helped a classmate, awarded by a teacher')
ON DUPLICATE KEY UPDATE title=VALUES(title), icon=VALUES(icon), criteria=VALUES(criteria);

-- Modules whose activity must be installed under lab/ start unpublished.
-- Publish them once the tool is in place; see lab/README.md.
INSERT INTO modules (slug,title,summary,tool,tool_url,age_band,sort_order,published) VALUES
 ('getting-started','Getting started','Find your way around a computer: the mouse, the keyboard, saving your work, and asking for help when a step is unclear.','unplugged',NULL,'8-14',10,1),
 ('staying-safe','Staying safe online','What to share and what to keep private. Why we never put our full name, address, phone number, or school on a web page.','unplugged',NULL,'8-14',20,1),
 ('blockly-maze','Blockly: Maze','Give a character step-by-step instructions to reach the goal. Your first taste of sequence, loops, and fixing what does not work yet.','blockly','lab/blockly-games/maze.html','8-12',30,0),
 ('blockly-turtle','Blockly: Turtle drawing','Draw shapes and patterns with code. Repeat blocks turn four lines into a square and a square into a whole pattern.','blockly','lab/blockly-games/turtle.html','8-14',40,0),
 ('scratch-animation','Scratch: my first animation','Make a character move, speak, and react. Plan a short scene and build it.','scratch','lab/scratch/','9-14',50,0),
 ('first-web-page','My first web page','Headings, paragraphs, and pictures. Build the first version of your own page on the school website.','webdev',NULL,'10-14',60,1),
 ('colour-and-style','Colour and style','Use CSS to choose colours, fonts, and spacing so your page looks the way you imagined it.','webdev',NULL,'10-14',70,1)
ON DUPLICATE KEY UPDATE title=VALUES(title), summary=VALUES(summary), tool=VALUES(tool),
  tool_url=VALUES(tool_url), age_band=VALUES(age_band), sort_order=VALUES(sort_order);

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'Show me what you learned','Write two or three sentences: what did you practise today, and what was the hardest part?','reflection',10
FROM modules m WHERE m.slug='getting-started'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='Show me what you learned');

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'The three private things','Name three things we never put on a public web page, and say why for each one.','reflection',10
FROM modules m WHERE m.slug='staying-safe'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='The three private things');

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'Finish Maze level 10','Work through the maze levels. When you reach level 10, take a screenshot and tell us which level was hardest.','screenshot',10
FROM modules m WHERE m.slug='blockly-maze'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='Finish Maze level 10');

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'Draw a pattern with a loop','Use a repeat block to draw a shape or pattern. Take a screenshot of your drawing and of the blocks you used.','screenshot',10
FROM modules m WHERE m.slug='blockly-turtle'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='Draw a pattern with a loop');

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'Build your first page','Open My page and add a heading, two paragraphs about something you like, and one picture. Then ask your teacher to check it.','site',10
FROM modules m WHERE m.slug='first-web-page'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='Build your first page');

INSERT INTO assignments (module_id,title,brief,submission_type,sort_order)
SELECT id,'Give your page a colour scheme','Choose a background colour, a text colour, and a font for your page. Explain why you chose them.','site',10
FROM modules m WHERE m.slug='colour-and-style'
  AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.module_id=m.id AND a.title='Give your page a colour scheme');
