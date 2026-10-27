<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class AuthService
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_only_cookies', '1');
            session_start();
        }
    }

    public static function login(string $usernameOrEmail, string $password): array
    {
        self::startSession();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, username, email, password_hash, role, storage_quota, storage_used, status 
            FROM users 
            WHERE username = :username OR email = :email 
            LIMIT 1
        ");
        $stmt->execute([
            ':username' => $usernameOrEmail,
            ':email'    => $usernameOrEmail,
        ]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Kombinasi pengguna dan kata sandi tidak ditemukan.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Akun Anda sedang dinonaktifkan. Hubungi admin.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Kombinasi pengguna dan kata sandi tidak ditemukan.'];
        }

        // Rehash password jika algoritma perlu diperbarui
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $updateStmt->execute([':hash' => $newHash, ':id' => $user['id']]);
        }

        // Set session
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return ['success' => true, 'error' => null];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, username, email, role, storage_quota, storage_used, status, created_at 
            FROM users 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function id(): ?int
    {
        self::startSession();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::startSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            header('Location: ?action=login');
            exit;
        }

        if (!self::isAdmin()) {
            http_response_code(403);
            echo "Akses ditolak. Fitur ini khusus untuk Administrator.";
            exit;
        }
    }
}
