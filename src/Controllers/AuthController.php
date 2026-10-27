<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

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

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->showLogin('Silakan isi nama pengguna dan kata sandi.');
            return;
        }

        $result = AuthService::login($username, $password);
        if ($result['success']) {
            header('Location: ?action=drive');
            exit;
        }

        $this->showLogin($result['error']);
    }

    public function handleLogout(): void
    {
        AuthService::logout();
        header('Location: ?action=login');
        exit;
    }
}
