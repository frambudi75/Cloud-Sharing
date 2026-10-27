<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DownloadService;
use App\Services\ShareService;
use App\Services\StorageService;

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

        // Handle POST password unlock
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_password'])) {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!AuthService::verifyCsrf($csrf)) {
                $passwordError = 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.';
            } else {
                $submittedPassword = (string) $_POST['unlock_password'];
                if (ShareService::verifyPassword($share, $submittedPassword)) {
                    $_SESSION["unlocked_share_{$token}"] = true;
                    $isUnlocked = true;
                } else {
                    $passwordError = 'Kata sandi salah. Silakan coba kembali.';
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

        // Catat download counter dan audit log
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        ShareService::recordDownload((int) $share['share_id'], $ip, $ua, $token);

        $fullPath = StorageService::getPhysicalPath($share['storage_path'], $share['storage_filename']);
        $forceDownload = !isset($_GET['preview']) || (int) $_GET['preview'] !== 1;

        DownloadService::stream($fullPath, $share['original_name'], $share['mime_type'], $forceDownload);
    }

    private function renderError(string $title, string $message): void
    {
        require_once dirname(__DIR__) . '/Views/guest/error.php';
        exit;
    }
}
