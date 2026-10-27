<?php
declare(strict_types=1);

// Smoke Test Mandiri untuk Cloud Sharing (Zero External Dependencies)
// Menjalankan pengecekan cepat logika inti: ekstensi berbahaya, formatting, path, dan csrf.

require_once dirname(__DIR__) . '/src/init.php';

use App\Services\AuthService;
use App\Services\StorageService;

echo "Memulai Smoke Test Cloud Sharing...\n";

// 1. Uji Blokir Ekstensi Berbahaya
assert(StorageService::isDangerousExtension('shell.php') === true, 'Ekstensi .php harus diblokir');
assert(StorageService::isDangerousExtension('archive.phar') === true, 'Ekstensi .phar harus diblokir');
assert(StorageService::isDangerousExtension('script.phtml') === true, 'Ekstensi .phtml harus diblokir');
assert(StorageService::isDangerousExtension('dokumen.pdf') === false, 'Ekstensi .pdf harus diizinkan');
assert(StorageService::isDangerousExtension('foto.png') === false, 'Ekstensi .png harus diizinkan');
echo "[PASS] Deteksi ekstensi file berbahaya berfungsi.\n";

// 2. Uji Formatting Ukuran Berkas (Bytes)
assert(StorageService::formatBytes(0) === '0 B', '0 bytes');
assert(StorageService::formatBytes(1024) === '1 KB', '1024 bytes -> 1 KB');
assert(StorageService::formatBytes(1048576) === '1 MB', '1048576 bytes -> 1 MB');
echo "[PASS] Kalkulasi dan formatting kuota bytes berfungsi.\n";

// 3. Uji Keselarasan getFullPath dan getPhysicalPath
$dummyPath = 'uploads/2026/10/';
$dummyFile = 'testfile.bin';
$full = StorageService::getFullPath($dummyPath, $dummyFile);
$phys = StorageService::getPhysicalPath($dummyPath, $dummyFile);
assert($full === $phys, 'getPhysicalPath harus mengembalikan string yang identik dengan getFullPath');
echo "[PASS] Keselarasan path storage fisik terverifikasi.\n";

// 4. Uji CSRF Token & Hash Timing-Safe Verification
AuthService::startSession();
$token = AuthService::csrfToken();
assert(!empty($token) && strlen($token) === 64, 'Token CSRF harus 64 karakter hex');
assert(AuthService::verifyCsrf($token) === true, 'Verifikasi CSRF token valid');
assert(AuthService::verifyCsrf('invalid_token') === false, 'Verifikasi CSRF token salah harus gagal');
assert(AuthService::verifyCsrf(null) === false, 'Verifikasi CSRF null harus gagal');
echo "[PASS] Sistem token dan verifikasi CSRF berfungsi.\n";

echo "Semua smoke test berhasil dilewati dengan sempurna (All tests passed)!\n";
