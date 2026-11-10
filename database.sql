CREATE DATABASE IF NOT EXISTS ghana_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ghana_school;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    is_teacher TINYINT(1) NOT NULL DEFAULT 0,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_journeys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_code VARCHAR(40) NOT NULL UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) NULL,
    age_band VARCHAR(40) NOT NULL,
    interest_area VARCHAR(120) NOT NULL,
    current_focus VARCHAR(160) NOT NULL,
    profile_summary TEXT NOT NULL,
    favorite_subject VARCHAR(120) NOT NULL DEFAULT '',
    strengths VARCHAR(180) NOT NULL DEFAULT '',
    aspirations VARCHAR(180) NOT NULL DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    access_pin VARCHAR(20) NOT NULL DEFAULT '2026',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sponsorships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    student_journey_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    frequency ENUM('monthly','one-time') NOT NULL,
    payment_provider ENUM('zeffy','stripe','paypal') NOT NULL DEFAULT 'zeffy',
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

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO student_journeys 
    (id, public_code, first_name, display_name, image_url, age_band, interest_area, current_focus, profile_summary, favorite_subject, strengths, aspirations, access_pin, active)
VALUES
(
    1,
    'emmanuel-p',
    'Emmanuel',
    'Emmanuel',
    'assets/images/students/emmanuel.jpg',
    'Age 10 • Primary 6',
    'Computer Science & Mathematics',
    'Foundational Python algorithms and mathematical problem solving',
    'Emmanuel is a focused Class 6 student with exceptional analytical aptitude. In his baseline program assessments, he scored an impressive 27/30 in English and 22/30 in Mathematics, demonstrating high enthusiasm for coding.',
    'Mathematics & English',
    'English: 27/30; Mathematics: 22/30; self-assessment: 6/10','Not yet provided'),
('shantel-g','Shantel','Shantel','assets/images/13fb6dc6-5d61-44b0-ad23-a6b6ced4a124.JPG','JHS 1','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Shantel is a JHS 1 learner. In the shared assessment, she scored 26/30 in English and 17/30 in mathematics.','Not yet provided','English: 26/30; Mathematics: 17/30; self-assessment: 5/10','Not yet provided'),
('ammielle-o','Ammielle','Ammielle','assets/images/066487eb-27fd-49de-9613-629f125616e1.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Ammielle is a Class 4 learner. In the shared assessment, she scored 28/30 in English and 16.5/30 in mathematics.','Not yet provided','English: 28/30; Mathematics: 16.5/30; self-assessment: 4/10','Not yet provided'),
('benedicta-a','Benedicta','Benedicta','assets/images/69bf72cc-5b7f-4344-8182-2299fb8edee7.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Benedicta is a Class 4 learner. In the shared assessment, she scored 29/30 in English and 24/30 in mathematics.','Not yet provided','English: 29/30; Mathematics: 24/30; self-assessment: 8/10','Not yet provided'),
('bruce-b','Bruce','Bruce','assets/images/90cceb47-213a-4906-a63a-e5a7029fcf89.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Bruce is a Class 4 learner. In the shared assessment, he scored 19/30 in English and 12.5/30 in mathematics.','Not yet provided','English: 19/30; Mathematics: 12.5/30; self-assessment: 2/10','Not yet provided'),
('isaac-m','Isaac','Isaac','assets/images/7a8bffae-95a0-45b7-ab78-85625c8fb4ad.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Isaac is a Class 6 learner. In the shared assessment, he scored 28/30 in English and 25/30 in mathematics.','Not yet provided','English: 28/30; Mathematics: 25/30; self-assessment: 3/10','Not yet provided'),
('geoffrey-a','Geoffrey','Geoffrey','assets/images/8e86c47a-3d47-4de4-a71f-7f77c116e7ed.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Geoffrey is a Class 6 learner. In the shared assessment, he scored 27/30 in English and 25/30 in mathematics.','Not yet provided','English: 27/30; Mathematics: 25/30; self-assessment: 4/10','Not yet provided'),
('benedict-a','Benedict','Benedict','assets/images/db7d0569-1768-4bee-a3ec-1b1545940c09.JPG','Primary Class 5','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Benedict is a Class 5 learner. In the shared assessment, he scored 26/30 in English and 20/30 in mathematics.','Not yet provided','English: 26/30; Mathematics: 20/30; self-assessment: 4/10','Not yet provided'),
('samuel-t','Samuel','Samuel','assets/images/b505b43d-cecd-45a5-9592-5f862c812cdf.JPG','Primary Class 5','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Samuel is a Class 5 learner. In the shared assessment, he scored 25/30 in English and 22/30 in mathematics.','Not yet provided','English: 25/30; Mathematics: 22/30; self-assessment: 5/10','Not yet provided'),
('beauty-t','Beauty','Beauty','assets/images/f6c988c2-cb7c-46bd-817c-8cef54fa25ac.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Beauty is a Class 6 learner. In the shared assessment, she scored 30/30 in English and 22/30 in mathematics.','Not yet provided','English: 30/30; Mathematics: 22/30; self-assessment: 3/10','Not yet provided')
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),display_name=VALUES(display_name),image_url=VALUES(image_url),age_band=VALUES(age_band),interest_area=VALUES(interest_area),current_focus=VALUES(current_focus),profile_summary=VALUES(profile_summary),favorite_subject=VALUES(favorite_subject),strengths=VALUES(strengths),aspirations=VALUES(aspirations),active=1;
