<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static ?string $lastError = null;
    private static bool $schemaVerified = false;

    /**
     * Mendapatkan koneksi singleton PDO dengan kemampuan self-healing / auto-repair
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = (int) Env::get('DB_PORT', 3306);
            $dbName = Env::get('DB_DATABASE', 'filesharing_db');
            $username = Env::get('DB_USERNAME', 'root');
            $password = Env::get('DB_PASSWORD', '');

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Jika database belum dibuat (SQLSTATE[HY000] [1049] Unknown database), auto-create database
                if ($e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    try {
                        $rawDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                        $rawPdo = new PDO($rawDsn, $username, $password, $options);
                        $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Hubungkan kembali ke database yang baru dibuat
                        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                        self::$instance = new PDO($dsn, $username, $password, $options);
                        
                        // Segera jalankan perbaikan / pembuatan skema tabel
                        self::repairSchema();
                    } catch (PDOException $createEx) {
                        self::$lastError = $createEx->getMessage();
                        throw $createEx;
                    }
                } else {
                    self::$lastError = $e->getMessage();
                    throw $e;
                }
            }

            // Lakukan verifikasi skema tabel otomatis jika belum diverifikasi
            if (!self::$schemaVerified) {
                self::ensureSchema();
            }
        }

        return self::$instance;
    }

    /**
     * Memastikan seluruh tabel yang dibutuhkan sistem tersedia
     */
    public static function ensureSchema(bool $force = false): array
    {
        if (self::$schemaVerified && !$force) {
            return ['status' => true, 'repaired' => false, 'message' => 'Skema sudah diverifikasi sebelumnya'];
        }

        try {
            $pdo = self::$instance;
            if (!$pdo) {
                $pdo = self::getConnection();
            }

            // Cek keberadaan tabel inti
            $requiredTables = ['users', 'folders', 'files', 'share_links', 'download_logs', 'user_shares'];
            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $missingTables = array_diff($requiredTables, $existingTables);
            if (!empty($missingTables) || $force) {
                return self::repairSchema();
            }

            // Pastikan kolom avatar_path ada di tabel users (self-healing migration)
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar_path'");
                if ($colsStmt && $colsStmt->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER email");
                }
            } catch (\Throwable $ignored) {
            }

            // Pastikan kolom deleted_at ada di tabel files dan folders untuk recycle bin
            try {
                $colsFiles = $pdo->query("SHOW COLUMNS FROM files LIKE 'deleted_at'");
                if ($colsFiles && $colsFiles->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE files ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL, ADD INDEX idx_files_deleted (deleted_at)");
                }
                $colsFolders = $pdo->query("SHOW COLUMNS FROM folders LIKE 'deleted_at'");
                if ($colsFolders && $colsFolders->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE folders ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL, ADD INDEX idx_folders_deleted (deleted_at)");
                }
            } catch (\Throwable $ignored) {
            }

            self::$schemaVerified = true;
            return ['status' => true, 'repaired' => false, 'message' => 'Seluruh tabel lengkap'];
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            return ['status' => false, 'repaired' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Memperbaiki dan membuat seluruh tabel, indeks, dan akun default jika hilang
     */
    public static function repairSchema(): array
    {
        try {
            $pdo = self::$instance;
            if (!$pdo) {
                $host = Env::get('DB_HOST', '127.0.0.1');
                $port = (int) Env::get('DB_PORT', 3306);
                $dbName = Env::get('DB_DATABASE', 'filesharing_db');
                $username = Env::get('DB_USERNAME', 'root');
                $password = Env::get('DB_PASSWORD', '');

                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance = $pdo;
            }

            // 1. Tabel users
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `email` VARCHAR(100) NOT NULL UNIQUE,
                    `avatar_path` VARCHAR(255) NULL DEFAULT NULL,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                    `storage_quota` BIGINT UNSIGNED NOT NULL DEFAULT 5368709120,
                    `storage_used` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_users_status_role` (`status`, `role`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar_path'");
                if ($colsStmt && $colsStmt->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER email");
                }
            } catch (\Throwable $ignored) {
            }

            // 2. Tabel folders
            $pdo->exec("
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
            ");

            // 3. Tabel files
            $pdo->exec("
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
            ");

            // 4. Tabel share_links
            $pdo->exec("
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
            ");

            // 5. Tabel download_logs
            $pdo->exec("
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
            ");

            // 6. Tabel user_shares
            $pdo->exec("
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
            ");

            // 7. Tabel system_settings (Pengaturan Webhook & Server)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `system_settings` (
                    `setting_key` VARCHAR(64) PRIMARY KEY,
                    `setting_value` TEXT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed Akun Admin Default jika belum ada admin sama sekali
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();
            if ($adminCount === 0) {
                $defaultPassHash = password_hash('admin123', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `storage_quota`, `storage_used`, `status`)
                    VALUES ('admin', 'admin@fileshare.local', :hash, 'admin', 10737418240, 0, 'active')
                    ON DUPLICATE KEY UPDATE `role` = 'admin'
                ");
                $stmt->execute([':hash' => $defaultPassHash]);
            }

            self::$schemaVerified = true;
            return [
                'status' => true,
                'repaired' => true,
                'message' => 'Skema database dan seluruh tabel berhasil diperiksa serta diperbaiki secara otomatis.'
            ];
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            return [
                'status' => false,
                'repaired' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function checkConnection(): array
    {
        try {
            self::getConnection();
            $schemaResult = self::ensureSchema();
            return [
                'status' => true, 
                'message' => 'Terhubung ke database.',
                'schema' => $schemaResult
            ];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }
}
