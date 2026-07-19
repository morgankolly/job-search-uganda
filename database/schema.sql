-- ============================================================
-- Job Search System - Database Schema
-- Target: MySQL / MariaDB (XAMPP)
--
-- Usage:
--   mysql -u root -e "CREATE DATABASE IF NOT EXISTS job_search
--        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
--   mysql -u root job_search < database/schema.sql
--
-- The DB name (job_search) matches .env -> DB_DATABASE.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `job_search`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `job_search`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Roles
-- ------------------------------------------------------------
CREATE TABLE `roles` (
    `role_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
    (1, 'admin'),
    (2, 'employer'),
    (3, 'agent');

-- ------------------------------------------------------------
-- Users (admins / employers)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `user_name`  VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `profile`    VARCHAR(255) DEFAULT 'default.png',
    `role_id`    INT NOT NULL DEFAULT 2,
    `google_id`          VARCHAR(100) DEFAULT NULL,
    `email_verified_at`  TIMESTAMP    DEFAULT NULL,
    `status`             ENUM('active','suspended','pending_verification') NOT NULL DEFAULT 'active',
    `employer_type`      ENUM('registered','none') NOT NULL DEFAULT 'none',
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed admin account.
-- Email:    admin@jobsearch.test
-- Password: Admin@123   (bcrypt hash below)
INSERT INTO `users` (`user_name`, `email`, `password`, `profile`, `role_id`) VALUES
    ('Administrator', 'admin@jobsearch.test',
     '$2y$10$ZKqCjnkNNUjOl0WY/p/f4.ggontA08QEn5eoOcf3paWPV0WnCbzFC',
     'default.png', 1);

-- ------------------------------------------------------------
-- Job categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `job_categories`;
CREATE TABLE `job_categories` (
    `category_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `job_categories` (`category_name`) VALUES
    ('Information Technology'),
    ('Sales & Marketing'),
    ('Finance & Accounting'),
    ('Healthcare'),
    ('Education'),
    ('Engineering'),
    ('Customer Service'),
    ('Administration');

-- ------------------------------------------------------------
-- Job types
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `job_types`;
CREATE TABLE `job_types` (
    `type_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `type_name` VARCHAR(50) NOT NULL UNIQUE,
    `status`    ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `job_types` (`type_name`, `status`) VALUES
    ('Full-time', 'active'),
    ('Part-time', 'active'),
    ('Contract',  'active'),
    ('Internship','active'),
    ('Remote',    'active');

-- ------------------------------------------------------------
-- Jobs (posted by registered employers)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
    `job_id`               INT AUTO_INCREMENT PRIMARY KEY,
    `employer_id`          INT DEFAULT NULL,
    `job_reference`        VARCHAR(50) UNIQUE DEFAULT NULL,
    `job_title`            VARCHAR(150) NOT NULL,
    `company_name`         VARCHAR(150) DEFAULT NULL,
    `job_category`         INT DEFAULT NULL,
    `location`             VARCHAR(150) DEFAULT NULL,
    `salary`               VARCHAR(100) DEFAULT NULL,
    `job_type`             INT DEFAULT NULL,
    `description`          TEXT,
    `requirements`         TEXT,
    `deadline`             DATE DEFAULT NULL,
    `max_applications`     INT NOT NULL DEFAULT 50,
    `current_applications` INT NOT NULL DEFAULT 0,
    `status`               ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_jobs_employer`
        FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_jobs_category`
        FOREIGN KEY (`job_category`) REFERENCES `job_categories` (`category_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_jobs_type`
        FOREIGN KEY (`job_type`) REFERENCES `job_types` (`type_id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Guest jobs (posted without registration -> need admin approval)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `guest_jobs`;
CREATE TABLE `guest_jobs` (
    `job_id`               INT AUTO_INCREMENT PRIMARY KEY,
    `job_reference`        VARCHAR(50) UNIQUE DEFAULT NULL,
    `company_name`         VARCHAR(150) NOT NULL,
    `contact_person`       VARCHAR(150) NOT NULL,
    `email`                VARCHAR(150) NOT NULL,
    `phone`                VARCHAR(50)  NOT NULL,
    `company_logo`         VARCHAR(255) DEFAULT NULL,
    `category_id`          INT DEFAULT NULL,
    `job_title`            VARCHAR(150) NOT NULL,
    `job_type`             INT DEFAULT NULL,
    `location`             VARCHAR(150) DEFAULT NULL,
    `salary`               VARCHAR(100) DEFAULT NULL,
    `description`          TEXT,
    `requirements`         TEXT,
    `deadline`             DATE DEFAULT NULL,
    `max_applications`     INT NOT NULL DEFAULT 50,
    `current_applications` INT NOT NULL DEFAULT 0,
    `payment_status`       ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    `status`               ENUM('pending','approved','rejected','closed') NOT NULL DEFAULT 'pending',
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_guest_jobs_category`
        FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`category_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_guest_jobs_type`
        FOREIGN KEY (`job_type`) REFERENCES `job_types` (`type_id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Applications (full fields per Project Overview spec)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
    `application_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `application_reference` VARCHAR(50)  UNIQUE DEFAULT NULL,
    `job_id`                INT          NOT NULL,
    `job_source`            ENUM('jobs','guest') NOT NULL DEFAULT 'jobs',
    `applicant_name`        VARCHAR(150) NOT NULL,
    `email`                 VARCHAR(150) NOT NULL,
    `phone`                 VARCHAR(50)  DEFAULT NULL,
    `education`             TEXT         DEFAULT NULL,
    `experience`            TEXT         DEFAULT NULL,
    `skills`                TEXT         DEFAULT NULL,
    `cover_letter`          TEXT,
    `cv_path`               VARCHAR(255) DEFAULT NULL,
    `status`                ENUM('pending','reviewed','shortlisted','accepted','rejected') NOT NULL DEFAULT 'pending',
    `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_applications_job` (`job_source`, `job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Contact messages
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `contact`;
CREATE TABLE `contact` (
    `contact_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `contact_name` VARCHAR(150) NOT NULL,
    `email`        VARCHAR(150) NOT NULL,
    `message`      TEXT NOT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Employer profiles (company info for registered employers)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `employer_profiles`;
CREATE TABLE `employer_profiles` (
    `profile_id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT          NOT NULL UNIQUE,
    `company_name`        VARCHAR(200) NOT NULL,
    `company_logo`        VARCHAR(255) DEFAULT NULL,
    `company_description` TEXT,
    `industry`            VARCHAR(100) DEFAULT NULL,
    `website`             VARCHAR(255) DEFAULT NULL,
    `phone`               VARCHAR(50)  DEFAULT NULL,
    `address`             VARCHAR(255) DEFAULT NULL,
    `city`                VARCHAR(100) DEFAULT NULL,
    `country`             VARCHAR(100) NOT NULL DEFAULT 'Uganda',
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ep_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Subscription plans
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE `subscription_plans` (
    `plan_id`       INT AUTO_INCREMENT PRIMARY KEY,
    `plan_name`     VARCHAR(100) NOT NULL,
    `plan_type`     ENUM('monthly','annual') NOT NULL,
    `price`         DECIMAL(10,2) NOT NULL,
    `currency`      VARCHAR(10)  NOT NULL DEFAULT 'UGX',
    `duration_days` INT          NOT NULL,
    `description`   TEXT,
    `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscription_plans`
    (`plan_id`, `plan_name`, `plan_type`, `price`, `currency`, `duration_days`, `description`) VALUES
    (1, 'Monthly Plan', 'monthly', 50000.00, 'UGX', 30,
     'Full dashboard access for 30 days. Post unlimited jobs, manage applicants, download CVs.'),
    (2, 'Annual Plan',  'annual',  500000.00, 'UGX', 365,
     'Full dashboard access for 365 days. Save 17% vs monthly. Post unlimited jobs, manage applicants, download CVs.');

-- ------------------------------------------------------------
-- Subscriptions (employer subscription records)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
    `subscription_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT  NOT NULL,
    `plan_id`         INT  NOT NULL,
    `start_date`      DATE DEFAULT NULL,
    `end_date`        DATE DEFAULT NULL,
    `status`          ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`)  REFERENCES `users`             (`user_id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`)  REFERENCES `subscription_plans`(`plan_id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Payments (subscriptions AND pay-per-post fees)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `payment_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `payment_reference` VARCHAR(50)   NOT NULL UNIQUE,
    `payment_type`      ENUM('subscription','pay_per_post') NOT NULL,
    `payer_type`        ENUM('employer','guest')            NOT NULL,
    `user_id`           INT           DEFAULT NULL,
    `guest_job_id`      INT           DEFAULT NULL,
    `subscription_id`   INT           DEFAULT NULL,
    `amount`            DECIMAL(10,2) NOT NULL,
    `currency`          VARCHAR(10)   NOT NULL DEFAULT 'UGX',
    `payment_method`    ENUM('mtn_mobile_money','airtel_money','visa_mastercard') NOT NULL,
    `payer_phone`       VARCHAR(50)   DEFAULT NULL,
    `payer_name`        VARCHAR(150)  DEFAULT NULL,
    `transaction_id`    VARCHAR(100)  DEFAULT NULL,
    `status`            ENUM('pending','verified','failed','refunded') NOT NULL DEFAULT 'pending',
    `notes`             TEXT          DEFAULT NULL,
    `verified_by`       INT           DEFAULT NULL,
    `verified_at`       TIMESTAMP     DEFAULT NULL,
    `created_at`        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`)         REFERENCES `users`         (`user_id`)         ON DELETE SET NULL,
    CONSTRAINT `fk_pay_vby`  FOREIGN KEY (`verified_by`)     REFERENCES `users`         (`user_id`)         ON DELETE SET NULL,
    CONSTRAINT `fk_pay_sub`  FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`subscription_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Password resets
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `reset_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `email`      VARCHAR(150) NOT NULL,
    `token`      VARCHAR(100) NOT NULL,
    `expires_at` TIMESTAMP    NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pr_token` (`token`),
    INDEX `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Email verifications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
    `verification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT          NOT NULL,
    `token`           VARCHAR(100) NOT NULL,
    `expires_at`      TIMESTAMP    NOT NULL,
    `verified_at`     TIMESTAMP    DEFAULT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    INDEX `idx_ev_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- System config (platform settings, fees)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
    `config_key`   VARCHAR(100) PRIMARY KEY,
    `config_value` VARCHAR(500) NOT NULL,
    `description`  VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_config` (`config_key`, `config_value`, `description`) VALUES
    ('pay_per_post_fee',      '20000',             'One-time fee (UGX) for a single guest job post'),
    ('pay_per_post_currency', 'UGX',               'Currency for pay-per-post fee'),
    ('platform_name',         'Job Search Uganda', 'Platform display name'),
    ('support_email',         'support@jobsearch.ug', 'Platform support email'),
    ('support_phone',         '+256700000000',     'Platform support phone');

SET FOREIGN_KEY_CHECKS = 1;
