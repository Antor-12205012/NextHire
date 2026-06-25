-- NextHire - Complete Database Schema & Seed Data
-- Designed for MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS `nexthire` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nexthire`;

-- --------------------------------------------------------
-- 1. Table structure for table `companies`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `gemini_api_key` VARCHAR(255) DEFAULT '',
  `openai_api_key` VARCHAR(255) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(191) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'recruiter',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for table `jobs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `required_skills` TEXT NOT NULL,
  `required_experience` INT NOT NULL DEFAULT 0,
  `required_education` VARCHAR(255) NOT NULL,
  `interview_limit` INT NOT NULL DEFAULT 5,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for table `candidates`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT '',
  `resume_text` LONGTEXT NOT NULL,
  `resume_path` VARCHAR(255) DEFAULT '',
  `match_score` INT NOT NULL DEFAULT 0,
  `skills_found` TEXT DEFAULT '',
  `experience_years` INT NOT NULL DEFAULT 0,
  `education_found` VARCHAR(255) DEFAULT '',
  `summary` TEXT DEFAULT '',
  `status` VARCHAR(50) NOT NULL DEFAULT 'applied',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table structure for table `messages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table structure for table `feedbacks`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `feedbacks`;
CREATE TABLE `feedbacks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL,
  `suggestion` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- SEED DATA FOR QUICK START & TESTING
-- ========================================================

-- 1. Insert Sample Company: "Innovations Inc"
INSERT INTO `companies` (`id`, `name`, `gemini_api_key`, `openai_api_key`) 
VALUES (1, 'Innovations Inc', '', '');

-- 2. Insert Recruiter User: "Afsana"
-- Password is pre-hashed: "password123"
INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `password_hash`, `role`) 
VALUES (1, 1, 'Afsana', 'afsana@innovations.com', '$2y$10$hQlhZtI.D3.bX1YcWb51IOPN4lC.y4vVvVp0Uo11/l/Q1hVvVvVv.', 'admin');

-- 3. Insert Job Posting: "PHP Software Engineer"
INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `required_skills`, `required_experience`, `required_education`, `interview_limit`, `status`) 
VALUES (1, 1, 'PHP Software Engineer', 'We are seeking a senior PHP Software Engineer to design, develop, and maintain high-performance web applications. The ideal candidate has experience with MVC structures, robust database designs, and Git version control.', 'PHP, SQL, JavaScript, Git', 2, 'Bachelor', 2, 'open');

-- 4. Insert Job Posting: "React UI Developer"
INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `required_skills`, `required_experience`, `required_education`, `interview_limit`, `status`) 
VALUES (2, 1, 'React UI Developer', 'Join our frontend engineering team to build sleek, interactive, and highly responsive user interfaces. You will collaborate closely with UI/UX designers and work with React, Redux, and modern CSS libraries.', 'React, JavaScript, CSS, Figma', 1, 'Bachelor', 2, 'open');

-- 5. Insert Candidates for PHP Software Engineer (Job 1)
-- Candidate 1: Jane Doe (Strong Match)
INSERT INTO `candidates` (`id`, `job_id`, `name`, `email`, `phone`, `resume_text`, `resume_path`, `match_score`, `skills_found`, `experience_years`, `education_found`, `summary`, `status`) 
VALUES (
  1, 
  1, 
  'Jane Doe', 
  'jane.doe@example.com', 
  '+880-1711-223344', 
  'Jane Doe\nEmail: jane.doe@example.com\nPhone: +880-1711-223344\n\nObjective:\nSenior Software Engineer specializing in backend development with PHP and relational databases.\n\nExperience:\n- 3 years working as a Software Engineer at TechCorp.\n- Developed and maintained multiple PHP web portals.\n- Wrote complex SQL queries, optimized database performance, and integrated Git into deployment workflows.\n\nSkills:\nPHP, SQL, JavaScript, Git, Bootstrap, Tailwind, Laravel.\n\nEducation:\nBachelor of Science in Computer Science & Engineering - Dhaka University (Graduated 2023).', 
  '', 
  100, 
  'PHP, SQL, JavaScript, Git, Bootstrap, Tailwind, Laravel', 
  3, 
  'Bachelor', 
  'Senior Software Engineer specializing in backend development with PHP and relational databases. 3 years working as a Software Engineer at TechCorp. Developed and maintained multiple PHP web portals. Wrote complex SQL queries, optimized database performance, and integrated Git into deployment workflows.', 
  'shortlisted'
);

-- Candidate 2: John Smith (Weak Match)
INSERT INTO `candidates` (`id`, `job_id`, `name`, `email`, `phone`, `resume_text`, `resume_path`, `match_score`, `skills_found`, `experience_years`, `education_found`, `summary`, `status`) 
VALUES (
  2, 
  1, 
  'John Smith', 
  'john.smith@example.com', 
  '+880-1811-556677', 
  'John Smith\nEmail: john.smith@example.com\n\nProfessional Summary:\nWeb Designer and enthusiast with a focus on UI aesthetics, styling, and basic programming.\n\nExperience:\n- 1 year as a Junior Web Designer at CreativeArts Studio.\n- Styled landing pages using HTML, CSS, and basic JavaScript.\n\nSkills:\nHTML, CSS, JavaScript, Photoshop, Illustrator.\n\nEducation:\nHigh School Graduate.', 
  '', 
  22, 
  'JavaScript', 
  1, 
  'High School / Other', 
  'Web Designer and enthusiast with a focus on UI aesthetics, styling, and basic programming. 1 year as a Junior Web Designer at CreativeArts Studio. Styled landing pages using HTML, CSS, and basic JavaScript.', 
  'applied'
);

-- Candidate 3: Dr. Suman Rahman (Moderate Match)
INSERT INTO `candidates` (`id`, `job_id`, `name`, `email`, `phone`, `resume_text`, `resume_path`, `match_score`, `skills_found`, `experience_years`, `education_found`, `summary`, `status`) 
VALUES (
  3, 
  1, 
  'Dr. Suman Rahman', 
  'suman@univ-edu.org', 
  '+880-1911-998800', 
  'Dr. Suman Rahman\nEmail: suman@univ-edu.org\n\nAcademic & Professional Profile:\nResearcher and developer with a focus on databases, programming paradigms, and algorithms.\n\nExperience:\n- 5 years of academic research and teaching.\n- Developed custom database modules using SQL.\n\nSkills:\nSQL, Python, Java, Git.\n\nEducation:\nPhD in Computer Science - BUET (Graduated 2021).', 
  '', 
  55, 
  'SQL, Git', 
  5, 
  'PhD', 
  'Researcher and developer with a focus on databases, programming paradigms, and algorithms. 5 years of academic research and teaching. Developed custom database modules using SQL.', 
  'applied'
);

-- 6. Insert Chat Messages (Job 1 Discussion)
INSERT INTO `messages` (`id`, `job_id`, `user_id`, `message`, `created_at`) 
VALUES (1, 1, 1, 'Jane Doe looks like an exceptional candidate. She matches 100% of our requirements and has 3 years of experience.', '2026-06-25 14:00:00');
INSERT INTO `messages` (`id`, `job_id`, `user_id`, `message`, `created_at`) 
VALUES (2, 1, 1, 'I will schedule Jane Doe for a technical interview next Monday.', '2026-06-25 14:05:00');

-- 7. Insert Sample Feedback
INSERT INTO `feedbacks` (`id`, `user_id`, `rating`, `suggestion`) 
VALUES (1, 1, 5, 'The match score engine is working beautifully! The team chat and smart shortlisting features are highly efficient.');
