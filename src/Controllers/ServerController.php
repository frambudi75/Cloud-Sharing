<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\CacheService;
use App\Services\SettingService;
use App\Services\StorageService;
use App\Services\WebhookService;
use Throwable;

class ServerController
{
    public function __construct()
    {
        AuthService::requireAdmin();
    }

    public function index(): void
    {
        $db = Database::getConnection();
        $uploadDir = StorageService::getUploadDir();

        // Disk Information
        $diskTotal = @disk_total_space($uploadDir);
        $diskFree = @disk_free_space($uploadDir);
        $diskUsed = ($diskTotal !== false && $diskFree !== false) ? ($diskTotal - $diskFree) : 0;
        $diskPercent = ($diskTotal !== false && $diskTotal > 0) ? round(($diskUsed / $diskTotal) * 100, 1) : 0.0;

        // DB stats
        $dbVersion = 'Unknown';
        $totalFiles = 0;
        $totalUsers = 0;
        $totalUserStorage = 0;
        try {
            $dbVersion = (string) $db->query("SELECT VERSION()")->fetchColumn();
            $totalFiles = (int) $db->query("SELECT COUNT(*) FROM files WHERE deleted_at IS NULL")->fetchColumn();
            $totalUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $totalUserStorage = (int) $db->query("SELECT COALESCE(SUM(storage_used), 0) FROM users")->fetchColumn();
        } catch (Throwable) {}

        // Redis cache status
        $redisActive = CacheService::isAvailable();

        // Server Load & Memory Metrics
        $loadAndMem = self::getServerLoadAndMemory();

        // Temporary Chunk Stats
        $chunkStats = self::getChunkDirectoryStats();

        // Webhook settings & deliveries
        $webhookEnabled = SettingService::get('webhook_enabled', '0') === '1';
        $webhookUrl = (string) (SettingService::get('webhook_url', '') ?? '');
        $savedEvents = json_decode(SettingService::get('webhook_events', '["upload","download","login"]') ?? '[]', true);
        if (!is_array($savedEvents)) {
            $savedEvents = ['upload', 'download', 'login'];
        }
        $webhookDeliveries = WebhookService::getRecentDeliveries(8);

        // Global System Settings
        $globalSettings = [
            'default_user_quota_gb' => (float) SettingService::get('default_user_quota_gb', '5'),
            'default_share_expiry'  => (string) SettingService::get('default_share_expiry', 'never'),
            'maintenance_mode'      => SettingService::get('maintenance_mode', '0') === '1',
            'maintenance_message'   => (string) SettingService::get('maintenance_message', 'Sistem Cloud Sharing sedang dalam proses pemeliharaan rutin. Silakan coba kembali dalam beberapa saat.'),
        ];

        // Global Activity Logs (Latest 20)
        $globalActivityLogs = ActivityLogService::getGlobalLogs(20, 0);
        $globalActivityTotal = ActivityLogService::countGlobalLogs();

        // Metrics package
        $metrics = [
            'disk_total'          => $diskTotal !== false ? $diskTotal : 0,
            'disk_free'           => $diskFree !== false ? $diskFree : 0,
            'disk_used'           => $diskUsed,
            'disk_percent'        => $diskPercent,
            'upload_dir'          => $uploadDir,
            'upload_dir_writable' => is_writable($uploadDir),
            'php_version'         => PHP_VERSION,
            'php_os'              => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            'server_software'     => $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx / PHP-FPM',
            'hostname'            => gethostname() ?: 'server',
            'memory_limit'        => ini_get('memory_limit') ?: 'N/A',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'N/A',
            'post_max_size'       => ini_get('post_max_size') ?: 'N/A',
            'max_execution_time'  => (ini_get('max_execution_time') ?: '0') . 's',
            'db_version'          => $dbVersion,
            'total_files'         => $totalFiles,
            'total_users'         => $totalUsers,
            'total_user_storage'  => $totalUserStorage,
            'redis_active'        => $redisActive,
            'cpu_load'            => $loadAndMem['cpu_load'],
            'ram_total'           => $loadAndMem['ram_total'],
            'ram_used'            => $loadAndMem['ram_used'],
            'ram_free'            => $loadAndMem['ram_free'],
            'ram_percent'         => $loadAndMem['ram_percent'],
            'uptime'              => $loadAndMem['uptime'],
            'chunk_dirs'          => $chunkStats['count'],
            'chunk_bytes'         => $chunkStats['bytes'],
        ];

        $currentUser = AuthService::user();
        $csrfToken = AuthService::csrfToken();

        require_once dirname(__DIR__) . '/Views/admin/server.php';
    }

