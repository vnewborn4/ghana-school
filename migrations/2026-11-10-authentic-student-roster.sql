-- Complete authentic student roster provided by Ghana Country Director Francis Mckikuchi Bantey
-- Includes verified baseline academic evaluation scores, class level, individual portraits, and milestones.
-- Child Safeguarding: First names only, no personal residence or sensitive data.

START TRANSACTION;

-- Clean out previous temporary sample profiles
DELETE FROM student_updates;
DELETE FROM sponsorships WHERE student_journey_id IS NOT NULL;
DELETE FROM student_journeys;

-- Insert the 10 authentic students
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
    'English: 27/30; Mathematics: 22/30; strong logical reasoning and rapid code comprehension.',
    'Aspires to become a software engineer and robotics developer for West African tech infrastructure.',
    '2026',
    1
),
(
    2,
    'shantel-g',
    'Shantel',
    'Shantel',
    'assets/images/students/shantel.jpg',
    'Age 12 • JHS 1',
    'STEM & Digital Literacy',
    'Junior High computing curriculum, logic loops, and collaborative programming',
    'Shantel is a bright JHS 1 student with natural leadership and communication skills. She recorded 26/30 in English and 17/30 in Mathematics, showing great dedication to mastering digital tools and peer collaboration.',
    'English & Information Technology',
    'English: 26/30; Mathematics: 17/30; collaborative team leadership and verbal clarity.',
    'Aspires to lead technology-driven community initiatives and educational software projects in Ghana.',
    '2026',
    1
),
(
    3,
    'ammielle-o',
    'Ammielle',
    'Ammielle',
    'assets/images/students/ammielle.jpg',
    'Age 9 • Primary 4',
    'Creative Coding & Digital Arts',
    'Visual problem solving, keyboarding, and introductory interactive Python',
    'Ammielle is a creative 9-year-old in Class 4 with an inquisitive mind. She achieved a stellar 28/30 in English and 16.5/30 in Mathematics, excelling in creative digital tasks and visual storytelling.',
    'English & Creative Arts',
    'English: 28/30; Mathematics: 16.5/30; high curiosity, visual acuity, and persistent problem solving.',
    'Aspires to design educational digital games and interactive learning software for young learners.',
    '2026',
    1
),
(
    4,
    'benedicta-a',
    'Benedicta',
    'Benedicta',
    'assets/images/students/benedicta.jpg',
    'Age 8 • Primary 4',
    'Early Logic & Applied Sciences',
    'Computational thinking, structured problem solving, and science exploration',
    'Benedicta is an academic prodigy in Class 4. At just 8 years old, she earned outstanding scores of 29/30 in English, 24/30 in Mathematics, and 8/10 in self-confidence, making her one of the highest overall scorers.',
    'Mathematics & Integrated Science',
    'Top academic performer: English 29/30, Mathematics 24/30; superior self-discipline and speed of comprehension.',
    'Aspires to study computational sciences and medicine to pioneer healthcare technologies.',
    '2026',
    1
),
(
    5,
    'dominic-b',
    'Dominic',
    'Dominic',
    'assets/images/students/dominic.jpg',
    'Age 13 • Primary 4',
    'Hardware Systems & Applied Computing',
    'Hands-on workstation maintenance, peripheral interfacing, and basic scripting',
    'Dominic is a determined, hands-on learner in Class 4 who thrives in practical lab workshops. He demonstrates strong mechanical curiosity and remarkable tenacity when assembling and troubleshooting hardware.',
    'Practical Electronics & Computing',
    'Hands-on mechanical curiosity, persistent work ethic, and dedication to practical mastery in the computer lab.',
    'Aspires to master computer networking, electrical engineering, and IT systems infrastructure.',
    '2026',
    1
),
(
    6,
    'isaac-m',
    'Isaac',
    'Isaac',
    'assets/images/students/isaac.jpg',
    'Age 12 • Primary 6',
    'Physics, Mathematics & Code',
    'Advanced Class 6 mathematics, conditional programming, and algorithmic data flow',
    'Named after the father of physics, Isaac lives up to his name with a sharp mathematical mind. He recorded excellent baseline scores of 28/30 in English and 25/30 in Mathematics, displaying great discipline.',
    'Mathematics & Physical Sciences',
    'High academic achiever: Mathematics 25/30, English 28/30; conceptual abstraction and deductive logic.',
    'Aspires to become an aerospace engineer and computer programmer.',
    '2026',
    1
),
(
    7,
    'geoffrey-a',
    'Geoffrey',
    'Geoffrey',
    'assets/images/students/geoffrey.jpg',
    'Age 9 • Primary 6',
    'Mathematics & Software Logic',
    'Mathematical problem sets, Python loops, and structured debugging',
    'Geoffrey is an extraordinarily gifted 9-year-old already accelerated to Class 6. His evaluation reflects brilliant scores: 27/30 in English and 25/30 in Mathematics, proving advanced cognitive ability.',
    'Mathematics & Computing',
    'Mathematics 25/30, English 27/30; accelerated cognitive ability, quick arithmetic recall, and focus.',
    'Aspires to become a software architect and data scientist.',
    '2026',
    1
),
(
    8,
    'benedict-a',
    'Benedict',
    'Benedict',
    'assets/images/students/benedict.jpg',
    'Age 11 • Primary 5',
    'Software Design & Logic',
    'Class 5 digital fundamentals, structured coding patterns, and typing proficiency',
    'Benedict is a disciplined Class 5 student with a well-rounded academic record. He demonstrated consistent excellence with 26/30 in English and 20/30 in Mathematics, showing thorough attention to detail.',
    'English, Science & ICT',
    'Balanced performance: English 26/30, Mathematics 20/30; thorough attention to detail and methodology.',
    'Aspires to develop smart agricultural technology solutions and modern farming software.',
    '2026',
    1
),
(
    9,
    'samuel-t',
    'Samuel',
    'Samuel',
    'assets/images/students/samuel.jpg',
    'Age 10 • Primary 5',
    'Digital Systems & Leadership',
    'System fundamentals, data organization, and interactive problem solving',
    'Samuel is an engaging Class 5 learner with a natural flair for leadership. He scored 25/30 in English and 22/30 in Mathematics in his program evaluation, bringing proactive energy to classroom discussions.',
    'Information Technology & Social Studies',
    'English 25/30, Mathematics 22/30; articulate oral presentation and proactive classroom participation.',
    'Aspires to become a systems administrator and tech enterprise leader.',
    '2026',
    1
),
(
    10,
    'beauty-t',
    'Beauty',
    'Beauty',
    'assets/images/students/beauty.jpg',
    'Age 12 • Primary 6',
    'Web Engineering & Design',
    'Web markup, layout styling, and algorithmic scripting in the Accra lab',
    'Beauty delivered a flawless, perfect score of 30/30 in English alongside 22/30 in Mathematics in her assessment. She brings remarkable poise, intellect, and precision to web design and algorithmic exercises.',
    'English Literature & Computing',
    'Perfect 30/30 English score, 22/30 in Mathematics; outstanding reading comprehension and design precision.',
    'Aspires to become a senior web engineer and tech entrepreneur building digital platforms.',
    '2026',
    1
);

