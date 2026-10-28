-- ==============================================================
-- Database Schema & Initial Seed: File Sharing System
-- High Performance Indexing & Dual Compatible (MySQL & Docker)
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `filesharing_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `filesharing_db`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `avatar_path` VARCHAR(255) NULL DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `storage_quota` BIGINT UNSIGNED NOT NULL DEFAULT 5368709120, -- Default: 5 GB
    `storage_used` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_status_role` (`status`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Folders
CREATE TABLE IF NOT EXISTS `folders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_folders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_parent` (`user_id`, `parent_id`),
    INDEX `idx_folders_user_name` (`user_id`, `name`),
    INDEX `idx_folders_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Files
CREATE TABLE IF NOT EXISTS `files` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `folder_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `storage_filename` VARCHAR(100) NOT NULL,
    `storage_path` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `file_hash` CHAR(64) NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_files_folder` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL,
    INDEX `idx_files_user_folder` (`user_id`, `folder_id`),
    INDEX `idx_files_user_created` (`user_id`, `created_at`),
    INDEX `idx_files_user_name` (`user_id`, `original_name`),
    INDEX `idx_files_hash` (`file_hash`),
    INDEX `idx_files_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Share Links
CREATE TABLE IF NOT EXISTS `share_links` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `share_token` VARCHAR(64) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NULL DEFAULT NULL,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `max_downloads` INT UNSIGNED NULL DEFAULT NULL,
    `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `allow_preview` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_shares_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shares_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`share_token`),
    INDEX `idx_status_expiry` (`is_active`, `expires_at`),
    INDEX `idx_shares_file_active` (`file_id`, `is_active`),
    INDEX `idx_shares_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Download Logs (Audit)
CREATE TABLE IF NOT EXISTS `download_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `share_link_id` BIGINT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_logs_share` FOREIGN KEY (`share_link_id`) REFERENCES `share_links` (`id`) ON DELETE CASCADE,
    INDEX `idx_share_link` (`share_link_id`),
    INDEX `idx_logs_downloaded` (`downloaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel User Shares (Berbagi Antar Pengguna Terdaftar)
CREATE TABLE IF NOT EXISTS `user_shares` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id` BIGINT UNSIGNED NOT NULL,
    `shared_by_user_id` BIGINT UNSIGNED NOT NULL,
    `shared_with_user_id` BIGINT UNSIGNED NOT NULL,
    `permission` ENUM('view', 'download') NOT NULL DEFAULT 'download',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_shares_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_shares_by` FOREIGN KEY (`shared_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_shares_with` FOREIGN KEY (`shared_with_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_file_user` (`file_id`, `shared_with_user_id`),
    INDEX `idx_shared_with` (`shared_with_user_id`),
    INDEX `idx_shared_by` (`shared_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel System Settings (Pengaturan Webhook & Server)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(64) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel Activity Logs (Audit Trail & Log Aktivitas)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `file_id` BIGINT UNSIGNED NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_activity_user_time` (`user_id`, `created_at`),
    INDEX `idx_activity_action` (`action`),
    INDEX `idx_activity_file` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin Account:
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `storage_quota`, `storage_used`, `status`)
VALUES (
    'admin', 
    'admin@fileshare.local', 
    '$2y$12$jZ7GkivHnFE4XhKcIaZpWOph.VRl4DLER1hr7a1QW83.Jj0P0FRO6', 
    'admin', 
    10737418240, -- 10 GB
    0, 
    'active'
)
ON DUPLICATE KEY UPDATE `id` = `id`;