    public function saveSystemSettings(): void
    {
        $this->validateCsrf();

        $defaultQuota = max(0.1, (float) ($_POST['default_user_quota_gb'] ?? 5));
        $defaultExpiry = trim($_POST['default_share_expiry'] ?? 'never');
        if (!in_array($defaultExpiry, ['never', '1d', '7d', '30d'], true)) {
            $defaultExpiry = 'never';
        }

        $maintenanceMode = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1' ? '1' : '0';
        $maintenanceMessage = trim($_POST['maintenance_message'] ?? '');
        if (empty($maintenanceMessage)) {
            $maintenanceMessage = 'Sistem Cloud Sharing sedang dalam pemeliharaan rutin. Harap tunggu beberapa saat.';
        }

        SettingService::set('default_user_quota_gb', (string) $defaultQuota);
        SettingService::set('default_share_expiry', $defaultExpiry);
        SettingService::set('maintenance_mode', $maintenanceMode);
        SettingService::set('maintenance_message', $maintenanceMessage);

        $admin = AuthService::user();
        ActivityLogService::log(
            (int) ($admin['id'] ?? 0),
            'system_settings',
            'Pengaturan Global Diperbarui',
            "Default Kuota: {$defaultQuota} GB | Expiry: {$defaultExpiry} | Maintenance: " . ($maintenanceMode === '1' ? 'ON' : 'OFF')
        );

        $this->respondJson([
            'success'          => true,
            'message'          => 'Pengaturan global aplikasi berhasil disimpan.',
            'maintenance_mode' => $maintenanceMode === '1'
        ]);
    }

    public function cleanChunks(): void
    {
        $this->validateCsrf();

        // Bersihkan seluruh chunk sementara yang berumur > 0 detik (seluruh orphan)
        $res = StorageService::purgeChunks(0);

        $admin = AuthService::user();
        ActivityLogService::log(
            (int) ($admin['id'] ?? 0),
            'system_cleanup',
            'Pembersihan Chunk Unggahan',
            "Dihapus: {$res['deleted_dirs']} direktori chunk | Dibebaskan: " . StorageService::formatBytes($res['freed_bytes'])
        );

        $this->respondJson([
            'success'      => true,
            'deleted_dirs' => $res['deleted_dirs'],
            'freed_bytes'  => $res['freed_bytes'],
            'freed_str'    => StorageService::formatBytes($res['freed_bytes']),
            'message'      => "Berhasil membersihkan {$res['deleted_dirs']} folder chunk terbengkalai (" . StorageService::formatBytes($res['freed_bytes']) . " ruang disk dibebaskan)."
        ]);
    }

    public function purgeCache(): void
    {
        $this->validateCsrf();

        $cleared = CacheService::flush();

        $admin = AuthService::user();
        ActivityLogService::log(
            (int) ($admin['id'] ?? 0),
            'cache_purge',
            'Pembersihan Cache Redis',
            $cleared ? 'Seluruh cache Redis berhasil dibersihkan' : 'Redis fallback / cache dibersihkan'
        );

        $this->respondJson([
            'success' => true,
            'message' => $cleared 
                ? 'Seluruh cache Redis (fileshare:*) berhasil dibersihkan.'
                : 'Cache in-memory telah disinkronkan ulang.'
        ]);
    }

    public function optimizeDatabase(): void
    {
        $this->validateCsrf();

        try {
            $db = Database::getConnection();
            $tables = ['users', 'folders', 'files', 'share_links', 'download_logs', 'user_shares', 'system_settings', 'activity_logs'];
            $optimized = [];

            foreach ($tables as $t) {
                $db->query("OPTIMIZE TABLE `{$t}`");
                $optimized[] = $t;
            }

            $admin = AuthService::user();
            ActivityLogService::log(
                (int) ($admin['id'] ?? 0),
                'db_optimize',
                'Optimasi Tabel Database',
                count($optimized) . ' tabel MariaDB dioptimalkan'
            );

            $this->respondJson([
                'success' => true,
                'tables'  => $optimized,
                'message' => 'Berhasil mengoptimalkan dan mendefrag ' . count($optimized) . ' tabel database MariaDB.'
            ]);
        } catch (\Throwable $e) {
            $this->respondJson(['success' => false, 'error' => 'Gagal mengoptimalkan database: ' . $e->getMessage()]);
        }
    }

