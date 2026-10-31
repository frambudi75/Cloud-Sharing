<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\AuthService;
use App\Services\StorageService;

class UserController
{
    public function __construct()
    {
        AuthService::requireAdmin();
    }

    public function index(): void
    {
        $db = Database::getConnection();
        $search = trim($_GET['q'] ?? '');

        if (!empty($search)) {
            $stmt = $db->prepare("
                SELECT id, username, email, role, storage_quota, storage_used, status, created_at 
                FROM users 
                WHERE username LIKE :q OR email LIKE :q 
                ORDER BY created_at DESC
            ");
            $stmt->execute([':q' => "%{$search}%"]);
        } else {
            $stmt = $db->query("
                SELECT id, username, email, role, storage_quota, storage_used, status, created_at 
                FROM users 
                ORDER BY created_at DESC
            ");
        }

        $users = $stmt->fetchAll();
        $currentUser = AuthService::user();
        $csrfToken = AuthService::csrfToken();

        require_once dirname(__DIR__) . '/Views/admin/users.php';
    }

    public function createUser(): void
    {
        $this->validateCsrf();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $defaultQuota = (float) SettingService::get('default_user_quota_gb', '5');
        $quotaGb = !empty($_POST['quota_gb']) ? (float) $_POST['quota_gb'] : $defaultQuota;

        if (empty($username) || empty($email) || empty($password)) {
            $this->respondJson(['success' => false, 'error' => 'Semua kolom wajib diisi.']);
            return;
        }

        if (strlen($password) < 6) {
            $this->respondJson(['success' => false, 'error' => 'Kata sandi minimal 6 karakter.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respondJson(['success' => false, 'error' => 'Format email tidak valid.']);
            return;
        }

        if (!in_array($role, ['admin', 'user'], true)) {
            $role = 'user';
        }

        $quotaBytes = (int) ($quotaGb * 1024 * 1024 * 1024);
        if ($quotaBytes <= 0) {
            $quotaBytes = 5368709120; // 5 GB default
        }

        $db = Database::getConnection();

        // Cek duplikasi username / email
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $checkStmt->execute([':username' => $username, ':email' => $email]);
        if ($checkStmt->fetch()) {
            $this->respondJson(['success' => false, 'error' => 'Nama pengguna atau email sudah terdaftar.']);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, role, storage_quota, storage_used, status) 
            VALUES (:username, :email, :password_hash, :role, :storage_quota, 0, 'active')
        ");
        $insertStmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':role'          => $role,
            ':storage_quota' => $quotaBytes,
        ]);

        $this->respondJson(['success' => true]);
    }

    public function updateUser(): void
    {
        $this->validateCsrf();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $status = trim($_POST['status'] ?? 'active');
        $quotaGb = (float) ($_POST['quota_gb'] ?? 5);

        if ($userId <= 0 || empty($email)) {
            $this->respondJson(['success' => false, 'error' => 'Data pengguna tidak valid.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respondJson(['success' => false, 'error' => 'Format email tidak valid.']);
            return;
        }

        if (!in_array($role, ['admin', 'user'], true)) {
            $role = 'user';
        }

        if (!in_array($status, ['active', 'suspended'], true)) {
            $status = 'active';
        }

        // Mencegah admin mengubah role / menonaktifkan akunnya sendiri
        $currentAdminId = (int) AuthService::id();
        if ($userId === $currentAdminId) {
            $role = 'admin';
            $status = 'active';
        }

        $quotaBytes = (int) ($quotaGb * 1024 * 1024 * 1024);
        if ($quotaBytes <= 0) {
            $quotaBytes = 5368709120; // 5 GB
        }

        $db = Database::getConnection();

        // Cek duplikasi email pada user lain
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $checkStmt->execute([':email' => $email, ':id' => $userId]);
        if ($checkStmt->fetch()) {
            $this->respondJson(['success' => false, 'error' => 'Email ini sudah digunakan oleh akun lain.']);
            return;
        }

        $updateStmt = $db->prepare("
            UPDATE users 
            SET email = :email, role = :role, status = :status, storage_quota = :storage_quota 
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':email'         => $email,
            ':role'          => $role,
            ':status'        => $status,
            ':storage_quota' => $quotaBytes,
            ':id'            => $userId,
        ]);

        $this->respondJson(['success' => true]);
    }

    public function resetPassword(): void
    {
        $this->validateCsrf();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($userId <= 0 || strlen($newPassword) < 6) {
            $this->respondJson(['success' => false, 'error' => 'Kata sandi baru minimal 6 karakter.']);
            return;
        }

        $db = Database::getConnection();
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute([':hash' => $newHash, ':id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function deleteUser(): void
    {
        $this->validateCsrf();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $currentAdminId = (int) AuthService::id();

        if ($userId <= 0) {
            $this->respondJson(['success' => false, 'error' => 'ID pengguna tidak valid.']);
            return;
        }

        if ($userId === $currentAdminId) {
            $this->respondJson(['success' => false, 'error' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.']);
            return;
        }

        $db = Database::getConnection();

        // 1. Dapatkan semua file fisik milik user untuk dihapus dari disk
        $filesStmt = $db->prepare("SELECT storage_path, storage_filename FROM files WHERE user_id = :id");
        $filesStmt->execute([':id' => $userId]);
        $files = $filesStmt->fetchAll();

        foreach ($files as $file) {
            $fullPath = StorageService::getPhysicalPath($file['storage_path'], $file['storage_filename']);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // 2. Hapus user dari DB (Foreign key cascade akan menghapus folders, files, dan shares)
        $avStmt = $db->prepare("SELECT avatar_path FROM users WHERE id = :id LIMIT 1");
        $avStmt->execute([':id' => $userId]);
        $avRow = $avStmt->fetch();
        if (!empty($avRow['avatar_path'])) {
            $avFile = StorageService::getAvatarDir() . DIRECTORY_SEPARATOR . $avRow['avatar_path'];
            if (file_exists($avFile) && is_file($avFile)) {
                @unlink($avFile);
            }
        }
        $deleteStmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $deleteStmt->execute([':id' => $userId]);

        $this->respondJson(['success' => true]);
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
