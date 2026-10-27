<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class SettingService
{
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();

            if ($val !== false && $val !== null) {
                self::$cache[$key] = (string) $val;
                return self::$cache[$key];
            }
        } catch (\Throwable $e) {
            // Fail gracefully jika tabel belum di-create
        }

        return $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::$cache[$key] = $value;
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (:k, :v) 
                ON DUPLICATE KEY UPDATE setting_value = :v_update
            ");
            $stmt->execute([
                ':k' => $key,
                ':v' => $value,
                ':v_update' => $value,
            ]);
        } catch (\Throwable $e) {
            // Log or ignore
        }
    }

    public static function getAll(): array
    {
        $settings = [];
        try {
            $db = Database::getConnection();
            $rows = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
                self::$cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (\Throwable $e) {
        }
        return $settings;
    }
}
