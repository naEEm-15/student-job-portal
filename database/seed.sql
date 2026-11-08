-- ============================================================
-- seed.sql
-- Reference data only: education levels and a starter skill list.
-- No users, no passwords, no admin account.
-- Safe to run more than once: INSERT IGNORE skips rows that already
-- exist, so nothing is duplicated.
-- ============================================================

USE student_job_portal;

-- Education levels with an explicit rank (higher rank = higher level)
INSERT IGNORE INTO education_levels (level_id, level_name, rank_order) VALUES
  (1, 'Any',      0),
  (2, 'Diploma',  1),
  (3, 'Bachelor', 2),
  (4, 'Master',   3);

-- Starter skills (the admin can add, edit or retire skills later)
INSERT IGNORE INTO skills (skill_name, category) VALUES
  ('PHP',               'Programming'),
  ('JavaScript',        'Programming'),
  ('Python',            'Programming'),
  ('Java',              'Programming'),
  ('C',                 'Programming'),
  ('C++',               'Programming'),
  ('C#',                'Programming'),
  ('MySQL',             'Database'),
  ('MongoDB',           'Database'),
  ('PostgreSQL',        'Database'),
  ('SQL',               'Database'),
  ('HTML',              'Web Development'),
  ('CSS',               'Web Development'),
  ('Bootstrap',         'Web Development'),
  ('React',             'Web Development'),
  ('Node.js',           'Web Development'),
  ('Laravel',           'Web Development'),
  ('Git',               'Tools'),
  ('GitHub',            'Tools'),
  ('Docker',            'Tools'),
  ('Linux',             'Tools'),
  ('Data Analysis',     'Data'),
  ('Machine Learning',  'Data'),
  ('Excel',             'Data'),
  ('Power BI',          'Data'),
  ('Statistics',        'Data'),
  ('Communication',     'Soft Skills'),
  ('Teamwork',          'Soft Skills'),
  ('Problem Solving',   'Soft Skills'),
  ('Time Management',   'Soft Skills');