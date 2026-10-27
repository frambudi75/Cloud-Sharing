<?php
declare(strict_types=1);

require_once __DIR__ . '/src/init.php';

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\DriveController;
use App\Controllers\GuestController;
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
    case 'delete_file':
        (new DriveController())->deleteFile();
        break;
    case 'delete_folder':
        (new DriveController())->deleteFolder();
        break;
    case 'create_share':
        (new DriveController())->createShare();
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

    default:
        if (AuthService::check()) {
            (new DriveController())->index();
        } else {
            (new AuthController())->showLogin();
        }
        break;
}
