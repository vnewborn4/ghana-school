CREATE DATABASE IF NOT EXISTS ghana_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ghana_school;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_journeys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_code VARCHAR(40) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    age_band VARCHAR(40) NOT NULL,
    interest_area VARCHAR(120) NOT NULL,
    current_focus VARCHAR(160) NOT NULL,
    profile_summary TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sponsorships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    student_journey_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    frequency ENUM('monthly','one-time') NOT NULL,
    payment_provider ENUM('stripe','paypal') NOT NULL,
    status ENUM('pending','active','paused','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
    provider_reference VARCHAR(255) NULL,
    next_payment_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sponsorship_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sponsorship_journey FOREIGN KEY (student_journey_id) REFERENCES student_journeys(id) ON DELETE RESTRICT,
    INDEX idx_sponsorship_user (user_id), INDEX idx_sponsorship_journey (student_journey_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_journey_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    summary TEXT NOT NULL,
    milestone VARCHAR(120) NULL,
    is_sample TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NOT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_update_journey FOREIGN KEY (student_journey_id) REFERENCES student_journeys(id) ON DELETE CASCADE,
    INDEX idx_update_journey_date (student_journey_id, published_at)
) ENGINE=InnoDB;

INSERT INTO student_journeys (public_code,display_name,age_band,interest_area,current_focus,profile_summary) VALUES
('creative-coder','Journey A · Creative Coder','Early secondary','Creative coding and digital design','Building confidence with keyboard skills and visual programming','A curious learner who enjoys turning ideas into colorful digital projects. This privacy-safe profile describes learning interests without publishing a name, school, or family details.'),
('digital-builder','Journey B · Digital Builder','Middle secondary','Hardware, problem-solving, and web basics','Learning how computers work and creating a first web page','A hands-on learner drawn to understanding systems and building useful things. Updates focus on skills and projects rather than private circumstances.'),
('future-analyst','Journey C · Future Analyst','Upper secondary','Data, spreadsheets, and career readiness','Organizing information and presenting a small data project','A thoughtful learner developing practical digital skills for further education and work. The public journey never includes direct contact information.')
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name),age_band=VALUES(age_band),interest_area=VALUES(interest_area),current_focus=VALUES(current_focus),profile_summary=VALUES(profile_summary),active=1;

INSERT INTO student_updates (student_journey_id,title,summary,milestone,published_at)
SELECT id,'First guided project completed','The learner completed a guided project, practiced saving work correctly, and explained one design choice to the group.','Project completed',DATE_SUB(NOW(),INTERVAL 12 DAY) FROM student_journeys j WHERE public_code='creative-coder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='First guided project completed');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,published_at)
SELECT id,'Growing keyboard confidence','Recent practice focused on typing accuracy, file organization, and asking for help when a step was unclear.','Practice streak',DATE_SUB(NOW(),INTERVAL 3 DAY) FROM student_journeys j WHERE public_code='creative-coder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Growing keyboard confidence');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,published_at)
SELECT id,'A first web page takes shape','The learner used headings, paragraphs, and links to build a simple page and reviewed it with a classmate.','Web basics',DATE_SUB(NOW(),INTERVAL 8 DAY) FROM student_journeys j WHERE public_code='digital-builder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='A first web page takes shape');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,published_at)
SELECT id,'From rows to a clear story','The learner organized a small practice dataset and created a chart that made the main pattern easy to explain.','Data project',DATE_SUB(NOW(),INTERVAL 6 DAY) FROM student_journeys j WHERE public_code='future-analyst' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='From rows to a clear story');
