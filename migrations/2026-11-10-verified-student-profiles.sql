-- Replace unsupported demo profiles with records supplied by Ghana program staff on 2025-05-12.
-- Public fields intentionally use first names only and omit exact ages and school names.

START TRANSACTION;

DELETE FROM student_updates
WHERE student_journey_id IN (
    SELECT id FROM student_journeys
    WHERE public_code IN ('creative-coder', 'digital-builder', 'future-analyst', 'young-innovator')
);

UPDATE student_journeys
SET active = 0
WHERE public_code IN ('creative-coder', 'digital-builder', 'future-analyst', 'young-innovator');

INSERT INTO student_journeys
    (public_code, first_name, display_name, image_url, age_band, interest_area, current_focus, profile_summary, favorite_subject, strengths, aspirations, active)
VALUES
    ('emmanuel-p','Emmanuel','Emmanuel','assets/images/a4642998-539b-4370-a0f2-c6cc9c10a98a.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Emmanuel is a Class 6 learner. In the shared assessment, he scored 27/30 in English and 22/30 in mathematics.','Not yet provided','English: 27/30; Mathematics: 22/30; self-assessment: 6/10','Not yet provided',1),
    ('shantel-g','Shantel','Shantel','assets/images/13fb6dc6-5d61-44b0-ad23-a6b6ced4a124.JPG','JHS 1','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Shantel is a JHS 1 learner. In the shared assessment, she scored 26/30 in English and 17/30 in mathematics.','Not yet provided','English: 26/30; Mathematics: 17/30; self-assessment: 5/10','Not yet provided',1),
    ('ammielle-o','Ammielle','Ammielle','assets/images/066487eb-27fd-49de-9613-629f125616e1.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Ammielle is a Class 4 learner. In the shared assessment, she scored 28/30 in English and 16.5/30 in mathematics.','Not yet provided','English: 28/30; Mathematics: 16.5/30; self-assessment: 4/10','Not yet provided',1),
    ('benedicta-a','Benedicta','Benedicta','assets/images/69bf72cc-5b7f-4344-8182-2299fb8edee7.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Benedicta is a Class 4 learner. In the shared assessment, she scored 29/30 in English and 24/30 in mathematics.','Not yet provided','English: 29/30; Mathematics: 24/30; self-assessment: 8/10','Not yet provided',1),
    ('bruce-b','Bruce','Bruce','assets/images/90cceb47-213a-4906-a63a-e5a7029fcf89.JPG','Primary Class 4','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Bruce is a Class 4 learner. In the shared assessment, he scored 19/30 in English and 12.5/30 in mathematics.','Not yet provided','English: 19/30; Mathematics: 12.5/30; self-assessment: 2/10','Not yet provided',1),
    ('isaac-m','Isaac','Isaac','assets/images/7a8bffae-95a0-45b7-ab78-85625c8fb4ad.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Isaac is a Class 6 learner. In the shared assessment, he scored 28/30 in English and 25/30 in mathematics.','Not yet provided','English: 28/30; Mathematics: 25/30; self-assessment: 3/10','Not yet provided',1),
    ('geoffrey-a','Geoffrey','Geoffrey','assets/images/8e86c47a-3d47-4de4-a71f-7f77c116e7ed.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Geoffrey is a Class 6 learner. In the shared assessment, he scored 27/30 in English and 25/30 in mathematics.','Not yet provided','English: 27/30; Mathematics: 25/30; self-assessment: 4/10','Not yet provided',1),
    ('benedict-a','Benedict','Benedict','assets/images/db7d0569-1768-4bee-a3ec-1b1545940c09.JPG','Primary Class 5','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Benedict is a Class 5 learner. In the shared assessment, he scored 26/30 in English and 20/30 in mathematics.','Not yet provided','English: 26/30; Mathematics: 20/30; self-assessment: 4/10','Not yet provided',1),
    ('samuel-t','Samuel','Samuel','assets/images/b505b43d-cecd-45a5-9592-5f862c812cdf.JPG','Primary Class 5','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Samuel is a Class 5 learner. In the shared assessment, he scored 25/30 in English and 22/30 in mathematics.','Not yet provided','English: 25/30; Mathematics: 22/30; self-assessment: 5/10','Not yet provided',1),
    ('beauty-t','Beauty','Beauty','assets/images/f6c988c2-cb7c-46bd-817c-8cef54fa25ac.JPG','Primary Class 6','English, mathematics, and foundational computing','Developing digital literacy and foundational computer skills','Beauty is a Class 6 learner. In the shared assessment, she scored 30/30 in English and 22/30 in mathematics.','Not yet provided','English: 30/30; Mathematics: 22/30; self-assessment: 3/10','Not yet provided',1)
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

COMMIT;
