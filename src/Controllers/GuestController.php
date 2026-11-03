<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CacheService;
use App\Services\DownloadService;
use App\Services\ShareService;
use App\Services\StorageService;
use App\Services\WebhookService;

class GuestController
{
    public function viewShare(): void
    {
        AuthService::startSession();
        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            $this->renderError('Tautan Berbagi Tidak Valid', 'Parameter token berbagi tidak ditemukan.');
            return;
        }

        $share = ShareService::getShareByToken($token);

        if (!$share) {
            $this->renderError('Tautan Tidak Ditemukan', 'Tautan ini mungkin telah dihapus atau dinonaktifkan oleh pemiliknya.');
            return;
        }

        if (isset($share['expired'])) {
            $this->renderError('Tautan Kedaluwarsa', $share['reason']);
            return;
        }

        $isPasswordRequired = !empty($share['password_hash']);
        $isUnlocked = false;

        // Cek apakah user sudah menginput password sebelumnya di sesi ini
        if ($isPasswordRequired) {
            $unlockedKey = "unlocked_share_{$token}";
            if (!empty($_SESSION[$unlockedKey])) {
                $isUnlocked = true;
            }
        } else {
            $isUnlocked = true;
        }

        $passwordError = null;

        // Handle POST password unlock dengan proteksi anti brute-force
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_password'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $rateKey = 'share_pass_rate:' . md5($clientIp . '_' . $token);
            $attempts = (int) (CacheService::get($rateKey) ?? 0);

            if ($attempts >= 5) {
                $passwordError = 'Terlalu banyak percobaan kata sandi yang salah. Akses dibatasi selama 15 menit.';
            } else {
                $csrf = $_POST['csrf_token'] ?? null;
                if (!AuthService::verifyCsrf($csrf)) {
                    $passwordError = 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.';
                } else {
                    $submittedPassword = (string) $_POST['unlock_password'];
                    if (ShareService::verifyPassword($share, $submittedPassword)) {
                        CacheService::delete($rateKey);
                        $_SESSION["unlocked_share_{$token}"] = true;
                        $isUnlocked = true;
                    } else {
                        CacheService::set($rateKey, $attempts + 1, 900); // 15 menit
                        $remaining = 5 - ($attempts + 1);
                        $passwordError = $remaining > 0 
                            ? "Kata sandi salah. Sisa {$remaining} kesempatan percobaan." 
                            : 'Terlalu banyak percobaan kata sandi yang salah. Akses dibatasi selama 15 menit.';
                    }
                }
            }
        }

        $csrfToken = AuthService::csrfToken();

        require_once dirname(__DIR__) . '/Views/guest/download.php';
    }

    public function downloadShare(): void
    {
        AuthService::startSession();
        $token = trim($_GET['token'] ?? '');
        $share = ShareService::getShareByToken($token);

        if (!$share || isset($share['expired'])) {
            http_response_code(404);
            echo "Tautan berkas tidak tersedia.";
            exit;
        }

        // Cek proteksi password
        if (!empty($share['password_hash'])) {
            $unlockedKey = "unlocked_share_{$token}";
            if (empty($_SESSION[$unlockedKey])) {
                header("Location: ?action=share&token={$token}");
                exit;
            }
        }

        $fullPath = StorageService::getPhysicalPath($share['storage_path'], $share['storage_filename']);
        $forceDownload = !isset($_GET['preview']) || (int) $_GET['preview'] !== 1;

        // Catat download counter dan webhook HANYA jika benar-benar diunduh (bukan pratinjau) dan bukan range continuation
        $isRangeContinuation = isset($_SERVER['HTTP_RANGE']) && !str_starts_with($_SERVER['HTTP_RANGE'], 'bytes=0-');
        if ($forceDownload && !$isRangeContinuation) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            ShareService::recordDownload((int) $share['share_id'], $ip, $ua, $token);

            WebhookService::trigger(
                'download',
                'Berkas Diunduh (Tautan Tamu)',
                "Tamu publik mengunduh berkas **{$share['original_name']}**.",
                [
                    'Berkas' => $share['original_name'],
                    'Ukuran' => StorageService::formatBytes((int) $share['file_size']),
                    'Tipe'   => 'Tautan Tamu Publik',
                    'IP'     => $ip,
                ]
            );
        }

        DownloadService::stream($fullPath, $share['original_name'], $share['mime_type'], $forceDownload);
    }

    private function renderError(string $title, string $message): void
    {
        require_once dirname(__DIR__) . '/Views/guest/error.php';
        exit;
    }
}
