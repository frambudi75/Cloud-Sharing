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

    public static function getAvatarDir(): string
    {
        $uploadDir = self::getUploadDir();
        $baseDir = dirname($uploadDir);
        $avatarDir = $baseDir . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($avatarDir)) {
            @mkdir($avatarDir, 0775, true);
        }
        return realpath($avatarDir) ?: $avatarDir;
    }

    public static function getChunkDir(): string
    {
        $uploadDir = self::getUploadDir();
        $baseDir = dirname($uploadDir);
        $chunkDir = $baseDir . DIRECTORY_SEPARATOR . 'chunks';
        if (!is_dir($chunkDir)) {
            @mkdir($chunkDir, 0775, true);
        }
        return realpath($chunkDir) ?: $chunkDir;
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

    public static function storeMergedFile(string $tempFilePath, string $originalName, int $userId, ?int $folderId = null): array
    {
        if (!file_exists($tempFilePath)) {
            return ['success' => false, 'error' => 'Berkas hasil penggabungan tidak ditemukan.'];
        }

        $fileSize = filesize($tempFilePath);

        // Cek Kuota Pengguna
        $user = AuthService::user();
        if ($user && ($user['storage_used'] + $fileSize) > $user['storage_quota']) {
            return ['success' => false, 'error' => 'Kapasitas penyimpanan Anda tidak mencukupi untuk berkas ini.'];
        }

        // Deteksi MIME Type asli
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tempFilePath) ?: 'application/octet-stream';

        // Checksum SHA-256
        $fileHash = hash_file('sha256', $tempFilePath) ?: null;

        // Generate nama file acak
        $storageFilename = bin2hex(random_bytes(16)) . '.bin';

        $subDir = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $uploadBaseDir = self::getUploadDir();
        $targetDirectory = $uploadBaseDir . DIRECTORY_SEPARATOR . $subDir;

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $targetFullPath = $targetDirectory . DIRECTORY_SEPARATOR . $storageFilename;
        $relativeStoragePath = 'uploads/' . date('Y') . '/' . date('m') . '/';

        // Pindahkan file hasil penggabungan
        if (!rename($tempFilePath, $targetFullPath)) {
            if (!copy($tempFilePath, $targetFullPath)) {
                return ['success' => false, 'error' => 'Gagal memindahkan berkas gabungan ke storage.'];
            }
            @unlink($tempFilePath);
        }

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
                @unlink($targetFullPath);
            }
            return ['success' => false, 'error' => 'Gagal mencatat berkas ke database: ' . $e->getMessage()];
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

    public static function bulkDeleteFiles(array $fileIds, int $userId): array
    {
        if (empty($fileIds)) {
            return ['success' => false, 'error' => 'Tidak ada berkas yang dipilih.'];
        }

        $db = Database::getConnection();
        $deletedCount = 0;
        $freedBytes = 0;

        try {
            $db->beginTransaction();

            $inPlaceholders = implode(',', array_fill(0, count($fileIds), '?'));
            $params = array_merge([$userId], array_map('intval', $fileIds));

            $stmt = $db->prepare("
                SELECT id, storage_path, storage_filename, file_size 
                FROM files 
                WHERE user_id = ? AND id IN ({$inPlaceholders})
            ");
            $stmt->execute($params);
            $filesToDelete = $stmt->fetchAll();

            $filesPhysicallyToDelete = [];
            foreach ($filesToDelete as $file) {
                $fullPath = self::getPhysicalPath($file['storage_path'], $file['storage_filename']);
                $filesPhysicallyToDelete[] = $fullPath;
                $freedBytes += (int) $file['file_size'];
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $delStmt = $db->prepare("DELETE FROM files WHERE user_id = ? AND id IN ({$inPlaceholders})");
                $delStmt->execute($params);

                // Update storage pengguna
                $updateUser = $db->prepare("
                    UPDATE users 
                    SET storage_used = GREATEST(0, CAST(storage_used AS SIGNED) - :freed)
                    WHERE id = :user_id
                ");
                $updateUser->execute([':freed' => $freedBytes, ':user_id' => $userId]);
            }

            $db->commit();

            // Hapus berkas fisik dari disk
            foreach ($filesPhysicallyToDelete as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            return ['success' => true, 'count' => $deletedCount, 'freed_bytes' => $freedBytes];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Gagal menghapus berkas: ' . $e->getMessage()];
        }
    }

    public static function getStorageCategoryBreakdown(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT mime_type, original_name, file_size FROM files WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $categories = [
            'images' => ['label' => 'Gambar', 'size' => 0, 'count' => 0, 'color' => '#f43f5e'],
            'documents' => ['label' => 'Dokumen & PDF', 'size' => 0, 'count' => 0, 'color' => '#3b82f6'],
            'videos' => ['label' => 'Video', 'size' => 0, 'count' => 0, 'color' => '#f59e0b'],
            'archives' => ['label' => 'Arsip & ZIP', 'size' => 0, 'count' => 0, 'color' => '#10b981'],
            'audio' => ['label' => 'Audio', 'size' => 0, 'count' => 0, 'color' => '#6366f1'],
            'others' => ['label' => 'Lainnya', 'size' => 0, 'count' => 0, 'color' => '#64748b'],
        ];

        foreach ($rows as $row) {
            $mime = strtolower((string)$row['mime_type']);
            $ext = strtolower(pathinfo((string)$row['original_name'], PATHINFO_EXTENSION));
            $size = (int)$row['file_size'];

            if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'])) {
                $categories['images']['size'] += $size;
                $categories['images']['count']++;
            } elseif ($mime === 'application/pdf' || in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'])) {
                $categories['documents']['size'] += $size;
                $categories['documents']['count']++;
            } elseif (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'webm'])) {
                $categories['videos']['size'] += $size;
                $categories['videos']['count']++;
            } elseif (in_array($mime, ['application/zip', 'application/x-rar-compressed', 'application/x-tar', 'application/gzip', 'application/x-7z-compressed']) || in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                $categories['archives']['size'] += $size;
                $categories['archives']['count']++;
            } elseif (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'flac'])) {
                $categories['audio']['size'] += $size;
                $categories['audio']['count']++;
            } else {
                $categories['others']['size'] += $size;
                $categories['others']['count']++;
            }
        }

        return $categories;
    }

    public static function getFileTypeInfo(string $mimeType, string $fileName): array
    {
        $mime = strtolower($mimeType);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'])) {
            return [
                'type' => 'image',
                'badge' => 'IMG',
                'bg' => 'bg-rose-50 text-rose-600 border border-rose-200/80',
                'icon_bg' => 'bg-rose-100/70 text-rose-600',
            ];
        }

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return [
                'type' => 'pdf',
                'badge' => 'PDF',
                'bg' => 'bg-red-50 text-red-600 border border-red-200/80',
                'icon_bg' => 'bg-red-100/70 text-red-600',
            ];
        }

        if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'webm'])) {
            return [
                'type' => 'video',
                'badge' => 'VID',
                'bg' => 'bg-amber-50 text-amber-700 border border-amber-200/80',
                'icon_bg' => 'bg-amber-100/70 text-amber-600',
            ];
        }

        if (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'flac'])) {
            return [
                'type' => 'audio',
                'badge' => 'AUD',
                'bg' => 'bg-indigo-50 text-indigo-600 border border-indigo-200/80',
                'icon_bg' => 'bg-indigo-100/70 text-indigo-600',
            ];
        }

        if (in_array($mime, ['application/zip', 'application/x-rar-compressed', 'application/x-tar', 'application/gzip', 'application/x-7z-compressed']) || in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
            return [
                'type' => 'archive',
                'badge' => 'ZIP',
                'bg' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
                'icon_bg' => 'bg-emerald-100/70 text-emerald-600',
            ];
        }

        if (in_array($ext, ['doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'csv', 'xls', 'xlsx', 'ppt', 'pptx'])) {
            return [
                'type' => 'document',
                'badge' => strtoupper($ext ?: 'DOC'),
                'bg' => 'bg-sky-50 text-sky-700 border border-sky-200/80',
                'icon_bg' => 'bg-sky-100/70 text-sky-600',
            ];
        }

        if (in_array($ext, ['php', 'js', 'json', 'html', 'css', 'ts', 'py', 'sql', 'yml', 'yaml', 'xml'])) {
            return [
                'type' => 'code',
                'badge' => strtoupper($ext),
                'bg' => 'bg-violet-50 text-violet-700 border border-violet-200/80',
                'icon_bg' => 'bg-violet-100/70 text-violet-600',
            ];
        }

        return [
            'type' => 'file',
            'badge' => strtoupper($ext ?: 'FILE'),
            'bg' => 'bg-slate-100 text-slate-700 border border-slate-200',
            'icon_bg' => 'bg-slate-200/70 text-slate-600',
        ];
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
