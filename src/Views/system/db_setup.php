<?php
use App\Config\Env;

$pageTitle = 'Setup Database - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';

$isDocker = file_exists('/.dockerenv') || Env::get('DB_HOST') === 'db' || PHP_OS_FAMILY !== 'Windows';
$currentHost = Env::get('DB_HOST', '127.0.0.1');
$currentPort = Env::get('DB_PORT', 3306);
$currentDb = Env::get('DB_DATABASE', 'filesharing_db');
$currentUser = Env::get('DB_USERNAME', 'root');

$isAccessDenied = str_contains($dbError ?? '', 'Access denied') || str_contains($dbError ?? '', '1045');
$isConnectionRefused = str_contains($dbError ?? '', 'Connection refused') || str_contains($dbError ?? '', '2002');
$sqlFilePath = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'database.sql') ?: (dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'database.sql');
?>

<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">
    <div class="sm:mx-auto sm:w-full sm:max-w-xl px-4 sm:px-0">
        
        <div class="flex justify-center mb-6">
            <div class="h-14 w-14 rounded-2xl bg-amber-500 flex items-center justify-center text-white shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
            <h1 class="text-xl font-bold text-slate-900 text-center mb-1">
                Database Belum Terhubung
            </h1>
            <p class="text-xs text-slate-500 text-center mb-5">
                Sistem tidak dapat berkomunikasi dengan database MariaDB / MySQL.
            </p>

            <!-- Informasi Ringkas Koneksi -->
            <div class="mb-4 grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-200 text-slate-700">
                <div><span class="text-slate-400">Host:</span> <strong class="font-mono"><?= htmlspecialchars($currentHost) ?>:<?= htmlspecialchars((string)$currentPort) ?></strong></div>
                <div><span class="text-slate-400">Database:</span> <strong class="font-mono"><?= htmlspecialchars($currentDb) ?></strong></div>
                <div><span class="text-slate-400">User:</span> <strong class="font-mono"><?= htmlspecialchars($currentUser) ?></strong></div>
                <div><span class="text-slate-400">Environment:</span> <span class="inline-flex items-center font-semibold <?= $isDocker ? 'text-indigo-600' : 'text-amber-600' ?>"><?= $isDocker ? '🐳 Docker Container' : '💻 Local / XAMPP' ?></span></div>
            </div>

            <!-- Pesan Error Asli -->
            <div class="mb-5 rounded-xl bg-amber-50/80 p-4 border border-amber-200 text-xs text-amber-900">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Pesan Error Database:
                </div>
                <div class="font-mono text-slate-800 bg-white p-2.5 rounded-lg border border-amber-200/60 break-words text-[11px]">
                    <?= htmlspecialchars($dbError ?? 'Tidak dapat terhubung ke server MySQL.') ?>
                </div>
            </div>

            <?php if ($isDocker): ?>
                <!-- PANDUAN DOCKER SERVER -->
                <div class="space-y-3.5 text-xs text-slate-600 border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Solusi di Server Docker:
                    </div>

                    <?php if ($isAccessDenied): ?>
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 space-y-1.5">
                            <strong class="font-semibold block">Password atau Username Ditolak (Access Denied):</strong>
                            <p>Di Docker MariaDB, jika Anda mengubah <code>DB_PASSWORD</code> di <code>.env</code> setelah container pertama kali dibuat, MariaDB tetap menggunakan password lama yang tersimpan di volume <code>db_data</code>.</p>
                            <div class="mt-2 text-slate-700 bg-white p-2 rounded border border-rose-200 font-mono text-[11px]">
                                # Reset volume database agar password baru diterapkan (setup server baru):<br>
                                docker compose down -v && docker compose up -d
                            </div>
                        </div>
                    <?php elseif ($isConnectionRefused): ?>
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 space-y-1.5">
                            <strong class="font-semibold block">Koneksi Ditolak (Connection Refused):</strong>
                            <?php if ($currentHost === '127.0.0.1' || $currentHost === 'localhost'): ?>
                                <p class="text-rose-700 font-bold">⚠️ Perhatian: Di Docker, <code>DB_HOST</code> tidak boleh <code>127.0.0.1</code>! Ubah di file <code>.env</code> menjadi:</p>
                                <div class="bg-white p-2 rounded border border-amber-200 font-mono text-emerald-700 font-bold">
                                    DB_HOST=db
                                </div>
                            <?php else: ?>
                                <p>Container database MariaDB belum aktif atau masih dalam proses inisialisasi awal. Jalankan:</p>
                                <div class="bg-white p-2 rounded border border-amber-200 font-mono text-slate-800">
                                    docker compose up -d db && docker compose restart app
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <ol class="list-decimal pl-5 space-y-1.5">
                            <li>Pastikan file <code>.env</code> memiliki <code>DB_HOST=db</code> dan kredensial yang sesuai.</li>
                            <li>Periksa status container dengan perintah: <code class="bg-slate-200 px-1 py-0.5 rounded font-mono">docker compose ps</code></li>
                            <li>Jika baru pertama kali deploy, pastikan container <code>filesharing_db</code> berstatus <em>Up</em>.</li>
                        </ol>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- PANDUAN XAMPP LOKAL -->
                <div class="space-y-3 text-xs text-slate-600 border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                    <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Solusi Cepat di XAMPP:
                    </div>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Buka <strong>XAMPP Control Panel</strong> dan pastikan modul <strong>Apache</strong> serta <strong>MySQL</strong> dalam status <em>Running</em> (hijau).</li>
                        <li>Buka <a href="http://localhost/phpmyadmin" target="_blank" class="text-brand-600 font-semibold underline">phpMyAdmin</a> di browser Anda.</li>
                        <li>Buat database baru bernama <code class="bg-slate-200 px-1 py-0.5 rounded font-mono font-bold text-slate-800"><?= htmlspecialchars($currentDb) ?></code>.</li>
                        <li>Buka menu <strong>Import</strong>, lalu pilih berkas SQL skema:
                            <div class="mt-1 font-mono bg-white p-1.5 rounded border border-slate-200 text-slate-800 text-[11px] truncate" title="<?= htmlspecialchars($sqlFilePath) ?>">
                                <?= htmlspecialchars($sqlFilePath) ?>
                            </div>
                        </li>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="?action=repair_db" 
                   class="w-full sm:w-auto inline-flex items-center justify-center h-11 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Perbaiki & Inisialisasi Database Otomatis
                </a>
                <a href="index.php" 
                   class="w-full sm:w-auto inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Cek Ulang Koneksi
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
