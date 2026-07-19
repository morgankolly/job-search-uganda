-- ============================================================
-- Migration 001 – JobSearch Project Overview additions
-- Run this on an EXISTING job_search database (one that was
-- already created from the original schema.sql).
-- For a FRESH install, use database/schema.sql instead.
-- ============================================================

USE `job_search`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Alter: users  →  add email verification, status, employer type
-- ------------------------------------------------------------
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `email_verified_at` TIMESTAMP    DEFAULT NULL             AFTER `google_id`,
    ADD COLUMN IF NOT EXISTS `status`            ENUM('active','suspended','pending_verification')
                                                              NOT NULL DEFAULT 'active' AFTER `email_verified_at`,
    ADD COLUMN IF NOT EXISTS `employer_type`     ENUM('registered','none')
                                                              NOT NULL DEFAULT 'none'   AFTER `status`;

-- Existing admin users are considered verified and active.
UPDATE `users` SET `status` = 'active', `email_verified_at` = `created_at`
WHERE `email_verified_at` IS NULL;

-- ------------------------------------------------------------
-- Alter: applications  →  add applicant detail fields + reference
-- ------------------------------------------------------------
ALTER TABLE `applications`
    ADD COLUMN IF NOT EXISTS `application_reference` VARCHAR(50)  UNIQUE DEFAULT NULL AFTER `application_id`,
    ADD COLUMN IF NOT EXISTS `education`             TEXT                DEFAULT NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `experience`            TEXT                DEFAULT NULL AFTER `education`,
    ADD COLUMN IF NOT EXISTS `skills`                TEXT                DEFAULT NULL AFTER `experience`;

-- ------------------------------------------------------------
-- New: employer_profiles  – company info for registered employers
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employer_profiles` (
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
-- New: subscription_plans
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_plans` (
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

INSERT IGNORE INTO `subscription_plans`
    (`plan_id`, `plan_name`, `plan_type`, `price`, `currency`, `duration_days`, `description`) VALUES
    (1, 'Monthly Plan', 'monthly', 50000.00, 'UGX', 30,
     'Full dashboard access for 30 days. Post unlimited jobs, manage applicants, download CVs.'),
    (2, 'Annual Plan',  'annual',  500000.00, 'UGX', 365,
     'Full dashboard access for 365 days. Save 17% vs monthly. Post unlimited jobs, manage applicants, download CVs.');

-- ------------------------------------------------------------
-- New: subscriptions  – employer subscription records
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
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
-- New: payments  – subscriptions AND pay-per-post fees
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `payment_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `payment_reference` VARCHAR(50)  NOT NULL UNIQUE,
    `payment_type`      ENUM('subscription','pay_per_post') NOT NULL,
    `payer_type`        ENUM('employer','guest')            NOT NULL,
    `user_id`           INT          DEFAULT NULL,
    `guest_job_id`      INT          DEFAULT NULL,
    `subscription_id`   INT          DEFAULT NULL,
    `amount`            DECIMAL(10,2) NOT NULL,
    `currency`          VARCHAR(10)  NOT NULL DEFAULT 'UGX',
    `payment_method`    ENUM('mtn_mobile_money','airtel_money','visa_mastercard') NOT NULL,
    `payer_phone`       VARCHAR(50)  DEFAULT NULL,
    `payer_name`        VARCHAR(150) DEFAULT NULL,
    `transaction_id`    VARCHAR(100) DEFAULT NULL,
    `status`            ENUM('pending','verified','failed','refunded') NOT NULL DEFAULT 'pending',
    `notes`             TEXT         DEFAULT NULL,
    `verified_by`       INT          DEFAULT NULL,
    `verified_at`       TIMESTAMP    DEFAULT NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pay_user`  FOREIGN KEY (`user_id`)        REFERENCES `users`        (`user_id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_pay_vby`   FOREIGN KEY (`verified_by`)    REFERENCES `users`        (`user_id`)        ON DELETE SET NULL,
    CONSTRAINT `fk_pay_sub`   FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`subscription_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- New: password_resets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
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
-- New: email_verifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_verifications` (
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
-- New: system_config  – platform settings (fees, name, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_config` (
    `config_key`   VARCHAR(100) PRIMARY KEY,
    `config_value` VARCHAR(500) NOT NULL,
    `description`  VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_config` (`config_key`, `config_value`, `description`) VALUES
    ('pay_per_post_fee',      '20000',             'One-time fee (UGX) for a guest job post'),
    ('pay_per_post_currency', 'UGX',               'Currency for pay-per-post fee'),
    ('platform_name',         'Job Search Uganda', 'Platform display name'),
    ('support_email',         'support@jobsearch.ug', 'Platform support email'),
    ('support_phone',         '+256700000000',     'Platform support phone');

SET FOREIGN_KEY_CHECKS = 1;
