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

INSERT INTO student_journeys (public_code,first_name,display_name,image_url,age_band,interest_area,current_focus,profile_summary,favorite_subject,strengths,aspirations) VALUES
('creative-coder','Kwame','Kwame','assets/images/student-1.jpeg','Early secondary','Python 3 & Creative Algorithm Design','Mastering loops, conditional logic, and building interactive command-line math tools in VS Code','Kwame is an inquisitive early secondary learner who lights up when debugging code. After mastering touch typing and terminal basics, he is currently developing an interactive arithmetic quiz program in Python, demonstrating remarkable persistence and logic.','Mathematics & Computing','Analytical reasoning, attention to syntax detail, and peer coaching','Build software applications that solve agricultural and logistics challenges in Ghana'),
('digital-builder','Kofi','Kofi','assets/images/student-2.jpeg','Middle secondary','Computer Architecture, Linux & Web Fundamentals','Understanding motherboard hardware, operating systems, and writing semantic HTML/CSS layouts','Kofi has a builder\'s mindset. Whether inspecting hardware components to understand memory architectures or styling his first responsive web page, he approaches technology with hands-on dedication and patience.','Integrated Science & ICT','Hardware troubleshooting, spatial thinking, and relentless curiosity','Become a systems network engineer and build community tech infrastructure'),
('future-analyst','Ama','Ama','assets/images/student-3.jpeg','Upper secondary','Data Structures, Web Applications & UI Design','Structuring data models, clean user interface design, and collaborative Git version control','Ama is a natural leader and problem solver. She excels at organizing complex information into clear, functional digital experiences and serves as a mentor to younger peers in the computer lab.','Advanced Mathematics & English','Clear documentation, UI design sensibility, and team collaboration','Study computer science at university and lead digital product teams in West Africa'),
('young-innovator','Esi','Esi','assets/images/classroom-coding-close.jpeg','Early secondary','Interactive Storytelling & Introductory Algorithms','Transitioning from visual block algorithms to typed Python syntax and typing fluency','Esi brings relentless enthusiasm and imagination to every lab session. She has quickly increased her typing speed from 18 to 44 words per minute and is writing her first text-based interactive story games in Python.','Creative Arts & Social Studies','Creative narrative design, rapid keyboard fluency, and joyful determination','Develop educational games that help Ghanaian children learn science and history')
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),display_name=VALUES(display_name),image_url=VALUES(image_url),age_band=VALUES(age_band),interest_area=VALUES(interest_area),current_focus=VALUES(current_focus),profile_summary=VALUES(profile_summary),favorite_subject=VALUES(favorite_subject),strengths=VALUES(strengths),aspirations=VALUES(aspirations),active=1;

INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'Completed First 100-Line Python Script','Successfully implemented loops, variables, and user input validation to build an interactive command-line math game.','Python Syntax Milestone',0,DATE_SUB(NOW(),INTERVAL 8 DAY) FROM student_journeys j WHERE public_code='creative-coder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Completed First 100-Line Python Script');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'Terminal Mastery & Touch Typing Achievement','Achieved 42 words per minute benchmark in touch typing and learned essential Linux terminal navigation commands.','Speed & Fluency',0,DATE_SUB(NOW(),INTERVAL 2 DAY) FROM student_journeys j WHERE public_code='creative-coder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Terminal Mastery & Touch Typing Achievement');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'Hardware Diagnostics & Lab Setup','Assisted the instructor in configuring workstation laptops, inspecting RAM slots, and setting up clean VS Code programming environments.','Hardware Architecture',0,DATE_SUB(NOW(),INTERVAL 6 DAY) FROM student_journeys j WHERE public_code='digital-builder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Hardware Diagnostics & Lab Setup');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'First Responsive HTML Web Page Published','Coded a clean semantic HTML5 layout with headers, navigation, and CSS styles from scratch.','Web Development',0,DATE_SUB(NOW(),INTERVAL 1 DAY) FROM student_journeys j WHERE public_code='digital-builder' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='First Responsive HTML Web Page Published');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'Algorithm Optimization & Peer Mentorship','Demonstrated how to optimize nested loops for list sorting and led a 30-minute peer review session helping classmates debug syntax errors.','Leadership & Logic',0,DATE_SUB(NOW(),INTERVAL 4 DAY) FROM student_journeys j WHERE public_code='future-analyst' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Algorithm Optimization & Peer Mentorship');
INSERT INTO student_updates (student_journey_id,title,summary,milestone,is_sample,published_at)
SELECT id,'Typing Velocity Benchmark & First Python Function','Crossed the 40 WPM milestone on daily typing drills and wrote her first custom Python function with return statements.','Foundation Milestone',0,DATE_SUB(NOW(),INTERVAL 3 DAY) FROM student_journeys j WHERE public_code='young-innovator' AND NOT EXISTS (SELECT 1 FROM student_updates u WHERE u.student_journey_id=j.id AND u.title='Typing Velocity Benchmark & First Python Function');
