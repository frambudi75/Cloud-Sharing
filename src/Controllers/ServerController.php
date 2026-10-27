<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
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

        // Webhook settings
        $webhookEnabled = SettingService::get('webhook_enabled', '0') === '1';
        $webhookUrl = (string) (SettingService::get('webhook_url', '') ?? '');
        $savedEvents = json_decode(SettingService::get('webhook_events', '["upload","download","login"]') ?? '[]', true);
        if (!is_array($savedEvents)) {
            $savedEvents = ['upload', 'download', 'login'];
        }

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
        ];

        $currentUser = AuthService::user();
        $csrfToken = AuthService::csrfToken();

        require_once dirname(__DIR__) . '/Views/admin/server.php';
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
