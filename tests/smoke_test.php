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

// 5. Uji SettingService & WebhookService Safe Defaults
use App\Services\SettingService;
use App\Services\WebhookService;

SettingService::set('test_key', 'test_val');
assert(SettingService::get('test_key') === 'test_val', 'SettingService::get harus mengembalikan nilai yang diset');
assert(SettingService::get('non_existent_key', 'fallback') === 'fallback', 'SettingService::get harus mengembalikan fallback untuk key kosong');

// WebhookService saat disabled (default 0) harus return false tanpa melempar exception
assert(WebhookService::trigger('upload', 'Judul', 'Deskripsi') === false, 'Webhook saat disable harus false');
assert(WebhookService::trigger('invalid_event', 'Judul', 'Deskripsi') === false, 'Webhook dengan event tidak valid harus false');
echo "[PASS] SettingService dan WebhookService safe execution terverifikasi.\n";

// 6. Uji ActivityLogService Metadata & Formatting
use App\Services\ActivityLogService;

$badge = ActivityLogService::getActionBadge('upload');
assert(!empty($badge['label']) && !empty($badge['color']), 'Badge upload harus memiliki label dan color');

$dev = ActivityLogService::parseDevice('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0');
assert($dev['device'] === 'Windows PC' && $dev['browser'] === 'Google Chrome', 'Device parser harus mendeteksi Windows PC & Chrome');

$timeAgo = ActivityLogService::formatTimeAgo(date('Y-m-d H:i:s'));
assert($timeAgo === 'Baru saja', 'formatTimeAgo waktu saat ini harus Baru saja');
echo "[PASS] ActivityLogService metadata, parser, dan formatting terverifikasi.\n";

// 7. Uji Maintenance Tools & Server Controller Helper
use App\Controllers\ServerController;
use App\Services\CacheService;

$serverStats = ServerController::getServerLoadAndMemory();
assert(isset($serverStats['cpu_load'], $serverStats['ram_total'], $serverStats['uptime']), 'getServerLoadAndMemory harus menyediakan cpu_load, ram_total, uptime');

$purgeRes = StorageService::purgeChunks(99999999);
assert(is_array($purgeRes) && isset($purgeRes['deleted_dirs']), 'purgeChunks harus mengembalikan array hasil');

$cacheFlush = CacheService::flush();
assert(is_bool($cacheFlush), 'CacheService::flush harus mengembalikan boolean');

$maskedUrl = WebhookService::maskWebhookUrl('https://discord.com/api/webhooks/123456789/abcdefghijklmnopqrstuvwxyz');
assert(str_starts_with($maskedUrl, 'https://discord.com') && str_contains($maskedUrl, '...'), 'maskWebhookUrl harus menyamarkan token webhook');
echo "[PASS] Server maintenance helpers & URL masking terverifikasi.\n";

echo "Semua smoke test berhasil dilewati dengan sempurna (All tests passed)!\n";
