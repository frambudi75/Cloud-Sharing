<?php
$pageTitle = 'Pengaturan Server & Webhook - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$diskTotal = $metrics['disk_total'] ?? 0;
$diskFree = $metrics['disk_free'] ?? 0;
$diskUsed = $metrics['disk_used'] ?? 0;
$diskPercent = $metrics['disk_percent'] ?? 0;
?>

<div class="min-h-full flex flex-col bg-slate-50" id="serverSettingsApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-lg shadow-sm group-hover:scale-105 transition-all duration-200">
                    <span class="font-bold text-slate-900 text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="font-semibold text-slate-700 text-sm">Pengaturan Server</span>
            </div>

            <!-- Header Navigation -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <a href="?action=users" 
                   class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Kelola Pengguna
                </a>

                <a href="?action=drive" 
                   class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Buka Drive Saya
                </a>

                <a href="?action=logout" class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-700 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- Header Title Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Pengaturan Server & Webhook</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau performa infrastruktur server, ruang disk fisik, dan konfigurasi webhook notifikasi otomatis.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                    <span class="w-2 h-2 rounded-full bg-brand-500 mr-2 animate-pulse"></span>
                    Admin Panel
                </span>
            </div>
        </div>

        <!-- Section 1: Physical Disk Space Overview -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 11h16M16 15h.01M16 8h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Kapasitas Partisi Disk Server Fisik</h2>
                        <p class="text-xs text-slate-500">Membaca ukuran partisi tempat penyimpanan data berkas pengguna disimpan secara fisik.</p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="flex items-center space-x-2">
                    <?php if ($metrics['upload_dir_writable']): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Storage Folder: Writable
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                            Storage Folder: Read-Only (Perlu chmod)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Disk Gauge / Progress Bar -->
            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between text-xs sm:text-sm font-semibold">
                    <span class="text-slate-700">Ruang Terpakai: <span class="font-bold text-slate-900"><?= StorageService::formatBytes($diskUsed) ?></span> (<?= $diskPercent ?>%)</span>
                    <span class="text-slate-500">Sisa Bebas: <span class="font-bold text-emerald-600"><?= StorageService::formatBytes($diskFree) ?></span></span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden border border-slate-200/60 p-0.5">
                    <div class="h-full rounded-full transition-all duration-500 <?= $diskPercent > 90 ? 'bg-red-500' : ($diskPercent > 75 ? 'bg-amber-500' : 'bg-gradient-to-r from-brand-500 to-indigo-600') ?>" 
                         style="width: <?= min(100, max(2, $diskPercent)) ?>%"></div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 gap-2 pt-1">
                    <span>Path Unggahan: <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]"><?= htmlspecialchars($metrics['upload_dir']) ?></code></span>
                    <span>Total Kapasitas Partisi: <strong class="text-slate-700"><?= StorageService::formatBytes($diskTotal) ?></strong></span>
                </div>

                <!-- Note explaining difference between user quota and server disk -->
                <div class="mt-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200/60 flex items-start space-x-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        <strong>Catatan:</strong> Kuota di sidebar (default 10 GB untuk admin) adalah <em>kuota pengguna</em> per akun yang dapat diatur di menu <a href="?action=users" class="font-semibold underline hover:text-amber-900">Kelola Pengguna</a>. Sedangkan angka di atas adalah kapasitas <em>hard drive / disk fisik server sesungguhnya</em>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Section 2: Server Hardware & Stack Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Runtime PHP -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">PHP Runtime</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-600">v<?= $metrics['php_version'] ?></span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Memory Limit:</span>
                        <span class="font-semibold text-slate-900"><?= $metrics['memory_limit'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Max Upload:</span>
                        <span class="font-semibold text-slate-900"><?= $metrics['upload_max_filesize'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Post Max Size:</span>
                        <span class="font-semibold text-slate-900"><?= $metrics['post_max_size'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Max Execution:</span>
                        <span class="font-semibold text-slate-900"><?= $metrics['max_execution_time'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Database MariaDB -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Database</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-600">MariaDB / MySQL</span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Versi:</span>
                        <span class="font-semibold text-slate-900 truncate max-w-[140px]" title="<?= htmlspecialchars($metrics['db_version']) ?>"><?= htmlspecialchars(substr($metrics['db_version'], 0, 15)) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Berkas:</span>
                        <span class="font-semibold text-slate-900"><?= number_format($metrics['total_files']) ?> berkas</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Pengguna:</span>
                        <span class="font-semibold text-slate-900"><?= number_format($metrics['total_users']) ?> akun</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Akumulasi File:</span>
                        <span class="font-semibold text-slate-900"><?= StorageService::formatBytes($metrics['total_user_storage']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Cache & Memory -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cache Engine</span>
                    <?php if ($metrics['redis_active']): ?>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700">Redis Aktif</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-500">Fallback Direct</span>
                    <?php endif; ?>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Status:</span>
                        <span class="font-semibold <?= $metrics['redis_active'] ? 'text-emerald-600' : 'text-slate-500' ?>">
                            <?= $metrics['redis_active'] ? 'Connected & Caching' : 'Standby / Disabled' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Driver:</span>
                        <span class="font-semibold text-slate-900">php-redis ext</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Manfaat:</span>
                        <span class="font-semibold text-slate-900">Session & File Cache</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Latency:</span>
                        <span class="font-semibold text-slate-900">&lt; 1 ms (In-Memory)</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Host & Web Server -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sistem Operasi</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700"><?= PHP_OS_FAMILY ?></span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Hostname:</span>
                        <span class="font-semibold text-slate-900 truncate max-w-[130px]"><?= htmlspecialchars($metrics['hostname']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Web Server:</span>
                        <span class="font-semibold text-slate-900 truncate max-w-[130px]"><?= htmlspecialchars($metrics['server_software']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu Server:</span>
                        <span class="font-semibold text-slate-900"><?= date('H:i:s T') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Protokol:</span>
                        <span class="font-semibold text-slate-900"><?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS (Secure)' : 'HTTP' ?></span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Section 3: Webhook Notification Settings -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Integrasi Notifikasi Webhook</h2>
                        <p class="text-xs text-slate-500">Kirim pemberitahuan otomatis ke Discord, Slack, atau server webhook kustom Anda.</p>
                    </div>
                </div>

                <!-- Webhook Status Pill -->
                <div id="webhookStatusBadge">
                    <?php if ($webhookEnabled): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                            Webhook Aktif
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                            <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>
                            Webhook Dinonaktifkan
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Body -->
            <form id="webhookForm" onsubmit="saveWebhook(event)" class="p-6 space-y-6">
                
                <!-- Toggle Webhook Active -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div>
                        <label for="webhook_enabled" class="text-sm font-bold text-slate-900 cursor-pointer">Aktifkan Notifikasi Webhook</label>
                        <p class="text-xs text-slate-500">Nyalakan sakelar ini agar sistem mengirim payload HTTP POST ke URL tujuan saat event terjadi.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="webhook_enabled" name="webhook_enabled" value="1" <?= $webhookEnabled ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>

                <!-- Webhook URL Input -->
                <div class="space-y-1.5">
                    <label for="webhook_url" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        URL Webhook Endpoint (Discord / Slack / Generic) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="url" id="webhook_url" name="webhook_url" 
                               value="<?= htmlspecialchars($webhookUrl) ?>" 
                               placeholder="https://discord.com/api/webhooks/123456789/abcdefghijk..." 
                               class="w-full h-11 px-3.5 text-sm rounded-xl border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 font-mono transition-all">
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Format universal: Kompatibel langsung dengan Discord Channel Webhook, Slack Incoming Webhooks, dan bot HTTP server lainnya.
                    </p>
                </div>

                <!-- Event Triggers Checkboxes -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Pilih Event Pemicu Notifikasi (Trigger Events)
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        
                        <!-- Upload File Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-slate-50/50 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="upload" <?= in_array('upload', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 block">📤 Berkas Diunggah</span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Kirim saat ada pengguna berhasil mengunggah berkas baru.</span>
                            </div>
                        </label>

                        <!-- Download File Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-slate-50/50 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="download" <?= in_array('download', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 block">📥 Berkas Diunduh</span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Kirim saat berkas diunduh langsung atau via tautan publik.</span>
                            </div>
                        </label>

                        <!-- User Login Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-slate-50/50 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="login" <?= in_array('login', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 block">🔐 Pengguna Masuk</span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Kirim saat ada pengguna atau admin yang berhasil login.</span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- Feedback Toast / Message Box -->
                <div id="webhookAlert" class="hidden p-3.5 rounded-xl text-xs font-medium"></div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="testWebhook()" id="testBtn"
                            class="w-full sm:w-auto inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-all">
                        <svg class="w-4 h-4 mr-1.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Kirim Notifikasi Tes (Test Connection)</span>
                    </button>

                    <button type="submit" id="saveBtn"
                            class="w-full sm:w-auto inline-flex items-center justify-center h-10 px-5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs shadow-brand-500/20 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Simpan Pengaturan Webhook</span>
                    </button>
                </div>

            </form>
        </div>

        <!-- Documentation Card: Panduan Singkat -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center">
                <svg class="w-4 h-4 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Cara Mendapatkan URL Webhook Discord & Slack
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600 leading-relaxed">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                    <strong class="text-slate-900 block mb-1">🎮 Discord:</strong>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600">
                        <li>Buka <em>Server Settings</em> atau <em>Edit Channel</em> di Discord.</li>
                        <li>Pilih menu <strong>Integrations</strong> &rarr; klik <strong>Webhooks</strong>.</li>
                        <li>Klik <strong>New Webhook</strong>, tentukan channel, lalu klik <strong>Copy Webhook URL</strong>.</li>
                        <li>Tempelkan tautan ke kolom URL di atas lalu klik tes notifikasi.</li>
                    </ol>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                    <strong class="text-slate-900 block mb-1">💬 Slack / Lainnya:</strong>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600">
                        <li>Buka menu <em>Apps</em> di workspace Slack Anda &rarr; cari <strong>Incoming WebHooks</strong>.</li>
                        <li>Pilih channel tujuan dan klik <strong>Add Incoming WebHooks Integration</strong>.</li>
                        <li>Salin <strong>Webhook URL</strong> yang diberikan dan tempel di formulir atas.</li>
                        <li>Payload JSON menyertakan field <code>text</code> dan rich <code>embeds</code> otomatis.</li>
                    </ol>
                </div>
            </div>
        </div>

    </main>

</div>

<script>
const csrfToken = document.getElementById('serverSettingsApp').dataset.csrf;

function showAlert(type, message) {
    const box = document.getElementById('webhookAlert');
    box.className = 'p-3.5 rounded-xl text-xs font-medium ' + (type === 'success' 
        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' 
        : 'bg-red-50 text-red-800 border border-red-200');
    box.innerHTML = message;
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function saveWebhook(e) {
    e.preventDefault();
    const saveBtn = document.getElementById('saveBtn');
    const origHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="animate-spin mr-1">⌛</span> Menyimpan...';

    const form = document.getElementById('webhookForm');
    const formData = new FormData(form);
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=save_webhook', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showAlert('success', '✓ ' + (json.message || 'Pengaturan webhook berhasil disimpan.'));
            const isEnabled = document.getElementById('webhook_enabled').checked;
            const badge = document.getElementById('webhookStatusBadge');
            badge.innerHTML = isEnabled 
                ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>Webhook Aktif</span>'
                : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200"><span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>Webhook Dinonaktifkan</span>';
        } else {
            showAlert('error', '✗ ' + (json.error || 'Gagal menyimpan pengaturan webhook.'));
        }
    } catch (err) {
        showAlert('error', '✗ Terjadi kesalahan koneksi atau server tidak merespons.');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = origHtml;
    }
}

async function testWebhook() {
    const testBtn = document.getElementById('testBtn');
    const urlInput = document.getElementById('webhook_url');
    const targetUrl = urlInput.value.trim();

    if (!targetUrl) {
        showAlert('error', '✗ Masukkan URL webhook terlebih dahulu sebelum mengirim tes.');
        urlInput.focus();
        return;
    }

    const origHtml = testBtn.innerHTML;
    testBtn.disabled = true;
    testBtn.innerHTML = '<span class="animate-spin mr-1">⚡</span> Mengirim tes...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('webhook_url', targetUrl);

    try {
        const res = await fetch('?action=test_webhook', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showAlert('success', '✓ ' + (json.message || 'Notifikasi tes berhasil dikirim! Silakan periksa channel Anda.'));
        } else {
            showAlert('error', '✗ ' + (json.error || 'Gagal mengirim webhook. Pastikan URL endpoint valid.'));
        }
    } catch (err) {
        showAlert('error', '✗ Gagal menghubungi server pengujian webhook.');
    } finally {
        testBtn.disabled = false;
        testBtn.innerHTML = origHtml;
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
