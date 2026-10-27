<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CacheService;

class AuthController
{
    public function showLogin(?string $error = null): void
    {
        if (AuthService::check()) {
            header('Location: ?action=drive');
            exit;
        }

        $csrfToken = AuthService::csrfToken();
        require_once dirname(__DIR__) . '/Views/auth/login.php';
    }

    public function handleLogin(): void
    {
        if (!AuthService::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $this->showLogin('Sesi keamanan tidak valid. Silakan muat ulang halaman.');
            return;
        }

        $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateKey = 'login_rate:' . md5($clientIp);
        $attempts = (int) (CacheService::get($rateKey) ?? 0);

        if ($attempts >= 5) {
            $this->showLogin('Terlalu banyak percobaan masuk yang gagal. Akses dibatasi selama 15 menit.');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->showLogin('Silakan isi nama pengguna dan kata sandi.');
            return;
        }

        $result = AuthService::login($username, $password);
        if ($result['success']) {
            CacheService::delete($rateKey);
            \App\Services\WebhookService::trigger('login', 'Pengguna Masuk ke Sistem', "Pengguna `{$username}` berhasil masuk ke Cloud Drive.", [
                'Pengguna'   => $username,
                'IP Address' => $clientIp,
                'Waktu'      => date('d M Y, H:i:s'),
            ]);
            header('Location: ?action=drive');
            exit;
        }

        CacheService::set($rateKey, $attempts + 1, 900); // 15 menit
        $this->showLogin($result['error']);
    }

    public function handleLogout(): void
    {
        AuthService::logout();
        header('Location: ?action=login');
        exit;
    }
}
