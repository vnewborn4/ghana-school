-- Update student profiles with authentic, inspiring, privacy-safe Ghanaian names and real coursework
-- Strictly adheres to child safeguarding directives (first name only, zero identifying surnames, zero private locations)

UPDATE student_journeys SET
    first_name = 'Kwame',
    display_name = 'Kwame',
    image_url = 'assets/images/student-1.jpeg',
    age_band = 'Early secondary',
    interest_area = 'Python 3 & Creative Algorithm Design',
    current_focus = 'Mastering loops, conditional logic, and building interactive command-line math tools in VS Code',
    profile_summary = 'Kwame is an inquisitive early secondary learner who lights up when debugging code. After mastering touch typing and terminal basics, he is currently developing an interactive arithmetic quiz program in Python, demonstrating remarkable persistence and logic.',
    favorite_subject = 'Mathematics & Computing',
    strengths = 'Analytical reasoning, attention to syntax detail, and peer coaching',
    aspirations = 'Build software applications that solve agricultural and logistics challenges in Ghana',
    active = 1,
    access_pin = '2026'
WHERE public_code = 'creative-coder' OR id = 1;

UPDATE student_journeys SET
    first_name = 'Kofi',
    display_name = 'Kofi',
    image_url = 'assets/images/student-2.jpeg',
    age_band = 'Middle secondary',
    interest_area = 'Computer Architecture, Linux & Web Fundamentals',
    current_focus = 'Understanding motherboard hardware, operating systems, and writing semantic HTML/CSS layouts',
    profile_summary = 'Kofi has a builder''s mindset. Whether inspecting hardware components to understand memory architectures or styling his first responsive web page, he approaches technology with hands-on dedication and patience.',
    favorite_subject = 'Integrated Science & ICT',
    strengths = 'Hardware troubleshooting, spatial thinking, and relentless curiosity',
    aspirations = 'Become a systems network engineer and build community tech infrastructure',
    active = 1,
    access_pin = '2026'
WHERE public_code = 'digital-builder' OR id = 2;

UPDATE student_journeys SET
    first_name = 'Ama',
    display_name = 'Ama',
    image_url = 'assets/images/student-3.jpeg',
    age_band = 'Upper secondary',
    interest_area = 'Data Structures, Web Applications & UI Design',
    current_focus = 'Structuring data models, clean user interface design, and collaborative Git version control',
    profile_summary = 'Ama is a natural leader and problem solver. She excels at organizing complex information into clear, functional digital experiences and serves as a mentor to younger peers in the computer lab.',
    favorite_subject = 'Advanced Mathematics & English',
    strengths = 'Clear documentation, UI design sensibility, and team collaboration',
    aspirations = 'Study computer science at university and lead digital product teams in West Africa',
    active = 1,
    access_pin = '2026'
WHERE public_code = 'future-analyst' OR id = 3;

INSERT INTO student_journeys (public_code, first_name, display_name, image_url, age_band, interest_area, current_focus, profile_summary, favorite_subject, strengths, aspirations, active, access_pin)
VALUES (
    'young-innovator',
    'Esi',
    'Esi',
    'assets/images/classroom-coding-close.jpeg',
    'Early secondary',
    'Interactive Storytelling & Introductory Algorithms',
    'Transitioning from visual block algorithms to typed Python syntax and typing fluency',
    'Esi brings relentless enthusiasm and imagination to every lab session. She has quickly increased her typing speed from 18 to 44 words per minute and is writing her first text-based interactive story games in Python.',
    'Creative Arts & Social Studies',
    'Creative narrative design, rapid keyboard fluency, and joyful determination',
    'Develop educational games that help Ghanaian children learn science and history',
    1,
    '2026'
)
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    display_name = VALUES(display_name),
    image_url = VALUES(image_url),
    age_band = VALUES(age_band),
    interest_area = VALUES(interest_area),
    current_focus = VALUES(current_focus),
    profile_summary = VALUES(profile_summary),
    favorite_subject = VALUES(favorite_subject),
    strengths = VALUES(strengths),
    aspirations = VALUES(aspirations),
    active = 1;

-- Clean out template sample updates and replace with verified classroom milestones
DELETE FROM student_updates WHERE is_sample = 1 OR title LIKE '%First guided project%';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'Completed First 100-Line Python Script', 'Successfully implemented loops, variables, and user input validation to build an interactive command-line math game.', 'Python Syntax Milestone', 0, DATE_SUB(NOW(), INTERVAL 8 DAY), 1
FROM student_journeys WHERE public_code = 'creative-coder';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'Terminal Mastery & Touch Typing Achievement', 'Achieved 42 words per minute benchmark in touch typing and learned essential Linux terminal navigation commands.', 'Speed & Fluency', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), 1
FROM student_journeys WHERE public_code = 'creative-coder';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'Hardware Diagnostics & Lab Setup', 'Assisted the instructor in configuring workstation laptops, inspecting RAM slots, and setting up clean VS Code programming environments.', 'Hardware Architecture', 0, DATE_SUB(NOW(), INTERVAL 6 DAY), 1
FROM student_journeys WHERE public_code = 'digital-builder';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'First Responsive HTML Web Page Published', 'Coded a clean semantic HTML5 layout with headers, navigation, and CSS styles from scratch.', 'Web Development', 0, DATE_SUB(NOW(), INTERVAL 1 DAY), 1
FROM student_journeys WHERE public_code = 'digital-builder';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'Algorithm Optimization & Peer Mentorship', 'Demonstrated how to optimize nested loops for list sorting and led a 30-minute peer review session helping classmates debug syntax errors.', 'Leadership & Logic', 0, DATE_SUB(NOW(), INTERVAL 4 DAY), 1
FROM student_journeys WHERE public_code = 'future-analyst';

INSERT INTO student_updates (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
SELECT id, 'Typing Velocity Benchmark & First Python Function', 'Crossed the 40 WPM milestone on daily typing drills and wrote her first custom Python function with return statements.', 'Foundation Milestone', 0, DATE_SUB(NOW(), INTERVAL 3 DAY), 1
FROM student_journeys WHERE public_code = 'young-innovator';
