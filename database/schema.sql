-- ============================================================
-- schema.sql
-- Student-Job Recommendation and Skill Gap Analysis
-- Database: student_job_portal (MariaDB 10.4 / XAMPP)
-- Safe to re-run: there are no DROP statements, so existing
-- data is never erased.
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_job_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE student_job_portal;

-- ------------------------------------------------------------
-- 1. education_levels: reference table with an explicit rank
--    (Any=0, Diploma=1, Bachelor=2, Master=3 - filled by seed.sql)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS education_levels (
  level_id   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  level_name VARCHAR(30)      NOT NULL,
  rank_order TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (level_id),
  UNIQUE KEY uq_level_name (level_name),
  UNIQUE KEY uq_rank_order (rank_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. users: one login account for every role
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','employer','admin') NOT NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. student_profiles (1 : 1 with users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_profiles (
  student_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED NOT NULL,
  phone              VARCHAR(20)  NULL,
  university         VARCHAR(150) NULL,
  department         VARCHAR(100) NULL,
  graduation_year    YEAR         NULL,
  bio                TEXT         NULL,
  preferred_location VARCHAR(100) NULL,
  preferred_job_type ENUM('Full-time','Part-time','Internship','Remote') NULL,
  preferred_role     VARCHAR(100) NULL,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_student_user (user_id),
  CONSTRAINT fk_student_profiles_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. employer_profiles (1 : 1 with users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employer_profiles (
  employer_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  company_name VARCHAR(150) NOT NULL,
  industry     VARCHAR(100) NULL,
  website      VARCHAR(200) NULL,
  location     VARCHAR(100) NULL,
  description  TEXT         NULL,
  PRIMARY KEY (employer_id),
  UNIQUE KEY uq_employer_user (user_id),
  CONSTRAINT fk_employer_profiles_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. skills: master skill list (admin retires skills, never deletes)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
  skill_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  skill_name VARCHAR(80)  NOT NULL,
  category   VARCHAR(50)  NULL,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (skill_id),
  UNIQUE KEY uq_skill_name (skill_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. student_skills (many students <-> many skills)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_skills (
  student_id  INT UNSIGNED NOT NULL,
  skill_id    INT UNSIGNED NOT NULL,
  proficiency ENUM('Beginner','Intermediate','Advanced')
              NOT NULL DEFAULT 'Beginner',
  PRIMARY KEY (student_id, skill_id),
  CONSTRAINT fk_student_skills_student
    FOREIGN KEY (student_id) REFERENCES student_profiles (student_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_student_skills_skill
    FOREIGN KEY (skill_id) REFERENCES skills (skill_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. education (status: a degree in progress is 'Ongoing')
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS education (
  education_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id     INT UNSIGNED NOT NULL,
  level_id       TINYINT UNSIGNED NOT NULL,
  status         ENUM('Completed','Ongoing') NOT NULL DEFAULT 'Completed',
  institution    VARCHAR(150) NOT NULL,
  field_of_study VARCHAR(100) NULL,
  start_year     YEAR         NULL,
  end_year       YEAR         NULL,
  cgpa           DECIMAL(3,2) NULL,
  PRIMARY KEY (education_id),
  CONSTRAINT fk_education_student
    FOREIGN KEY (student_id) REFERENCES student_profiles (student_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_education_level
    FOREIGN KEY (level_id) REFERENCES education_levels (level_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. experience (end_date NULL = still ongoing)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS experience (
  experience_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  type          ENUM('Job','Internship','Project') NOT NULL,
  title         VARCHAR(150) NOT NULL,
  organization  VARCHAR(150) NULL,
  start_date    DATE         NOT NULL,
  end_date      DATE         NULL,
  description   TEXT         NULL,
  PRIMARY KEY (experience_id),
  CONSTRAINT fk_experience_student
    FOREIGN KEY (student_id) REFERENCES student_profiles (student_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. experience_skills: skills used in each experience entry.
--    This is what makes an experience "relevant" to a job.
--    The ONLY cascading delete in the project: deleting one
--    experience entry also removes its skill links.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS experience_skills (
  experience_id INT UNSIGNED NOT NULL,
  skill_id      INT UNSIGNED NOT NULL,
  PRIMARY KEY (experience_id, skill_id),
  CONSTRAINT fk_experience_skills_experience
    FOREIGN KEY (experience_id) REFERENCES experience (experience_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_experience_skills_skill
    FOREIGN KEY (skill_id) REFERENCES skills (skill_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. jobs (closed/removed jobs are kept, never deleted)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
  job_id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employer_id              INT UNSIGNED NOT NULL,
  title                    VARCHAR(150) NOT NULL,
  description              TEXT         NOT NULL,
  location                 VARCHAR(100) NULL,
  job_type                 ENUM('Full-time','Part-time','Internship','Remote') NOT NULL,
  min_education_level_id   TINYINT UNSIGNED NOT NULL,
  accepts_ongoing_students TINYINT(1)   NOT NULL DEFAULT 0,
  min_experience_months    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  salary_min               INT UNSIGNED NULL,
  salary_max               INT UNSIGNED NULL,
  deadline                 DATE         NOT NULL,
  status                   ENUM('open','closed','removed') NOT NULL DEFAULT 'open',
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  KEY idx_jobs_status_deadline (status, deadline),
  CONSTRAINT fk_jobs_employer
    FOREIGN KEY (employer_id) REFERENCES employer_profiles (employer_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_jobs_min_education
    FOREIGN KEY (min_education_level_id) REFERENCES education_levels (level_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. job_skills (skills each job requires, with a required level)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_skills (
  job_id         INT UNSIGNED NOT NULL,
  skill_id       INT UNSIGNED NOT NULL,
  required_level ENUM('Beginner','Intermediate','Advanced')
                 NOT NULL DEFAULT 'Beginner',
  PRIMARY KEY (job_id, skill_id),
  CONSTRAINT fk_job_skills_job
    FOREIGN KEY (job_id) REFERENCES jobs (job_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_job_skills_skill
    FOREIGN KEY (skill_id) REFERENCES skills (skill_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. applications: application history is always preserved
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
  application_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id            INT UNSIGNED NOT NULL,
  student_id        INT UNSIGNED NOT NULL,
  status            ENUM('Applied','Shortlisted','Rejected','Selected')
                    NOT NULL DEFAULT 'Applied',
  cover_note        TEXT NULL,
  match_percent     TINYINT UNSIGNED NULL,
  applied_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status_updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  UNIQUE KEY uq_application (job_id, student_id),
  CONSTRAINT fk_applications_job
    FOREIGN KEY (job_id) REFERENCES jobs (job_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_applications_student
    FOREIGN KEY (student_id) REFERENCES student_profiles (student_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. saved_jobs (used by the optional Saved Jobs module)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS saved_jobs (
  student_id INT UNSIGNED NOT NULL,
  job_id     INT UNSIGNED NOT NULL,
  saved_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id, job_id),
  CONSTRAINT fk_saved_jobs_student
    FOREIGN KEY (student_id) REFERENCES student_profiles (student_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_saved_jobs_job
    FOREIGN KEY (job_id) REFERENCES jobs (job_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;