    public function benchmarkDisk(): void
    {
        $this->validateCsrf();

        try {
            $uploadDir = StorageService::getUploadDir();
            if (!is_writable($uploadDir)) {
                $this->respondJson(['success' => false, 'error' => 'Direktori penyimpanan tidak dapat ditulis (Read-Only).']);
                return;
            }

            $testMb = 10;
            $chunk = str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ\n", 16508); // ~1MB
            $benchPath = $uploadDir . DIRECTORY_SEPARATOR . 'bench_' . bin2hex(random_bytes(6)) . '.tmp';

            // 1. Write Benchmark
            $startWrite = microtime(true);
            $fp = fopen($benchPath, 'wb');
            if (!$fp) {
                $this->respondJson(['success' => false, 'error' => 'Gagal membuka berkas pengujian disk.']);
                return;
            }

            for ($i = 0; $i < $testMb; $i++) {
                fwrite($fp, $chunk);
            }
            fflush($fp);
            fclose($fp);
            $writeSec = max(0.0001, microtime(true) - $startWrite);
            $writeSpeed = round($testMb / $writeSec, 1);

            // 2. Read Benchmark
            $startRead = microtime(true);
            $fp = fopen($benchPath, 'rb');
            if ($fp) {
                while (!feof($fp)) {
                    fread($fp, 1024 * 1024);
                }
                fclose($fp);
            }
            $readSec = max(0.0001, microtime(true) - $startRead);
            $readSpeed = round($testMb / $readSec, 1);

            @unlink($benchPath);

            $this->respondJson([
                'success'       => true,
                'write_speed'   => $writeSpeed . ' MB/s',
                'read_speed'    => $readSpeed . ' MB/s',
                'test_size'     => "{$testMb} MB",
                'total_time_ms' => (int) round(($writeSec + $readSec) * 1000)
            ]);
        } catch (\Throwable $e) {
            $this->respondJson(['success' => false, 'error' => 'Uji benchmark gagal: ' . $e->getMessage()]);
        }
    }

