<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Config\Env;
use finfo;
use PDO;
use RuntimeException;

class StorageService
{
    private static ?string $resolvedUploadDir = null;

    public static function getUploadDir(): string
    {
        if (self::$resolvedUploadDir !== null) {
            return self::$resolvedUploadDir;
        }

        $configPath = Env::get('STORAGE_PATH', 'storage/uploads');

        // Jika path relatif, hubungkan dengan root direktori aplikasi
        if (!str_starts_with($configPath, '/') && !preg_match('~^[a-zA-Z]:[\\\\/]~', $configPath)) {
            $baseDir = dirname(__DIR__, 2);
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $configPath);
        } else {
            $fullPath = $configPath;
        }

        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                throw new RuntimeException("Gagal membuat direktori penyimpanan: {$fullPath}");
            }
        }

        self::$resolvedUploadDir = realpath($fullPath) ?: $fullPath;
        return self::$resolvedUploadDir;
    }

    public static function storeUploadedFile(array $uploadedFile, int $userId, ?int $folderId = null): array
    {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::getUploadErrorMessage($uploadedFile['error'])];
        }

        $fileSize = (int) $uploadedFile['size'];
        $originalName = basename($uploadedFile['name']);
        $tmpPath = $uploadedFile['tmp_name'];

        // Cek Kuota Pengguna
        $user = AuthService::user();
        if ($user && ($user['storage_used'] + $fileSize) > $user['storage_quota']) {
            return ['success' => false, 'error' => 'Kapasitas penyimpanan Anda tidak mencukupi untuk berkas ini.'];
        }

        // Deteksi MIME Type asli menggunakan finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath) ?: 'application/octet-stream';

        // Hitung SHA256 checksum
        $fileHash = hash_file('sha256', $tmpPath) ?: null;

        // Generate nama berkas penyimpanan acak (UUID/Hex)
        $storageFilename = bin2hex(random_bytes(16)) . '.bin';

        // Simpan dalam subfolder berbasis tahun/bulan untuk menjaga performa filesystem
        $subDir = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $uploadBaseDir = self::getUploadDir();
        $targetDirectory = $uploadBaseDir . DIRECTORY_SEPARATOR . $subDir;

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $targetFullPath = $targetDirectory . DIRECTORY_SEPARATOR . $storageFilename;
        $relativeStoragePath = 'uploads/' . date('Y') . '/' . date('m') . '/';

        if (!move_uploaded_file($tmpPath, $targetFullPath)) {
            return ['success' => false, 'error' => 'Gagal memindahkan berkas yang diunggah ke storage.'];
        }

        // Simpan ke Database
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO files (
                    user_id, folder_id, original_name, storage_filename, 
                    storage_path, mime_type, file_size, file_hash
                ) VALUES (
                    :user_id, :folder_id, :original_name, :storage_filename, 
                    :storage_path, :mime_type, :file_size, :file_hash
                )
            ");

            $stmt->execute([
                ':user_id'          => $userId,
                ':folder_id'        => $folderId ?: null,
                ':original_name'    => $originalName,
                ':storage_filename' => $storageFilename,
                ':storage_path'     => $relativeStoragePath,
                ':mime_type'        => $mimeType,
                ':file_size'        => $fileSize,
                ':file_hash'        => $fileHash,
            ]);

            $fileId = (int) $db->lastInsertId();

            // Update kuota terpakai user
            $updateQuotaStmt = $db->prepare("
                UPDATE users SET storage_used = storage_used + :size WHERE id = :user_id
            ");
            $updateQuotaStmt->execute([':size' => $fileSize, ':user_id' => $userId]);

            $db->commit();

            return [
                'success' => true,
                'file_id' => $fileId,
                'file_name' => $originalName,
                'file_size' => $fileSize,
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            if (file_exists($targetFullPath)) {
                unlink($targetFullPath);
            }
            return ['success' => false, 'error' => 'Gagal menyimpan metadata berkas: ' . $e->getMessage()];
        }
    }

    public static function deleteFile(int $fileId, int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, storage_filename, storage_path, file_size 
            FROM files 
            WHERE id = :id AND user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $fileId, ':user_id' => $userId]);
        $file = $stmt->fetch();

        if (!$file) {
            return ['success' => false, 'error' => 'Berkas tidak ditemukan atau Anda tidak memiliki izin.'];
        }

        $uploadBaseDir = self::getUploadDir();
        // Bersihkan prefix uploads/ jika ada
        $cleanPath = preg_replace('~^uploads[\\\\/]~', '', $file['storage_path']);
        $fullPath = $uploadBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath) . $file['storage_filename'];

        $db->beginTransaction();
        try {
            // Hapus record dari database
            $deleteStmt = $db->prepare("DELETE FROM files WHERE id = :id");
            $deleteStmt->execute([':id' => $fileId]);

            // Kurangi kuota terpakai user
            $updateQuotaStmt = $db->prepare("
                UPDATE users SET storage_used = GREATEST(0, CAST(storage_used AS SIGNED) - :size) WHERE id = :user_id
            ");
            $updateQuotaStmt->execute([':size' => (int) $file['file_size'], ':user_id' => $userId]);

            $db->commit();

            // Hapus file fisik dari disk
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return ['success' => true];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Gagal menghapus berkas: ' . $e->getMessage()];
        }
    }

    public static function getPhysicalPath(string $storagePath, string $storageFilename): string
    {
        $uploadBaseDir = self::getUploadDir();
        $cleanPath = preg_replace('~^uploads[\\\\/]~', '', $storagePath);
        return $uploadBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath) . $storageFilename;
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);
        return round($value, $precision) . ' ' . $units[$power];
    }

    private static function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'Ukuran berkas melebihi batas upload_max_filesize di php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Ukuran berkas melebihi batas form.',
            UPLOAD_ERR_PARTIAL    => 'Berkas hanya terunggah sebagian. Silakan coba kembali.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada berkas yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara server hilang.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas ke penyimpanan server.',
            UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP membatalkan unggahan berkas.',
            default               => 'Terjadi kesalahan saat mengunggah berkas.',
        };
    }
}
