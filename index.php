<?php
declare(strict_types=1);

require_once __DIR__ . '/src/init.php';

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\DriveController;
use App\Controllers\GuestController;
use App\Controllers\ProfileController;
use App\Controllers\ServerController;
use App\Controllers\UserController;
use App\Services\AuthService;

// Cek koneksi Database sebelum routing
$dbCheck = Database::checkConnection();
if (!$dbCheck['status']) {
    // Tampilkan panduan persiapan database yang ramah pengguna
    $dbError = $dbCheck['message'];
    require_once __DIR__ . '/src/Views/system/db_setup.php';
    exit;
}

// Router sederhana berbasis query parameter 'action'
$action = $_GET['action'] ?? (AuthService::check() ? 'drive' : 'login');

switch ($action) {
    // Autentikasi
    case 'login':
        (new AuthController())->showLogin();
        break;
    case 'do_login':
        (new AuthController())->handleLogin();
        break;
    case 'logout':
        (new AuthController())->handleLogout();
        break;

    // Profil Pengguna (Edit Foto, Nama, Email, Password)
    case 'profile':
        (new ProfileController())->index();
        break;
    case 'update_profile':
        (new ProfileController())->updateProfile();
        break;
    case 'update_password':
        (new ProfileController())->updatePassword();
        break;
    case 'avatar':
        (new ProfileController())->getAvatar();
        break;

    // File Manager / Drive
    case 'drive':
        (new DriveController())->index();
        break;
    case 'create_folder':
        (new DriveController())->createFolder();
        break;
    case 'upload':
        (new DriveController())->upload();
        break;
    case 'upload_chunk':
        (new DriveController())->uploadChunk();
        break;
    case 'delete_file':
        (new DriveController())->deleteFile();
        break;
    case 'move_file':
        (new DriveController())->moveFile();
        break;
    case 'bulk_delete_files':
        (new DriveController())->bulkDeleteFiles();
        break;
    case 'delete_folder':
        (new DriveController())->deleteFolder();
        break;
    case 'rename_file':
        (new DriveController())->renameFile();
        break;
    case 'rename_folder':
        (new DriveController())->renameFolder();
        break;
    case 'restore_file':
        (new DriveController())->restoreFile();
        break;
    case 'restore_folder':
        (new DriveController())->restoreFolder();
        break;
    case 'permanent_delete_file':
        (new DriveController())->permanentDeleteFile();
        break;
    case 'permanent_delete_folder':
        (new DriveController())->permanentDeleteFolder();
        break;
    case 'empty_trash':
        (new DriveController())->emptyTrash();
        break;
    case 'download_folder_zip':
        (new DriveController())->downloadFolderZip();
        break;
    case 'create_share':
        (new DriveController())->createShare();
        break;
    case 'revoke_public_share':
        (new DriveController())->revokePublicShare();
        break;
    case 'share_with_user':
        (new DriveController())->shareWithUser();
        break;
    case 'get_file_user_shares':
        (new DriveController())->getFileUserShares();
        break;
    case 'revoke_user_share':
        (new DriveController())->revokeUserShare();
        break;
    case 'download_direct':
        (new DriveController())->downloadDirect();
        break;

    // Portal Tamu / Share Link
    case 'share':
        (new GuestController())->viewShare();
        break;
    case 'download_share':
        (new GuestController())->downloadShare();
        break;

    // Manajemen Pengguna (Admin)
    case 'users':
        (new UserController())->index();
        break;
    case 'create_user':
        (new UserController())->createUser();
        break;
    case 'update_user':
        (new UserController())->updateUser();
        break;
    case 'reset_user_password':
        (new UserController())->resetPassword();
        break;
    case 'delete_user':
        (new UserController())->deleteUser();
        break;

    // Pengaturan Server & Webhook (Admin)
    case 'server':
        (new ServerController())->index();
        break;
    case 'save_webhook':
        (new ServerController())->saveWebhook();
        break;
    case 'test_webhook':
        (new ServerController())->testWebhook();
        break;

    // Database Self-Healing / Auto-Repair (Khusus Admin)
    case 'repair_db':
        AuthService::requireAdmin();
        $repairResult = Database::repairSchema();
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json');
            echo json_encode($repairResult);
            exit;
        }
        header('Location: index.php?action=drive&repaired=1');
        exit;

    default:
        if (AuthService::check()) {
            (new DriveController())->index();
        } else {
            (new AuthController())->showLogin();
        }
        break;
}
