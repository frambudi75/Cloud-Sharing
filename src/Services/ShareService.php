<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use DateTime;
use PDO;

class ShareService
{
    public static function createShareLink(
        int $fileId,
        int $userId,
        ?string $password = null,
        ?string $expiryDuration = null,
        ?int $maxDownloads = null,
        bool $allowPreview = true
    ): array {
        $db = Database::getConnection();

        // Validasi kepemilikan file
        $stmt = $db->prepare("SELECT id, original_name FROM files WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $fileId, ':user_id' => $userId]);
        $file = $stmt->fetch();

        if (!$file) {
            return ['success' => false, 'error' => 'Berkas tidak ditemukan.'];
        }

        // Generate token kriptografis 32 karakter hex (128-bit)
        $shareToken = bin2hex(random_bytes(16));

        // Password hash jika ada
        $passwordHash = null;
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        // Hitung waktu kedaluwarsa
        $expiresAt = null;
        if (!empty($expiryDuration) && $expiryDuration !== 'never') {
            $now = new DateTime();
            match ($expiryDuration) {
                '1h'    => $now->modify('+1 hour'),
                '24h'   => $now->modify('+24 hours'),
                '7d'    => $now->modify('+7 days'),
                '30d'   => $now->modify('+30 days'),
                default => null,
            };
            if ($expiryDuration !== 'never') {
                $expiresAt = $now->format('Y-m-d H:i:s');
            }
        }

        $insertStmt = $db->prepare("
            INSERT INTO share_links (
                file_id, user_id, share_token, password_hash, 
                expires_at, max_downloads, allow_preview, is_active
            ) VALUES (
                :file_id, :user_id, :share_token, :password_hash, 
                :expires_at, :max_downloads, :allow_preview, 1
            )
        ");

        $insertStmt->execute([
            ':file_id'       => $fileId,
            ':user_id'       => $userId,
            ':share_token'   => $shareToken,
            ':password_hash' => $passwordHash,
            ':expires_at'    => $expiresAt,
            ':max_downloads' => $maxDownloads ?: null,
            ':allow_preview' => $allowPreview ? 1 : 0,
        ]);

        return [
            'success'     => true,
            'share_token' => $shareToken,
            'share_id'    => (int) $db->lastInsertId(),
        ];
    }

    public static function getShareByToken(string $token): ?array
    {
        $share = CacheService::remember("share:{$token}", 300, function () use ($token) {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    s.id as share_id,
                    s.share_token,
                    s.password_hash,
                    s.expires_at,
                    s.max_downloads,
                    s.download_count,
                    s.allow_preview,
                    s.is_active,
                    f.id as file_id,
                    f.original_name,
                    f.storage_filename,
                    f.storage_path,
                    f.mime_type,
                    f.file_size,
                    u.username as owner_username
                FROM share_links s
                INNER JOIN files f ON s.file_id = f.id
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.share_token = :token AND s.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
            $res = $stmt->fetch();
            return $res ?: null;
        });

        if (!$share) {
            return null;
        }

        // Cek kedaluwarsa tanggal
        if (!empty($share['expires_at'])) {
            $expiryTime = strtotime($share['expires_at']);
            if (time() > $expiryTime) {
                return ['expired' => true, 'reason' => 'Tautan ini sudah melewati batas waktu kedaluwarsa.'];
            }
        }

        // Cek batas unduhan
        if ($share['max_downloads'] !== null && (int) $share['download_count'] >= (int) $share['max_downloads']) {
            return ['expired' => true, 'reason' => 'Tautan ini telah mencapai batas maksimum unduhan yang diizinkan.'];
        }

        return $share;
    }

    public static function verifyPassword(array $share, string $password): bool
    {
        if (empty($share['password_hash'])) {
            return true;
        }
        return password_verify($password, $share['password_hash']);
    }

    public static function recordDownload(int $shareId, string $ip, ?string $userAgent): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Tambah counter
            $updateStmt = $db->prepare("UPDATE share_links SET download_count = download_count + 1 WHERE id = :id");
            $updateStmt->execute([':id' => $shareId]);

            // Catat log
            $logStmt = $db->prepare("
                INSERT INTO download_logs (share_link_id, ip_address, user_agent) 
                VALUES (:share_id, :ip, :ua)
            ");
            $logStmt->execute([
                ':share_id' => $shareId,
                ':ip'       => $ip,
                ':ua'       => substr($userAgent ?? '', 0, 500),
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
        }
    }

    public static function revokeShare(int $shareId, int $userId): bool
    {
        $db = Database::getConnection();

        // Cari token untuk invalidasi cache
        $findStmt = $db->prepare("SELECT share_token FROM share_links WHERE id = :id AND user_id = :user_id LIMIT 1");
        $findStmt->execute([':id' => $shareId, ':user_id' => $userId]);
        $row = $findStmt->fetch();

        $stmt = $db->prepare("
            UPDATE share_links 
            SET is_active = 0 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([':id' => $shareId, ':user_id' => $userId]);

        if ($stmt->rowCount() > 0 && !empty($row['share_token'])) {
            CacheService::delete("share:{$row['share_token']}");
            return true;
        }

        return false;
    }
}
