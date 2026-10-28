<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\AuthService;
use App\Services\StorageService;
use finfo;
use PDO;

class ProfileController
{
    public function __construct()
    {
        AuthService::startSession();
        if (!AuthService::check()) {
            header('Location: ?action=login');
            exit;
        }
    }

    public function index(): void
    {
        $user = AuthService::user();
        if (!$user) {
            header('Location: ?action=login');
            exit;
        }

        $userId = (int) $user['id'];
        $storageBreakdown = StorageService::getStorageCategoryBreakdown($userId);
        $csrfToken = AuthService::csrfToken();

        // Database stats for profile
        $db = Database::getConnection();
        $filesCount = (int) $db->query("SELECT COUNT(*) FROM files WHERE user_id = {$userId} AND deleted_at IS NULL")->fetchColumn();
        $foldersCount = (int) $db->query("SELECT COUNT(*) FROM folders WHERE user_id = {$userId} AND deleted_at IS NULL")->fetchColumn();
        $sharedCount = (int) $db->query("SELECT COUNT(*) FROM share_links WHERE user_id = {$userId} AND is_active = 1")->fetchColumn();

        $pageTitle = 'Profil Pengguna - Cloud Sharing';
        require_once dirname(__DIR__) . '/Views/profile/index.php';
    }

    public function updateProfile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';

        // 1. Validasi Username
        if (strlen($username) < 3 || strlen($username) > 50) {
            $this->respond(['success' => false, 'error' => 'Nama pengguna harus antara 3 hingga 50 karakter.']);
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $this->respond(['success' => false, 'error' => 'Nama pengguna hanya boleh memuat huruf, angka, titik, strip, dan garis bawah.']);
            return;
        }

        // 2. Validasi Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(['success' => false, 'error' => 'Format alamat email tidak valid.']);
            return;
        }

        $db = Database::getConnection();

        // 3. Cek Uniqueness username dan email
        $checkStmt = $db->prepare("SELECT id, username, email FROM users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1");
        $checkStmt->execute([':u' => $username, ':e' => $email, ':id' => $userId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if (strcasecmp($existing['username'], $username) === 0) {
                $this->respond(['success' => false, 'error' => 'Nama pengguna sudah digunakan oleh akun lain.']);
                return;
            }
            if (strcasecmp($existing['email'], $email) === 0) {
                $this->respond(['success' => false, 'error' => 'Alamat email sudah digunakan oleh akun lain.']);
                return;
            }
        }

        // Current user data
        $currentUser = AuthService::user();
        $avatarPath = $currentUser['avatar_path'] ?? null;
        $avatarDir = StorageService::getAvatarDir();

        // 4. Hapus Avatar jika diminta
        if ($removeAvatar && !empty($avatarPath)) {
            $oldFile = $avatarDir . DIRECTORY_SEPARATOR . $avatarPath;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            $avatarPath = null;
        }

        // 5. Upload Avatar Baru jika ada
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $maxSize = 5 * 1024 * 1024; // 5 MB

            if ($file['size'] > $maxSize) {
                $this->respond(['success' => false, 'error' => 'Ukuran foto profil maksimal 5 MB.']);
                return;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];

            if (!isset($allowedMimes[$mimeType])) {
                $this->respond(['success' => false, 'error' => 'Format foto profil harus JPG, PNG, WEBP, atau GIF.']);
                return;
            }

            $ext = $allowedMimes[$mimeType];
            $newAvatarFilename = sprintf('avatar_%d_%d_%s.%s', $userId, time(), bin2hex(random_bytes(6)), $ext);
            $targetPath = $avatarDir . DIRECTORY_SEPARATOR . $newAvatarFilename;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->respond(['success' => false, 'error' => 'Gagal menyimpan foto profil ke storage.']);
                return;
            }

            // Hapus file avatar lama jika ada
            if (!empty($currentUser['avatar_path'])) {
                $oldFile = $avatarDir . DIRECTORY_SEPARATOR . $currentUser['avatar_path'];
                if (file_exists($oldFile) && is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $avatarPath = $newAvatarFilename;
        }

        // 6. Simpan perubahan ke Database
        $updateStmt = $db->prepare("
            UPDATE users 
            SET username = :u, email = :e, avatar_path = :av, updated_at = NOW() 
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':u'  => $username,
            ':e'  => $email,
            ':av' => $avatarPath,
            ':id' => $userId
        ]);

        // Perbarui session
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['avatar_path'] = $avatarPath;

        $avatarUrl = $avatarPath ? "?action=avatar&user_id={$userId}&t=" . time() : null;

        $this->respond([
            'success'    => true,
            'message'    => 'Profil Anda berhasil diperbarui.',
            'username'   => $username,
            'email'      => $email,
            'avatar_url' => $avatarUrl
        ], '?action=profile&status=profile_updated');
    }

    public function updatePassword(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();

        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($currentPassword)) {
            $this->respond(['success' => false, 'error' => 'Silakan masukkan kata sandi Anda saat ini.']);
            return;
        }

        if (strlen($newPassword) < 6) {
            $this->respond(['success' => false, 'error' => 'Kata sandi baru minimal harus 6 karakter.']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->respond(['success' => false, 'error' => 'Konfirmasi kata sandi baru tidak sesuai.']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $this->respond(['success' => false, 'error' => 'Kata sandi saat ini tidak cocok.']);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

        $this->respond([
            'success' => true,
            'message' => 'Kata sandi Anda berhasil diperbarui.'
        ]);
    }

    public function getAvatar(): void
    {
        $userId = (int) ($_GET['user_id'] ?? AuthService::id() ?? 0);
        if ($userId <= 0) {
            $this->renderDefaultAvatarSvg('U');
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT username, avatar_path FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || empty($user['avatar_path'])) {
            $this->renderDefaultAvatarSvg($user['username'] ?? 'U');
            return;
        }

        $avatarFile = StorageService::getAvatarDir() . DIRECTORY_SEPARATOR . $user['avatar_path'];
        if (!file_exists($avatarFile) || !is_file($avatarFile)) {
            $this->renderDefaultAvatarSvg($user['username'] ?? 'U');
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($avatarFile) ?: 'image/jpeg';

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($avatarFile));
        header('Cache-Control: public, max-age=86400, must-revalidate');
        readfile($avatarFile);
        exit;
    }

    private function renderDefaultAvatarSvg(string $name): void
    {
        $initial = strtoupper(substr(trim($name), 0, 1) ?: 'U');
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400');
        echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#4f46e5" />
            <stop offset="100%" stop-color="#7c3aed" />
        </linearGradient>
    </defs>
    <rect width="128" height="128" rx="64" fill="url(#grad)" />
    <text x="50%" y="54%" font-family="system-ui, -apple-system, sans-serif" font-size="52" font-weight="bold" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">{$initial}</text>
</svg>
SVG;
        exit;
    }

    private function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!AuthService::verifyCsrf($token)) {
            $this->respond(['success' => false, 'error' => 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.']);
            exit;
        }
    }

    private function respond(array $data, ?string $redirectUrl = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