    public function getGlobalLogs(): void
    {
        $filterAction = trim($_GET['action_type'] ?? 'all');
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $logs = ActivityLogService::getGlobalLogs($limit, $offset, $filterAction, $search);
        $total = ActivityLogService::countGlobalLogs($filterAction, $search);

        // Map badges and human time
        $formatted = array_map(function ($log) {
            $badge = ActivityLogService::getActionBadge($log['action']);
            $dev = ActivityLogService::parseDevice($log['user_agent']);
            return [
                'id'         => $log['id'],
                'username'   => $log['username'] ?? 'User #' . $log['user_id'],
                'role'       => $log['role'] ?? 'user',
                'action'     => $log['action'],
                'badge'      => $badge,
                'file_name'  => $log['file_name'],
                'details'    => $log['details'],
                'ip_address' => $log['ip_address'],
                'device'     => $dev['device'] . ' • ' . $dev['browser'],
                'created_at' => $log['created_at'],
                'time_ago'   => ActivityLogService::formatTimeAgo($log['created_at']),
            ];
        }, $logs);

        $this->respondJson([
            'success' => true,
            'logs'    => $formatted,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $limit)
        ]);
    }

    public function saveWebhook(): void
    {
        $this->validateCsrf();

        $enabled = isset($_POST['webhook_enabled']) && $_POST['webhook_enabled'] === '1' ? '1' : '0';
        $url = trim($_POST['webhook_url'] ?? '');
        $events = $_POST['events'] ?? [];

        if (!is_array($events)) {
            $events = [];
        }

        // Filter valid events only
        $allowedEvents = ['upload', 'download', 'login'];
        $cleanEvents = array_values(array_intersect($events, $allowedEvents));

        if ($enabled === '1' && !empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->respondJson(['success' => false, 'error' => 'URL Webhook tidak valid. Pastikan diawali https://']);
            return;
        }

        $s1 = SettingService::set('webhook_enabled', $enabled);
        $s2 = SettingService::set('webhook_url', $url);
        $s3 = SettingService::set('webhook_events', json_encode($cleanEvents));

        if (!$s1 || !$s2 || !$s3) {
            $this->respondJson(['success' => false, 'error' => 'Gagal menyimpan pengaturan ke database.']);
            return;
        }

        $this->respondJson(['success' => true, 'message' => 'Pengaturan webhook berhasil disimpan.']);
    }

    public function testWebhook(): void
    {
        $this->validateCsrf();

        $url = trim($_POST['webhook_url'] ?? '');
        if (empty($url)) {
            $url = trim((string) SettingService::get('webhook_url', ''));
        }

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->respondJson(['success' => false, 'error' => 'URL Webhook kosong atau tidak valid. Masukkan URL lalu coba lagi.']);
            return;
        }

        $admin = AuthService::user();
        $testPayload = [
            'username'   => 'Cloud Sharing Bot',
            'content'    => "🔔 **[test]** Tes Koneksi Webhook Cloud Sharing Berhasil!\nWebhook ini siap menerima notifikasi aktivitas upload, download, dan login user.",
            'text'       => "🔔 [test] Tes Koneksi Webhook Cloud Sharing Berhasil!",
            'event'      => 'test',
            'title'      => 'Tes Koneksi Webhook',
            'timestamp'  => date('c'),
            'embeds'     => [
                [
                    'title'       => '🔔 Tes Koneksi Webhook Berhasil',
                    'description' => 'Konfigurasi webhook di Cloud Sharing berhasil terhubung dengan endpoint ini.',
                    'color'       => 0x10b981, // Emerald Green
                    'fields'      => [
                        [
                            'name'   => 'Pengirim Tes',
                            'value'  => $admin['username'] ?? 'Admin',
                            'inline' => true,
                        ],
                        [
                            'name'   => 'Host Server',
                            'value'  => gethostname() ?: 'server',
                            'inline' => true,
                        ],
                        [
                            'name'   => 'Waktu',
                            'value'  => date('d M Y H:i:s'),
                            'inline' => true,
                        ],
                    ],
                    'footer'      => [
                        'text' => 'Cloud Sharing Drive System',
                    ],
                    'timestamp'   => date('c'),
                ]
            ],
        ];

        $success = WebhookService::sendPost($url, $testPayload);

        if ($success) {
            $this->respondJson(['success' => true, 'message' => 'Tes webhook berhasil dikirim! Silakan periksa channel Anda.']);
        } else {
            $this->respondJson(['success' => false, 'error' => 'Gagal mengirim webhook. Pastikan URL endpoint valid dan dapat menerima POST request.']);
        }
    }

    public static function getServerLoadAndMemory(): array
    {
        // CPU Load Average
        $cpuLoad = ['1m' => 0.0, '5m' => 0.0, '15m' => 0.0];
        if (function_exists('sys_getloadavg')) {
            $loads = @sys_getloadavg();
            if (is_array($loads) && count($loads) >= 3) {
                $cpuLoad = [
                    '1m'  => round((float) $loads[0], 2),
                    '5m'  => round((float) $loads[1], 2),
                    '15m' => round((float) $loads[2], 2),
                ];
            }
        }

        // RAM Memory Info
        $memTotal = 0;
        $memAvailable = 0;

        if (is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo') ?: '';
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $m1)) {
                $memTotal = (int) $m1[1] * 1024;
            }
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $m2)) {
                $memAvailable = (int) $m2[1] * 1024;
            } elseif (preg_match('/MemFree:\s+(\d+)\s+kB/i', $meminfo, $m3)) {
                $memAvailable = (int) $m3[1] * 1024;
            }
        }

        if ($memTotal <= 0) {
            // Fallback for Windows or limited environments
            $memUsed = memory_get_usage(true);
            $memLimitStr = ini_get('memory_limit') ?: '256M';
            $memLimitBytes = 256 * 1024 * 1024;
            if (preg_match('/^(\d+)(M|G)$/i', $memLimitStr, $lim)) {
                $val = (int) $lim[1];
                $unit = strtoupper($lim[2]);
                $memLimitBytes = $unit === 'G' ? $val * 1024 * 1024 * 1024 : $val * 1024 * 1024;
            }
            $memTotal = $memLimitBytes;
            $memAvailable = max(0, $memTotal - $memUsed);
        }

        $memUsed = max(0, $memTotal - $memAvailable);
        $memPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0.0;

        // Uptime
        $uptimeStr = 'Online';
        if (is_readable('/proc/uptime')) {
            $rawUptime = @file_get_contents('/proc/uptime');
            if ($rawUptime) {
                $uptimeSec = (int) explode(' ', trim($rawUptime))[0];
                $days = floor($uptimeSec / 86400);
                $hours = floor(($uptimeSec % 86400) / 3600);
                $minutes = floor(($uptimeSec % 3600) / 60);
                $uptimeStr = ($days > 0 ? "{$days}d " : '') . "{$hours}h {$minutes}m";
            }
        }

        return [
            'cpu_load'    => $cpuLoad,
            'ram_total'   => $memTotal,
            'ram_used'    => $memUsed,
            'ram_free'    => $memAvailable,
            'ram_percent' => $memPercent,
            'uptime'      => $uptimeStr,
        ];
    }

    public static function getChunkDirectoryStats(): array
    {
        $count = 0;
        $bytes = 0;
        try {
            $chunkDir = StorageService::getChunkDir();
            if (is_dir($chunkDir)) {
                $items = scandir($chunkDir);
                if ($items) {
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $itemPath = $chunkDir . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($itemPath)) {
                            $count++;
                            $files = scandir($itemPath);
                            if ($files) {
                                foreach ($files as $f) {
                                    if ($f !== '.' && $f !== '..') {
                                        $bytes += (int) @filesize($itemPath . DIRECTORY_SEPARATOR . $f);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable) {}

        return ['count' => $count, 'bytes' => $bytes];
    }

    private function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!AuthService::verifyCsrf($token)) {
            $this->respondJson(['success' => false, 'error' => 'Sesi keamanan tidak valid. Silakan muat ulang halaman.']);
            exit;
        }
    }

    private function respondJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