-- Learning Milestones for the authentic roster
INSERT INTO student_updates 
    (student_journey_id, title, summary, milestone, is_sample, published_at, visible)
VALUES
(1, 'Baseline Assessment Mastery', 'Emmanuel completed the initial computational thinking diagnostic with 27/30 English and 22/30 Math scores, mastering conditional loop structures.', 'Class 6 Baseline', 0, '2026-10-18 10:30:00', 1),
(1, 'Solar Battery Python Calculation', 'Built a working script calculating renewable battery storage run-times for off-grid computer labs.', 'Python Applied Project', 0, '2026-10-25 14:15:00', 1),
(2, 'Collaborative Loop Architecture', 'Shantel led her workstation cohort in drafting a reusable nested loop pattern for community data aggregation.', 'JHS 1 Milestone', 0, '2026-10-20 11:00:00', 1),
(3, 'Creative Canvas Drawing in Python', 'Ammielle programmed turtle graphics to generate geometric Kente-inspired cloth patterns on the lab monitors.', 'Class 4 Creative', 0, '2026-10-22 09:45:00', 1),
(4, 'Accelerated Logic Gate Simulation', 'Benedicta (Age 8) scored 29/30 English and 24/30 Math, completing all truth-table logic exercises ahead of schedule.', 'Academic Honors', 0, '2026-10-24 13:20:00', 1),
(5, 'Lab Hardware Architecture Inspection', 'Dominic assisted Instructor Famous in testing RAM integrity and peripheral cable continuity across 12 workstations.', 'Lab Hardware Lead', 0, '2026-10-26 15:40:00', 1),
(6, 'Physics Vector Scripting', 'Isaac developed a command-line physics velocity solver incorporating multi-variable Newtonian motion equations.', 'Math & Physics Lead', 0, '2026-10-28 11:15:00', 1),
(7, 'Class 6 Algorithmic Optimization', 'Geoffrey (Age 9) achieved 25/30 in Math and streamlined array search runtime complexity in Python.', 'Algorithm Speed Test', 0, '2026-10-30 14:00:00', 1),
(8, 'Agri-Tech Sensor Simulation', 'Benedict authored an input validation module that simulates soil moisture sensor data processing.', 'ICT Applied Project', 0, '2026-11-02 10:50:00', 1),
(9, 'Operating System Configuration Guide', 'Samuel drafted a step-by-step terminal cheat-sheet for his classmates to navigate directory trees and manage files.', 'Lab Leadership', 0, '2026-11-05 16:30:00', 1),
(10, 'Perfect English Score & Web Layout', 'Beauty achieved a perfect 30/30 in English and completed a structured HTML/CSS layout representing Accra heritage.', 'Top Scorer Honors', 0, '2026-11-08 12:00:00', 1);

COMMIT;
