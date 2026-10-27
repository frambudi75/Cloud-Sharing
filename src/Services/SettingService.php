<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;
use Throwable;

class SettingService
{
    private static array $cache = [];
    private static bool $tableEnsured = false;

    private static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        try {
            $db = Database::getConnection();
            $db->exec("
                CREATE TABLE IF NOT EXISTS `system_settings` (
                    `setting_key` VARCHAR(64) PRIMARY KEY,
                    `setting_value` TEXT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            self::$tableEnsured = true;
        } catch (Throwable $e) {
            // Fail gracefully
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        self::ensureTable();

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();

            if ($val !== false && $val !== null) {
                self::$cache[$key] = (string) $val;
                return self::$cache[$key];
            }
        } catch (Throwable $e) {
            // Table might still be initializing
        }

        return $default;
    }

    public static function set(string $key, ?string $value): bool
    {
        self::$cache[$key] = $value;
        self::ensureTable();

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (:k, :v) 
                ON DUPLICATE KEY UPDATE setting_value = :v_update
            ");
            return $stmt->execute([
                ':k' => $key,
                ':v' => $value,
                ':v_update' => $value,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function getAll(): array
    {
        self::ensureTable();
        $settings = [];
        try {
            $db = Database::getConnection();
            $rows = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
                self::$cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
        }
        return $settings;
    }
}
