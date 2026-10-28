<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Services\AuthService;
use App\Services\DownloadService;
use App\Services\ShareService;
use App\Services\StorageService;
use App\Services\WebhookService;

class DriveController
{
    public function __construct()
    {
        if (!AuthService::check()) {
            header('Location: ?action=login');
            exit;
        }
    }

    public function index(): void
    {
        $userId = (int) AuthService::id();
        $user = AuthService::user();
        $activeTab = $_GET['tab'] ?? 'my_drive';
        $folderId = isset($_GET['folder']) && is_numeric($_GET['folder']) ? (int) $_GET['folder'] : null;
        $search = trim($_GET['q'] ?? '');

        $db = Database::getConnection();

        // 1. Info storage quota
        $storageUsed = (int) ($user['storage_used'] ?? 0);
        $storageQuota = (int) ($user['storage_quota'] ?? 5368709120);
        $quotaPercentage = $storageQuota > 0 ? min(100, round(($storageUsed / $storageQuota) * 100, 1)) : 0;

        // 2. Daftar user lain untuk modal direct share
        $allOtherUsers = ShareService::getAllUsersExcept($userId);

        $folders = [];
        $files = [];
        $sharedWithMeFiles = ShareService::getFilesSharedWithUser($userId);
        $sharedWithMeCount = count($sharedWithMeFiles);
        $myPublicShares = ShareService::getUserPublicShares($userId);
        $myPublicSharesCount = count($myPublicShares);
        $breadcrumbs = [['id' => null, 'name' => 'Drive Saya']];

        if ($activeTab === 'my_shares') {
            $breadcrumbs = [['id' => null, 'name' => 'Tautan Publik Saya']];
            if (!empty($search)) {
                $myPublicShares = array_filter($myPublicShares, function ($item) use ($search) {
                    return stripos($item['original_name'], $search) !== false || stripos($item['share_token'], $search) !== false;
                });
                $myPublicShares = array_values($myPublicShares);
            }
            $files = [];
            $folders = [];
        } elseif ($activeTab === 'shared_with_me') {
            // Tab Dibagikan ke Saya
            $breadcrumbs = [['id' => null, 'name' => 'Dibagikan ke Saya']];
            if (!empty($search)) {
                $sharedWithMeFiles = array_filter($sharedWithMeFiles, function ($item) use ($search) {
                    return stripos($item['original_name'], $search) !== false || stripos($item['shared_by_username'], $search) !== false;
                });
            }
            $files = array_values($sharedWithMeFiles);
            $folders = [];
        } elseif ($activeTab === 'recent') {
            // Tab Terbaru (50 berkas terbaru yang tidak di tempat sampah)
            $recentQuery = "
                SELECT f.id, f.original_name, f.storage_filename, f.storage_path, f.mime_type, f.file_size, f.created_at,
                       s.share_token, s.is_active as is_shared, s.expires_at, s.download_count, s.max_downloads,
                       (SELECT COUNT(*) FROM user_shares us WHERE us.file_id = f.id) as user_shares_count
                FROM files f
                LEFT JOIN share_links s ON f.id = s.file_id AND s.is_active = 1
                WHERE f.user_id = :user_id AND f.deleted_at IS NULL
                ORDER BY f.created_at DESC
                LIMIT 50
            ";
            $recentStmt = $db->prepare($recentQuery);
            $recentStmt->execute([':user_id' => $userId]);
            $files = $recentStmt->fetchAll();
            $folders = [];
        } elseif ($activeTab === 'starred') {
            $breadcrumbs = [['id' => null, 'name' => 'Berbintang']];
            $files = [];
            $folders = [];
        } elseif ($activeTab === 'trash') {
            // Tab Sampah (Recycle Bin)
            $breadcrumbs = [['id' => null, 'name' => 'Keranjang Sampah']];

            // Ambil subfolder yang dihapus
            $trashFoldersStmt = $db->prepare("SELECT id, name, updated_at, deleted_at FROM folders WHERE user_id = :user_id AND deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $trashFoldersStmt->execute([':user_id' => $userId]);
            $folders = $trashFoldersStmt->fetchAll();

            // Ambil file yang dihapus
            $trashFilesStmt = $db->prepare("
                SELECT f.id, f.original_name, f.storage_filename, f.storage_path, f.mime_type, f.file_size, f.created_at, f.deleted_at,
                       0 as is_shared, NULL as share_token, 0 as user_shares_count
                FROM files f
                WHERE f.user_id = :user_id AND f.deleted_at IS NOT NULL
                ORDER BY f.deleted_at DESC
            ");
            $trashFilesStmt->execute([':user_id' => $userId]);
            $files = $trashFilesStmt->fetchAll();
        } else {
            // Tab Drive Saya (Default)
            $currentFolder = null;
            if ($folderId !== null) {
                $folderStmt = $db->prepare("SELECT id, name, parent_id FROM folders WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL");
                $folderStmt->execute([':id' => $folderId, ':user_id' => $userId]);
                $currentFolder = $folderStmt->fetch();

                if (!$currentFolder) {
                    header('Location: ?action=drive');
                    exit;
                }

                // Bangun hierarki breadcrumb
                $trail = [];
                $tempFolder = $currentFolder;
                while ($tempFolder) {
                    array_unshift($trail, ['id' => $tempFolder['id'], 'name' => $tempFolder['name']]);
                    if ($tempFolder['parent_id'] !== null) {
                        $parentStmt = $db->prepare("SELECT id, name, parent_id FROM folders WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL");
                        $parentStmt->execute([':id' => $tempFolder['parent_id'], ':user_id' => $userId]);
                        $tempFolder = $parentStmt->fetch();
                    } else {
                        $tempFolder = null;
                    }
                }
                $breadcrumbs = array_merge($breadcrumbs, $trail);
            }

            // Ambil subfolder aktif
            if (!empty($search)) {
                $folderQuery = "SELECT id, name, updated_at FROM folders WHERE user_id = :user_id AND deleted_at IS NULL AND name LIKE :search ORDER BY name ASC";
                $folderParams = [':user_id' => $userId, ':search' => "%{$search}%"];
            } else {
                if ($folderId === null) {
                    $folderQuery = "SELECT id, name, updated_at FROM folders WHERE user_id = :user_id AND deleted_at IS NULL AND parent_id IS NULL ORDER BY name ASC";
                    $folderParams = [':user_id' => $userId];
                } else {
                    $folderQuery = "SELECT id, name, updated_at FROM folders WHERE user_id = :user_id AND deleted_at IS NULL AND parent_id = :parent_id ORDER BY name ASC";
                    $folderParams = [':user_id' => $userId, ':parent_id' => $folderId];
                }
            }
            $foldersStmt = $db->prepare($folderQuery);
            $foldersStmt->execute($folderParams);
            $folders = $foldersStmt->fetchAll();

            // Ambil file aktif
            if (!empty($search)) {
                $fileQuery = "
                    SELECT f.id, f.original_name, f.storage_filename, f.storage_path, f.mime_type, f.file_size, f.created_at,
                           s.share_token, s.is_active as is_shared, s.expires_at, s.download_count, s.max_downloads,
                           (SELECT COUNT(*) FROM user_shares us WHERE us.file_id = f.id) as user_shares_count
                    FROM files f
                    LEFT JOIN share_links s ON f.id = s.file_id AND s.is_active = 1
                    WHERE f.user_id = :user_id AND f.deleted_at IS NULL AND f.original_name LIKE :search
                    ORDER BY f.created_at DESC
                ";
                $fileParams = [':user_id' => $userId, ':search' => "%{$search}%"];
            } else {
                if ($folderId === null) {
                    $fileQuery = "
                        SELECT f.id, f.original_name, f.storage_filename, f.storage_path, f.mime_type, f.file_size, f.created_at,
                               s.share_token, s.is_active as is_shared, s.expires_at, s.download_count, s.max_downloads,
                               (SELECT COUNT(*) FROM user_shares us WHERE us.file_id = f.id) as user_shares_count
                        FROM files f
                        LEFT JOIN share_links s ON f.id = s.file_id AND s.is_active = 1
                        WHERE f.user_id = :user_id AND f.deleted_at IS NULL AND f.folder_id IS NULL
                        ORDER BY f.created_at DESC
                    ";
                    $fileParams = [':user_id' => $userId];
                } else {
                    $fileQuery = "
                        SELECT f.id, f.original_name, f.storage_filename, f.storage_path, f.mime_type, f.file_size, f.created_at,
                               s.share_token, s.is_active as is_shared, s.expires_at, s.download_count, s.max_downloads,
                               (SELECT COUNT(*) FROM user_shares us WHERE us.file_id = f.id) as user_shares_count
                        FROM files f
                        LEFT JOIN share_links s ON f.id = s.file_id AND s.is_active = 1
                        WHERE f.user_id = :user_id AND f.deleted_at IS NULL AND f.folder_id = :folder_id
                        ORDER BY f.created_at DESC
                    ";
                    $fileParams = [':user_id' => $userId, ':folder_id' => $folderId];
                }
            }
            $filesStmt = $db->prepare($fileQuery);
            $filesStmt->execute($fileParams);
            $files = $filesStmt->fetchAll();
        }

        // Summary Counts for Modern Dashboard Overview (Hanya yang aktif / bukan sampah)
        $totalFilesCount = (int) $db->query("SELECT COUNT(*) FROM files WHERE user_id = {$userId} AND deleted_at IS NULL")->fetchColumn();
        $totalFoldersCount = (int) $db->query("SELECT COUNT(*) FROM folders WHERE user_id = {$userId} AND deleted_at IS NULL")->fetchColumn();
        $totalSharedCount = (int) $db->query("
            SELECT (
                (SELECT COUNT(*) FROM share_links WHERE user_id = {$userId} AND is_active = 1) +
                (SELECT COUNT(*) FROM user_shares WHERE shared_by_user_id = {$userId} OR shared_with_user_id = {$userId})
            )
        ")->fetchColumn();

        $storageBreakdown = StorageService::getStorageCategoryBreakdown($userId);
        $csrfToken = AuthService::csrfToken();

        require_once dirname(__DIR__) . '/Views/drive/index.php';
    }

    public function createFolder(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $name = trim($_POST['name'] ?? '');
        $parentId = !empty($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        if (empty($name)) {
            $this->respondJson(['success' => false, 'error' => 'Nama folder tidak boleh kosong.']);
            return;
        }

        $db = Database::getConnection();
        if ($parentId === null) {
            $checkStmt = $db->prepare("SELECT id FROM folders WHERE user_id = :user_id AND parent_id IS NULL AND name = :name");
            $checkStmt->execute([':user_id' => $userId, ':name' => $name]);
        } else {
            $checkStmt = $db->prepare("SELECT id FROM folders WHERE user_id = :user_id AND parent_id = :parent_id AND name = :name");
            $checkStmt->execute([':user_id' => $userId, ':parent_id' => $parentId, ':name' => $name]);
        }

        if ($checkStmt->fetch()) {
            $this->respondJson(['success' => false, 'error' => 'Folder dengan nama ini sudah ada.']);
            return;
        }

        $insertStmt = $db->prepare("INSERT INTO folders (user_id, parent_id, name) VALUES (:user_id, :parent_id, :name)");
        $insertStmt->execute([':user_id' => $userId, ':parent_id' => $parentId, ':name' => $name]);

        $this->respondJson(['success' => true]);
    }

    public function upload(): void
    {
        try {
            $this->validateCsrf();
            $userId = (int) AuthService::id();
            $folderId = !empty($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;

            if (!isset($_FILES['file'])) {
                $this->respondJson(['success' => false, 'error' => 'Tidak ada berkas yang diterima.']);
                return;
            }

            $result = StorageService::storeUploadedFile($_FILES['file'], $userId, $folderId);
            $this->respondJson($result);
        } catch (\Throwable $e) {
            $this->respondJson([
                'success' => false,
                'error' => 'Gagal mengunggah berkas: ' . $e->getMessage()
            ]);
        }
    }

    public function uploadChunk(): void
    {
        @set_time_limit(600);
        try {
            $this->validateCsrf();
            $userId = (int) AuthService::id();

            $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['upload_id'] ?? '');
            $chunkIndex = isset($_POST['chunk_index']) ? (int) $_POST['chunk_index'] : -1;
            $totalChunks = isset($_POST['total_chunks']) ? (int) $_POST['total_chunks'] : -1;
            $originalName = basename($_POST['filename'] ?? '');
            $totalSize = isset($_POST['total_size']) ? (int) $_POST['total_size'] : 0;
            $folderId = !empty($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;

            if (empty($uploadId) || $chunkIndex < 0 || $totalChunks <= 0 || empty($originalName) || !isset($_FILES['file'])) {
                $this->respondJson(['success' => false, 'error' => 'Parameter potongan berkas tidak lengkap.']);
                return;
            }

            // Cek kuota pada potongan pertama & bersihkan potongan usang secara berkala
            if ($chunkIndex === 0) {
                if (mt_rand(1, 10) === 1) {
                    StorageService::cleanOldChunks(86400);
                }
                $user = AuthService::user();
                if ($user && ($user['storage_used'] + $totalSize) > $user['storage_quota']) {
                    $this->respondJson(['success' => false, 'error' => 'Kapasitas penyimpanan Anda tidak mencukupi untuk berkas ini.']);
                    return;
                }
            }

            $chunkFile = $_FILES['file'];
            if ($chunkFile['error'] !== UPLOAD_ERR_OK) {
                $this->respondJson(['success' => false, 'error' => 'Gagal menerima potongan berkas.']);
                return;
            }

            $chunkBaseDir = StorageService::getChunkDir();
            $chunkDir = $chunkBaseDir . DIRECTORY_SEPARATOR . $uploadId;
            if (!is_dir($chunkDir)) {
                @mkdir($chunkDir, 0775, true);
            }

            $chunkPath = $chunkDir . DIRECTORY_SEPARATOR . "chunk_{$chunkIndex}.part";
            if (!move_uploaded_file($chunkFile['tmp_name'], $chunkPath)) {
                $this->respondJson(['success' => false, 'error' => "Gagal menyimpan potongan ke-{$chunkIndex}."]);
                return;
            }

            // Jika masih ada potongan berikutnya
            if ($chunkIndex < $totalChunks - 1) {
                $this->respondJson([
                    'success' => true,
                    'completed' => false,
                    'chunk_index' => $chunkIndex,
                    'total_chunks' => $totalChunks
                ]);
                return;
            }

            // Seluruh potongan lengkap, verifikasi integritas
            for ($i = 0; $i < $totalChunks; $i++) {
                $part = $chunkDir . DIRECTORY_SEPARATOR . "chunk_{$i}.part";
                if (!file_exists($part)) {
                    $this->respondJson(['success' => false, 'error' => "Potongan berkas ke-{$i} tidak ditemukan."]);
                    return;
                }
            }

            // Gabungkan potongan
            $mergedTempPath = $chunkDir . DIRECTORY_SEPARATOR . 'merged.tmp';
            $out = fopen($mergedTempPath, 'wb');
            if (!$out) {
                $this->respondJson(['success' => false, 'error' => 'Gagal membuat berkas penggabungan di server.']);
                return;
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $part = $chunkDir . DIRECTORY_SEPARATOR . "chunk_{$i}.part";
                $in = fopen($part, 'rb');
                if ($in) {
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                }
                @unlink($part);
            }
            fclose($out);

            // Simpan berkas gabungan ke storage permanen
            $result = StorageService::storeMergedFile($mergedTempPath, $originalName, $userId, $folderId);

            // Bersihkan direktori chunk sementara
            @unlink($mergedTempPath);
            @rmdir($chunkDir);

            $result['completed'] = true;
            $this->respondJson($result);
        } catch (\Throwable $e) {
            $this->respondJson([
                'success' => false,
                'error' => 'Gagal memproses potongan berkas: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteFile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE files SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $fileId, ':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function bulkDeleteFiles(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileIds = $_POST['file_ids'] ?? [];

        if (is_string($fileIds)) {
            $decoded = json_decode($fileIds, true);
            if (is_array($decoded)) {
                $fileIds = $decoded;
            }
        }

        if (!is_array($fileIds) || empty($fileIds)) {
            $this->respondJson(['success' => false, 'error' => 'Tidak ada berkas yang dipilih.']);
            return;
        }

        $cleanIds = array_map('intval', $fileIds);
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE files SET deleted_at = NOW() WHERE user_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$userId], $cleanIds));

        $this->respondJson(['success' => true, 'count' => count($cleanIds)]);
    }

    public function deleteFolder(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $folderId = (int) ($_POST['folder_id'] ?? 0);

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE folders SET deleted_at = NOW() WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $folderId, ':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function restoreFile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE files SET deleted_at = NULL WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $fileId, ':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function restoreFolder(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $folderId = (int) ($_POST['folder_id'] ?? 0);

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE folders SET deleted_at = NULL WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $folderId, ':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function permanentDeleteFile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $result = StorageService::deleteFile($fileId, $userId);
        $this->respondJson($result);
    }

    public function permanentDeleteFolder(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $folderId = (int) ($_POST['folder_id'] ?? 0);

        $db = Database::getConnection();
        $deleteFolderContents = function (int $fid) use (&$deleteFolderContents, $db, $userId): void {
            $subStmt = $db->prepare("SELECT id FROM folders WHERE parent_id = :pid AND user_id = :uid");
            $subStmt->execute([':pid' => $fid, ':uid' => $userId]);
            foreach ($subStmt->fetchAll() as $sub) {
                $deleteFolderContents((int) $sub['id']);
            }

            $filesStmt = $db->prepare("SELECT id FROM files WHERE folder_id = :fid AND user_id = :uid");
            $filesStmt->execute([':fid' => $fid, ':uid' => $userId]);
            foreach ($filesStmt->fetchAll() as $f) {
                StorageService::deleteFile((int) $f['id'], $userId);
            }
        };

        $deleteFolderContents($folderId);

        $stmt = $db->prepare("DELETE FROM folders WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $folderId, ':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function emptyTrash(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $db = Database::getConnection();

        $filesStmt = $db->prepare("SELECT id FROM files WHERE user_id = :user_id AND deleted_at IS NOT NULL");
        $filesStmt->execute([':user_id' => $userId]);
        $files = $filesStmt->fetchAll();
        foreach ($files as $f) {
            StorageService::deleteFile((int) $f['id'], $userId);
        }

        $folderStmt = $db->prepare("DELETE FROM folders WHERE user_id = :user_id AND deleted_at IS NOT NULL");
        $folderStmt->execute([':user_id' => $userId]);

        $this->respondJson(['success' => true]);
    }

    public function downloadFolderZip(): void
    {
        $userId = (int) AuthService::id();
        $folderId = (int) ($_GET['folder_id'] ?? 0);

        if ($folderId <= 0) {
            http_response_code(400);
            die('ID folder tidak valid.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name FROM folders WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL");
        $stmt->execute([':id' => $folderId, ':user_id' => $userId]);
        $folder = $stmt->fetch();

        if (!$folder) {
            http_response_code(404);
            die('Folder tidak ditemukan.');
        }

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            die('Ekstensi PHP ZipArchive tidak aktif.');
        }

        $zip = new \ZipArchive();
        $tempZipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'folder_' . $folderId . '_' . uniqid() . '.zip';

        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            die('Gagal membuat arsip ZIP.');
        }

        $addFolderToZip = function ($currentFolderId, $zipPath) use (&$addFolderToZip, $zip, $db, $userId) {
            $fileStmt = $db->prepare("SELECT id, original_name, storage_path, storage_filename FROM files WHERE folder_id = :fid AND user_id = :uid AND deleted_at IS NULL");
            $fileStmt->execute([':fid' => $currentFolderId, ':uid' => $userId]);
            $files = $fileStmt->fetchAll();

            foreach ($files as $f) {
                $fullPath = StorageService::getFullPath($f['storage_path'], $f['storage_filename']);
                if (file_exists($fullPath) && is_readable($fullPath)) {
                    $entryName = ($zipPath !== '' ? $zipPath . '/' : '') . $f['original_name'];
                    $zip->addFile($fullPath, $entryName);
                }
            }

            $subStmt = $db->prepare("SELECT id, name FROM folders WHERE parent_id = :pid AND user_id = :uid AND deleted_at IS NULL");
            $subStmt->execute([':pid' => $currentFolderId, ':uid' => $userId]);
            $subs = $subStmt->fetchAll();

            foreach ($subs as $sub) {
                $subZipPath = ($zipPath !== '' ? $zipPath . '/' : '') . $sub['name'];
                $zip->addEmptyDir($subZipPath);
                $addFolderToZip($sub['id'], $subZipPath);
            }
        };

        $zip->addEmptyDir($folder['name']);
        $addFolderToZip($folderId, $folder['name']);
        $zip->close();

        if (!file_exists($tempZipPath)) {
            http_response_code(500);
            die('Berkas ZIP gagal dihasilkan.');
        }

        $cleanZipName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $folder['name']) . '.zip';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $cleanZipName . '"');
        header('Content-Length: ' . filesize($tempZipPath));
        header('Cache-Control: private, no-transform');
        header('X-Accel-Buffering: no');

        readfile($tempZipPath);
        @unlink($tempZipPath);
        exit;
    }

    public function moveFile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();

        // Dukung pemindahan berkas tunggal maupun banyak sekaligus (bulk drag & drop)
        $fileIds = [];
        if (!empty($_POST['file_ids'])) {
            $raw = $_POST['file_ids'];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $fileIds = $decoded;
            } elseif (is_array($raw)) {
                $fileIds = $raw;
            }
        }
        if (empty($fileIds) && !empty($_POST['file_id'])) {
            $fileIds = [(int)$_POST['file_id']];
        }

        $folderId = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' && $_POST['folder_id'] !== 'root' && is_numeric($_POST['folder_id'])
            ? (int) $_POST['folder_id']
            : null;

        if (empty($fileIds)) {
            $this->respondJson(['success' => false, 'error' => 'Tidak ada berkas yang dipilih.']);
            return;
        }

        $db = Database::getConnection();

        if ($folderId !== null) {
            $checkFolder = $db->prepare("SELECT id, name FROM folders WHERE id = :id AND user_id = :user_id");
            $checkFolder->execute([':id' => $folderId, ':user_id' => $userId]);
            if (!$checkFolder->fetch()) {
                $this->respondJson(['success' => false, 'error' => 'Folder tujuan tidak ditemukan.']);
                return;
            }
        }

        $cleanIds = array_map('intval', $fileIds);
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "UPDATE files SET folder_id = ? WHERE user_id = ? AND id IN ($placeholders)";
        $params = array_merge([$folderId, $userId], $cleanIds);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $this->respondJson(['success' => true, 'count' => count($cleanIds)]);
    }

    public function renameFile(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $newName = trim($_POST['name'] ?? '');

        if ($fileId <= 0 || empty($newName)) {
            $this->respondJson(['success' => false, 'error' => 'Nama berkas tidak boleh kosong.']);
            return;
        }

        $newName = str_replace(["\0", '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $newName);
        if (empty($newName)) {
            $this->respondJson(['success' => false, 'error' => 'Nama berkas mengandung karakter yang tidak diizinkan.']);
            return;
        }

        if (StorageService::isDangerousExtension($newName)) {
            $this->respondJson(['success' => false, 'error' => 'Berkas tidak boleh diubah ke ekstensi skrip (.php, .phar).']);
            return;
        }

        $db = Database::getConnection();

        $checkStmt = $db->prepare("SELECT id, original_name FROM files WHERE id = :id AND user_id = :user_id");
        $checkStmt->execute([':id' => $fileId, ':user_id' => $userId]);
        $file = $checkStmt->fetch();
        if (!$file) {
            $this->respondJson(['success' => false, 'error' => 'Berkas tidak ditemukan atau bukan milik Anda.']);
            return;
        }

        $oldExt = pathinfo($file['original_name'], PATHINFO_EXTENSION);
        $newExt = pathinfo($newName, PATHINFO_EXTENSION);

        if (empty($newExt) && !empty($oldExt)) {
            $newName .= '.' . $oldExt;
        }

        $stmt = $db->prepare("UPDATE files SET original_name = :name WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':name' => $newName, ':id' => $fileId, ':user_id' => $userId]);

        $this->respondJson(['success' => true, 'new_name' => $newName]);
    }

    public function renameFolder(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $folderId = (int) ($_POST['folder_id'] ?? 0);
        $newName = trim($_POST['name'] ?? '');

        if ($folderId <= 0 || empty($newName)) {
            $this->respondJson(['success' => false, 'error' => 'Nama folder tidak boleh kosong.']);
            return;
        }

        $newName = str_replace(["\0", '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $newName);
        if (empty($newName)) {
            $this->respondJson(['success' => false, 'error' => 'Nama folder mengandung karakter yang tidak diizinkan.']);
            return;
        }

        $db = Database::getConnection();

        $checkStmt = $db->prepare("SELECT id, parent_id FROM folders WHERE id = :id AND user_id = :user_id");
        $checkStmt->execute([':id' => $folderId, ':user_id' => $userId]);
        $folder = $checkStmt->fetch();
        if (!$folder) {
            $this->respondJson(['success' => false, 'error' => 'Folder tidak ditemukan.']);
            return;
        }

        if ($folder['parent_id'] === null) {
            $dupStmt = $db->prepare("SELECT id FROM folders WHERE user_id = :user_id AND parent_id IS NULL AND name = :name AND id != :id");
            $dupStmt->execute([':user_id' => $userId, ':name' => $newName, ':id' => $folderId]);
        } else {
            $dupStmt = $db->prepare("SELECT id FROM folders WHERE user_id = :user_id AND parent_id = :parent_id AND name = :name AND id != :id");
            $dupStmt->execute([':user_id' => $userId, ':parent_id' => $folder['parent_id'], ':name' => $newName, ':id' => $folderId]);
        }

        if ($dupStmt->fetch()) {
            $this->respondJson(['success' => false, 'error' => 'Folder dengan nama ini sudah ada di lokasi yang sama.']);
            return;
        }

        $stmt = $db->prepare("UPDATE folders SET name = :name WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':name' => $newName, ':id' => $folderId, ':user_id' => $userId]);

        $this->respondJson(['success' => true, 'new_name' => $newName]);
    }

    public function createShare(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        $expiry = trim($_POST['expiry'] ?? 'never');
        $maxDownloads = !empty($_POST['max_downloads']) ? (int) $_POST['max_downloads'] : null;
        $allowPreview = isset($_POST['allow_preview']) && (int) $_POST['allow_preview'] === 1;

        $result = ShareService::createShareLink($fileId, $userId, $password ?: null, $expiry, $maxDownloads, $allowPreview);
        $this->respondJson($result);
    }

    public function revokePublicShare(): void
    {
        $this->validateCsrf();
        $userId = (int) AuthService::id();
        $shareId = (int) ($_POST['share_id'] ?? 0);
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $ok = false;
        if ($shareId > 0) {
            $ok = ShareService::revokeShare($shareId, $userId);
        } elseif ($fileId > 0) {
            $ok = ShareService::revokeShareByFileId($fileId, $userId);
        }

        $this->respondJson([
            'success' => $ok,
            'message' => $ok ? 'Tautan publik berhasil dinonaktifkan.' : null,
            'error'   => $ok ? null : 'Tautan publik tidak ditemukan atau sudah dicabut.'
        ]);
    }

    // Direct User-to-User Sharing Endpoints
    public function shareWithUser(): void
    {
        $this->validateCsrf();
        $byUserId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $withUserId = (int) ($_POST['with_user_id'] ?? 0);
        $permission = trim($_POST['permission'] ?? 'download');

        $result = ShareService::shareWithUser($fileId, $byUserId, $withUserId, $permission);
        $this->respondJson($result);
    }

    public function getFileUserShares(): void
    {
        $byUserId = (int) AuthService::id();
        $fileId = (int) ($_GET['file_id'] ?? 0);

        $shares = ShareService::getSharesForFile($fileId, $byUserId);
        $this->respondJson(['success' => true, 'shares' => $shares]);
    }

    public function revokeUserShare(): void
    {
        $this->validateCsrf();
        $byUserId = (int) AuthService::id();
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $withUserId = (int) ($_POST['with_user_id'] ?? 0);

        $result = ShareService::revokeUserShare($fileId, $byUserId, $withUserId);
        $this->respondJson(['success' => $result]);
    }

    public function downloadDirect(): void
    {
        $userId = (int) AuthService::id();
        $fileId = (int) ($_GET['file_id'] ?? 0);

        $db = Database::getConnection();

        // 1. Cek apakah berkas milik user sendiri (dan tidak di tempat sampah)
        $stmt = $db->prepare("SELECT * FROM files WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $fileId, ':user_id' => $userId]);
        $file = $stmt->fetch();

        // 2. Jika bukan milik sendiri, cek apakah dibagikan langsung ke user ini
        $isSharedWithMe = false;
        $permission = 'download';
        if (!$file) {
            $shareStmt = $db->prepare("
                SELECT f.*, us.permission 
                FROM user_shares us
                INNER JOIN files f ON us.file_id = f.id
                WHERE us.file_id = :file_id AND us.shared_with_user_id = :user_id AND f.deleted_at IS NULL
                LIMIT 1
            ");
            $shareStmt->execute([':file_id' => $fileId, ':user_id' => $userId]);
            $sharedRow = $shareStmt->fetch();
            if ($sharedRow) {
                $file = $sharedRow;
                $isSharedWithMe = true;
                $permission = $sharedRow['permission'];
            }
        }

        if (!$file) {
            http_response_code(404);
            echo "Berkas tidak ditemukan atau Anda tidak memiliki akses.";
            exit;
        }

        $forceDownload = (!isset($_GET['preview']) || (int) $_GET['preview'] !== 1) 
            && (!isset($_GET['thumb']) || (int) $_GET['thumb'] !== 1);

        // Jika permission hanya 'view' dan mencoba download langsung, cegah!
        if ($isSharedWithMe && $permission === 'view' && $forceDownload) {
            http_response_code(403);
            echo "Pemilik berkas hanya memberikan izin pratinjau (view only).";
            exit;
        }

        $fullPath = StorageService::getPhysicalPath($file['storage_path'], $file['storage_filename']);

        if ($forceDownload) {
            $currentUser = AuthService::user();
            WebhookService::trigger(
                'download',
                'Berkas Diunduh (Langsung)',
                "Pengguna **" . ($currentUser['username'] ?? 'User') . "** mengunduh berkas **{$file['original_name']}**.",
                [
                    'Berkas'    => $file['original_name'],
                    'Ukuran'    => StorageService::formatBytes((int) $file['file_size']),
                    'Pengunduh' => $currentUser['username'] ?? 'User',
                    'IP'        => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]
            );
        }

        DownloadService::stream($fullPath, $file['original_name'], $file['mime_type'], $forceDownload);
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
