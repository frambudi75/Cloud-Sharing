<?php
$pageTitle = 'Pengaturan Server & Webhook - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$diskTotal = $metrics['disk_total'] ?? 0;
$diskFree = $metrics['disk_free'] ?? 0;
$diskUsed = $metrics['disk_used'] ?? 0;
$diskPercent = $metrics['disk_percent'] ?? 0;
$maintenanceActive = $globalSettings['maintenance_mode'] ?? false;
?>

<div class="min-h-full relative flex flex-col bg-slate-50 dark:bg-slate-950 overflow-x-hidden text-slate-800 dark:text-slate-100" id="serverSettingsApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Ambient Texture Grid (Anti-Slop Modern Texture) -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_30%,#000_60%,transparent_100%)] opacity-35 pointer-events-none"></div>

    <!-- Top Navigation Bar -->
    <header class="relative z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-lg shadow-sm group-hover:scale-105 transition-all duration-200">
                    <span class="font-bold text-slate-900 dark:text-white text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="font-semibold text-slate-700 dark:text-slate-200 text-sm">Pengaturan Server</span>
            </div>

            <!-- Header Navigation -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <a href="?action=about" 
                   class="inline-flex items-center h-9 px-3 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors" title="Tentang Aplikasi & Pengembang">
                    <svg class="w-4 h-4 sm:mr-1.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="hidden sm:inline">Tentang Aplikasi</span>
                </a>

                <a href="?action=users" 
                   class="inline-flex items-center h-9 px-3 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors" title="Kelola Pengguna">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="hidden sm:inline">Kelola Pengguna</span>
                </a>

                <a href="?action=drive" 
                   class="inline-flex items-center h-9 px-3 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors" title="Buka Drive Saya">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="hidden sm:inline">Buka Drive Saya</span>
                </a>

                <a href="?action=logout" class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-300 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 border border-slate-200 dark:border-slate-700 hover:border-red-200 transition-colors" title="Keluar">
                    <span class="hidden sm:inline">Keluar</span>
                    <svg class="w-4 h-4 sm:hidden text-slate-500 hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- Maintenance Alert Banner (If Active) -->
        <div id="maintenanceNoticeBanner" class="<?= $maintenanceActive ? '' : 'hidden' ?> p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-200 flex items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center space-x-3">
                <span class="text-2xl animate-pulse">⚠️</span>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold">Mode Pemeliharaan (Maintenance Mode) Sedang Aktif</h3>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">Pengguna non-admin saat ini dialihkan ke layar pemeliharaan sistem. Anda dapat mematikannya di formulir Pengaturan Global di bawah.</p>
                </div>
            </div>
            <a href="#globalSettingsCard" class="shrink-0 px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-xs">
                Atur Mode →
            </a>
        </div>

        <!-- Header Title Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Pengaturan Server, Webhook & Pemeliharaan</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Pantau performa infrastruktur server, ruang disk fisik, konfigurasi global, audit trail, dan webhook notifikasi.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-950/60 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                    <span class="w-2 h-2 rounded-full bg-brand-500 mr-2 animate-pulse"></span>
                    Admin Panel
                </span>
            </div>
        </div>

        <!-- Section 1: Physical Disk Space & Realtime System Load -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left (2 Cols): Disk Space Overview -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 11h16M16 15h.01M16 8h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900 dark:text-white">Kapasitas Partisi Disk Server Fisik</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Ukuran partisi tempat berkas pengguna disimpan secara fisik di storage.</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-2">
                            <?php if ($metrics['upload_dir_writable']): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Storage: Writable
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                    Storage: Read-Only
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Disk Gauge / Progress Bar -->
                    <div class="mt-5 space-y-2.5">
                        <div class="flex items-center justify-between text-xs sm:text-sm font-semibold">
                            <span class="text-slate-700 dark:text-slate-300">Ruang Terpakai: <span class="font-bold text-slate-900 dark:text-white"><?= StorageService::formatBytes($diskUsed) ?></span> (<?= $diskPercent ?>%)</span>
                            <span class="text-slate-500 dark:text-slate-400">Sisa Bebas: <span class="font-bold text-emerald-600 dark:text-emerald-400"><?= StorageService::formatBytes($diskFree) ?></span></span>
                        </div>

                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-4 overflow-hidden border border-slate-200/60 dark:border-slate-700 p-0.5">
                            <div class="h-full rounded-full transition-all duration-500 <?= $diskPercent > 90 ? 'bg-red-500' : ($diskPercent > 75 ? 'bg-amber-500' : 'bg-gradient-to-r from-brand-500 to-indigo-600') ?>" 
                                 style="width: <?= min(100, max(2, $diskPercent)) ?>%"></div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 dark:text-slate-500 gap-2 pt-1 font-mono">
                            <span class="truncate">Path: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px]"><?= htmlspecialchars($metrics['upload_dir']) ?></code></span>
                            <span>Total: <strong class="text-slate-700 dark:text-slate-300"><?= StorageService::formatBytes($diskTotal) ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Berkas Chunk Terbengkalai: <strong class="text-slate-800 dark:text-slate-200 font-mono"><?= $metrics['chunk_dirs'] ?> folder (<?= StorageService::formatBytes($metrics['chunk_bytes']) ?>)</strong></span>
                    <button type="button" onclick="cleanChunks()" class="text-brand-600 dark:text-brand-400 font-bold hover:underline">Bersihkan Sekarang →</button>
                </div>
            </div>

            <!-- Right (1 Col): CPU & RAM Metrics -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-sm">
                            ⚡
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Beban CPU & RAM</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                        <?= $metrics['uptime'] ?>
                    </span>
                </div>

                <!-- CPU Load Average -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">CPU Load (1m, 5m, 15m):</span>
                        <span class="font-mono text-slate-900 dark:text-white font-bold">
                            <?= $metrics['cpu_load']['1m'] ?>, <?= $metrics['cpu_load']['5m'] ?>, <?= $metrics['cpu_load']['15m'] ?>
                        </span>
                    </div>
                    <div class="flex items-center space-x-1.5 text-[11px] text-slate-400">
                        <span class="inline-block w-2 h-2 rounded-full <?= $metrics['cpu_load']['1m'] > 4.0 ? 'bg-red-500' : ($metrics['cpu_load']['1m'] > 2.0 ? 'bg-amber-500' : 'bg-emerald-500') ?>"></span>
                        <span><?= $metrics['cpu_load']['1m'] > 4.0 ? 'Beban Tinggi' : ($metrics['cpu_load']['1m'] > 2.0 ? 'Beban Sedang' : 'Normal / Responsif') ?></span>
                    </div>
                </div>

                <!-- Physical RAM Usage -->
                <div class="space-y-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Penggunaan RAM:</span>
                        <span class="font-mono text-slate-900 dark:text-white font-bold">
                            <?= StorageService::formatBytes($metrics['ram_used']) ?> / <?= StorageService::formatBytes($metrics['ram_total']) ?>
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden border border-slate-200/60 dark:border-slate-700">
                        <div class="h-full rounded-full transition-all duration-500 <?= $metrics['ram_percent'] > 85 ? 'bg-red-500' : ($metrics['ram_percent'] > 70 ? 'bg-amber-500' : 'bg-teal-500') ?>"
                             style="width: <?= min(100, max(3, $metrics['ram_percent'])) ?>%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                        <span>Bebas: <?= StorageService::formatBytes($metrics['ram_free']) ?></span>
                        <span><?= $metrics['ram_percent'] ?>%</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Section 2: Server Hardware & Stack Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Runtime PHP -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">PHP Runtime</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">v<?= $metrics['php_version'] ?></span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Memory Limit:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= $metrics['memory_limit'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Max Upload:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= $metrics['upload_max_filesize'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Post Max Size:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= $metrics['post_max_size'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Max Execution:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= $metrics['max_execution_time'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Database MariaDB -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Database</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">MariaDB / MySQL</span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Versi:</span>
                        <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[140px]" title="<?= htmlspecialchars($metrics['db_version']) ?>"><?= htmlspecialchars(substr($metrics['db_version'], 0, 15)) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Berkas:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= number_format($metrics['total_files']) ?> berkas</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Pengguna:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= number_format($metrics['total_users']) ?> akun</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Akumulasi File:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= StorageService::formatBytes($metrics['total_user_storage']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Cache & Memory -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cache Engine</span>
                    <?php if ($metrics['redis_active']): ?>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">Redis Aktif</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500">Fallback Direct</span>
                    <?php endif; ?>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Status:</span>
                        <span class="font-semibold <?= $metrics['redis_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                            <?= $metrics['redis_active'] ? 'Connected & Caching' : 'Standby / Direct' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Driver:</span>
                        <span class="font-semibold text-slate-900 dark:text-white">php-redis ext</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Namespace:</span>
                        <span class="font-mono font-semibold text-slate-900 dark:text-white">fileshare:*</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Latency:</span>
                        <span class="font-semibold text-slate-900 dark:text-white">&lt; 1 ms (Memory)</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Host & Web Server -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sistem Operasi</span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"><?= PHP_OS_FAMILY ?></span>
                </div>
                <div class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Hostname:</span>
                        <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[130px]"><?= htmlspecialchars($metrics['hostname']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Web Server:</span>
                        <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[130px]"><?= htmlspecialchars($metrics['server_software']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu Server:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= date('H:i:s T') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Protokol:</span>
                        <span class="font-semibold text-slate-900 dark:text-white"><?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS (Secure)' : 'HTTP' ?></span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Section 3: Alat Pemeliharaan & Optimasi Server (Maintenance Tools) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 space-y-5">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                    🛠️
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Alat Pemeliharaan & Optimasi Server</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Jalankan tugas pembersihan otomatis, optimasi tabel database, dan uji kecepatan disk.</p>
                </div>
            </div>

            <!-- Maintenance Action Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Tool 1: Clean Orphaned Chunks -->
                <div class="p-4 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center">
                                🧹 Bersihkan Chunk
                            </span>
                            <span class="text-[10px] font-mono font-bold text-slate-400" id="chunkBadge"><?= $metrics['chunk_dirs'] ?> folder</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Hapus seluruh potongan unggahan sementara (resumable chunks) yang terbengkalai.
                        </p>
                    </div>
                    <button type="button" onclick="cleanChunks()" id="btnCleanChunks"
                            class="w-full h-9 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all shadow-2xs">
                        Bersihkan Chunk
                    </button>
                </div>

                <!-- Tool 2: Purge Cache -->
                <div class="p-4 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center">
                                ⚡ Bersihkan Cache
                            </span>
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"><?= $metrics['redis_active'] ? 'Redis OK' : 'Direct' ?></span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Hapus seluruh cache Redis (<code class="text-[10px] font-mono">fileshare:*</code>) dan sinkronkan data terbaru.
                        </p>
                    </div>
                    <button type="button" onclick="purgeCache()" id="btnPurgeCache"
                            class="w-full h-9 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all shadow-2xs">
                        Bersihkan Cache
                    </button>
                </div>

                <!-- Tool 3: Optimize DB -->
                <div class="p-4 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center">
                                🗄️ Optimasi Database
                            </span>
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">8 Tabel</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Jalankan defragmentasi indeks & optimasi ruang penyimpanan tabel MariaDB.
                        </p>
                    </div>
                    <button type="button" onclick="optimizeDatabase()" id="btnOptimizeDb"
                            class="w-full h-9 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all shadow-2xs">
                        Optimalkan DB
                    </button>
                </div>

                <!-- Tool 4: Disk Benchmark -->
                <div class="p-4 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center">
                                ⏱️ Benchmark Disk
                            </span>
                            <span class="text-[10px] font-mono font-bold text-purple-600 dark:text-purple-400" id="benchBadge">10 MB Uji</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed" id="benchDesc">
                            Ukur kecepatan tulis & baca partisi penyimpanan secara langsung.
                        </p>
                    </div>
                    <button type="button" onclick="benchmarkDisk()" id="btnBenchmark"
                            class="w-full h-9 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all shadow-2xs">
                        Mulai Benchmark
                    </button>
                </div>

            </div>

            <!-- Maintenance Feedback Alert Box -->
            <div id="maintenanceToolAlert" class="hidden p-3.5 rounded-xl text-xs font-medium"></div>
        </div>

        <!-- Section 4: Pengaturan Global Aplikasi (System Global Settings) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden" id="globalSettingsCard">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 flex items-center justify-center font-bold">
                        ⚙️
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Pengaturan Global Aplikasi</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Atur kuota bawaan untuk akun baru, masa berlaku tautan publik, serta sakelar mode pemeliharaan.</p>
                    </div>
                </div>
            </div>

            <form id="globalSettingsForm" onsubmit="saveGlobalSettings(event)" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Default Quota Input -->
                    <div class="space-y-1.5">
                        <label for="default_user_quota_gb" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Default Kuota Akun Baru (Gigabyte)
                        </label>
                        <div class="relative">
                            <input type="number" step="0.5" min="0.5" max="1000" id="default_user_quota_gb" name="default_user_quota_gb" 
                                   value="<?= htmlspecialchars((string)$globalSettings['default_user_quota_gb']) ?>" 
                                   class="w-full h-11 px-3.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all">
                            <span class="absolute right-3.5 top-3 text-xs font-bold text-slate-400">GB</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Alokasi kuota bawaan otomatis saat Administrator membuat pengguna baru.</p>
                    </div>

                    <!-- Default Share Link Expiry -->
                    <div class="space-y-1.5">
                        <label for="default_share_expiry" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Default Masa Berlaku Tautan Publik
                        </label>
                        <select id="default_share_expiry" name="default_share_expiry" 
                                class="w-full h-11 px-3.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all">
                            <option value="never" <?= $globalSettings['default_share_expiry'] === 'never' ? 'selected' : '' ?>>Selamanya (Tanpa Batas Waktu)</option>
                            <option value="1d" <?= $globalSettings['default_share_expiry'] === '1d' ? 'selected' : '' ?>>1 Hari</option>
                            <option value="7d" <?= $globalSettings['default_share_expiry'] === '7d' ? 'selected' : '' ?>>7 Hari</option>
                            <option value="30d" <?= $globalSettings['default_share_expiry'] === '30d' ? 'selected' : '' ?>>30 Hari</option>
                        </select>
                        <p class="text-[11px] text-slate-400">Pilihan masa aktif yang otomatis terpilih saat pengguna membagikan berkas.</p>
                    </div>
                </div>

                <!-- Maintenance Mode Switch -->
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="maintenance_mode" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer flex items-center">
                                <span>Mode Pemeliharaan (Maintenance Mode)</span>
                                <?php if ($maintenanceActive): ?>
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 animate-pulse">AKTIF</span>
                                <?php endif; ?>
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Saat aktif, seluruh pengguna biasa akan melihat halaman pemeliharaan. Hanya Administrator yang dapat mengakses Drive.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= $maintenanceActive ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>

                    <!-- Custom Maintenance Message -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                        <label for="maintenance_message" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Pesan Pengumuman Pemeliharaan (Ditampilkan ke Pengguna)
                        </label>
                        <textarea id="maintenance_message" name="maintenance_message" rows="2" 
                                  placeholder="Sistem Cloud Sharing sedang dalam proses pemeliharaan rutin..." 
                                  class="w-full p-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all"><?= htmlspecialchars($globalSettings['maintenance_message']) ?></textarea>
                    </div>
                </div>

                <!-- Global Settings Alert Box -->
                <div id="globalSettingsAlert" class="hidden p-3.5 rounded-xl text-xs font-medium"></div>

                <!-- Save Button -->
                <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" id="btnSaveGlobal"
                            class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs shadow-brand-500/20 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Simpan Pengaturan Global</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Section 5: Webhook Notification Settings & Delivery History -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Integrasi Notifikasi Webhook</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kirim pemberitahuan otomatis ke Discord, Slack, atau server webhook kustom Anda.</p>
                    </div>
                </div>

                <!-- Webhook Status Pill -->
                <div id="webhookStatusBadge">
                    <?php if ($webhookEnabled): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                            Webhook Aktif
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                            <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>
                            Webhook Dinonaktifkan
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Body -->
            <form id="webhookForm" onsubmit="saveWebhook(event)" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <!-- Toggle Webhook Active -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800">
                    <div>
                        <label for="webhook_enabled" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer">Aktifkan Notifikasi Webhook</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Nyalakan sakelar ini agar sistem mengirim payload HTTP POST ke URL tujuan saat event terjadi.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="webhook_enabled" name="webhook_enabled" value="1" <?= $webhookEnabled ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>

                <!-- Webhook URL Input -->
                <div class="space-y-1.5">
                    <label for="webhook_url" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        URL Webhook Endpoint (Discord / Slack / Generic) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="url" id="webhook_url" name="webhook_url" 
                               value="<?= htmlspecialchars($webhookUrl) ?>" 
                               placeholder="https://discord.com/api/webhooks/123456789/abcdefghijk..." 
                               class="w-full h-11 px-3.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 font-mono transition-all">
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Format universal: Kompatibel langsung dengan Discord Channel Webhook, Slack Incoming Webhooks, dan bot HTTP server lainnya.
                    </p>
                </div>

                <!-- Event Triggers Checkboxes -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        Pilih Event Pemicu Notifikasi (Trigger Events)
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        
                        <!-- Upload File Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-brand-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/60 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="upload" <?= in_array('upload', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">📤 Berkas Diunggah</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5">Kirim saat ada pengguna berhasil mengunggah berkas baru.</span>
                            </div>
                        </label>

                        <!-- Download File Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-brand-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/60 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="download" <?= in_array('download', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">📥 Berkas Diunduh</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5">Kirim saat berkas diunduh langsung atau via tautan publik.</span>
                            </div>
                        </label>

                        <!-- User Login Trigger -->
                        <label class="flex items-start p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-brand-300 hover:bg-slate-50/50 dark:hover:bg-slate-800/60 cursor-pointer transition-all">
                            <input type="checkbox" name="events[]" value="login" <?= in_array('login', $savedEvents, true) ? 'checked' : '' ?> 
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">🔐 Pengguna Masuk</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5">Kirim saat ada pengguna atau admin yang berhasil login.</span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- Feedback Toast / Message Box -->
                <div id="webhookAlert" class="hidden p-3.5 rounded-xl text-xs font-medium"></div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="testWebhook()" id="testBtn"
                            class="w-full sm:w-auto inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-all">
                        <svg class="w-4 h-4 mr-1.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <!-- Sub-Section: Recent Webhook Deliveries Table -->
            <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Riwayat Pengiriman Webhook Terakhir
                    </h3>
                    <span class="text-[11px] text-slate-400">Menyimpan hingga 10 pengiriman terakhir</span>
                </div>

                <?php if (empty($webhookDeliveries)): ?>
                    <p class="text-xs text-slate-400 italic py-2">Belum ada riwayat pengiriman webhook yang tercatat.</p>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400 tracking-wider bg-slate-50/60 dark:bg-slate-800/50">
                                    <th class="py-2.5 px-3.5">Waktu</th>
                                    <th class="py-2.5 px-3.5">Event</th>
                                    <th class="py-2.5 px-3.5">Endpoint Masked</th>
                                    <th class="py-2.5 px-3.5">Status</th>
                                    <th class="py-2.5 px-3.5 text-right">Latensi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach ($webhookDeliveries as $del): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                        <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                            <?= htmlspecialchars($del['timestamp'] ?? '-') ?>
                                        </td>
                                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($del['event'] ?? 'event') ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-600 dark:text-slate-400 truncate max-w-xs">
                                            <?= htmlspecialchars($del['url_masked'] ?? '-') ?>
                                        </td>
                                        <td class="py-2.5 px-3.5 whitespace-nowrap">
                                            <?php if (!empty($del['success'])): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                                    HTTP <?= (int)($del['http_code'] ?? 200) ?> OK
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                                                    HTTP <?= (int)($del['http_code'] ?? 0) ?> Gagal
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2.5 px-3.5 text-right font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                            <?= (int)($del['duration_ms'] ?? 0) ?> ms
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 6: Audit Trail Global Administrator -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden space-y-4 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold">
                        📜
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Audit Trail Global Seluruh Pengguna</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                                <?= number_format($globalActivityTotal) ?> Catatan
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pantau seluruh riwayat unduhan, unggahan, dan aktivitas seluruh pengguna sistem.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" id="globalSearchInput" placeholder="Cari nama / user / IP..." onkeyup="if(event.key==='Enter') loadGlobalLogs(1)"
                           class="h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500 w-full sm:w-48">
                    <button type="button" onclick="loadGlobalLogs(1)" class="h-9 px-3.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                        Cari
                    </button>
                </div>
            </div>

            <!-- Global Logs Table Container -->
            <div id="globalLogsContainer" class="overflow-x-auto rounded-xl border border-slate-200/70 dark:border-slate-800">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400 tracking-wider bg-slate-50/70 dark:bg-slate-800/50">
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Tindakan</th>
                            <th class="py-3 px-4">Berkas & Detail</th>
                            <th class="py-3 px-4">IP & Perangkat</th>
                            <th class="py-3 px-4 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="globalLogsTableBody">
                        <?php if (empty($globalActivityLogs)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">Belum ada rekaman aktivitas pengguna.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($globalActivityLogs as $glog): ?>
                                <?php 
                                $gbadge = ActivityLogService::getActionBadge($glog['action']); 
                                $gdev = ActivityLogService::parseDevice($glog['user_agent']);
                                ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xs">
                                                <?= strtoupper(substr($glog['username'] ?? 'U', 0, 1)) ?>
                                            </span>
                                            <div>
                                                <span class="font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($glog['username'] ?? 'User') ?></span>
                                                <span class="text-[10px] text-slate-400 uppercase font-mono"><?= htmlspecialchars($glog['role'] ?? 'user') ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold <?= $gbadge['color'] ?>">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $gbadge['dot'] ?>"></span>
                                            <?= $gbadge['label'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 max-w-xs truncate">
                                            <?= htmlspecialchars($glog['file_name']) ?>
                                        </div>
                                        <?php if (!empty($glog['details'])): ?>
                                            <div class="text-[11px] text-slate-400 mt-0.5 max-w-sm truncate">
                                                <?= htmlspecialchars($glog['details']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                            <?= htmlspecialchars($glog['ip_address']) ?>
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            <?= htmlspecialchars($gdev['device']) ?> &bull; <?= htmlspecialchars($gdev['browser']) ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200" title="<?= $glog['created_at'] ?>">
                                            <?= ActivityLogService::formatTimeAgo($glog['created_at']) ?>
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                            <?= date('d M Y, H:i', strtotime($glog['created_at'])) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 7: Panduan Webhook Discord & Slack -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center">
                <svg class="w-4 h-4 mr-2 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Cara Mendapatkan URL Webhook Discord & Slack
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                    <strong class="text-slate-900 dark:text-white block mb-1">🎮 Discord:</strong>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600 dark:text-slate-400">
                        <li>Buka <em>Server Settings</em> atau <em>Edit Channel</em> di Discord.</li>
                        <li>Pilih menu <strong>Integrations</strong> &rarr; klik <strong>Webhooks</strong>.</li>
                        <li>Klik <strong>New Webhook</strong>, tentukan channel, lalu klik <strong>Copy Webhook URL</strong>.</li>
                        <li>Tempelkan tautan ke kolom URL di atas lalu klik tes notifikasi.</li>
                    </ol>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                    <strong class="text-slate-900 dark:text-white block mb-1">💬 Slack / Lainnya:</strong>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600 dark:text-slate-400">
                        <li>Buka menu <em>Apps</em> di workspace Slack Anda &rarr; cari <strong>Incoming WebHooks</strong>.</li>
                        <li>Pilih channel tujuan dan klik <strong>Add Incoming WebHooks Integration</strong>.</li>
                        <li>Salin <strong>Webhook URL</strong> yang diberikan dan tempel di formulir atas.</li>
                        <li>Payload JSON menyertakan field <code>text</code> dan rich <code>embeds</code> otomatis.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Section 8: Informasi Aplikasi & Pengembang (Habib Frambudi @frambudi75) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-3.5">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 text-white font-extrabold text-base flex items-center justify-center shrink-0 shadow-sm">
                        HF
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Informasi Aplikasi & Pengembang</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 dark:bg-brand-950/60 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800">v1.4.0</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Dikembangkan oleh <strong class="text-slate-800 dark:text-slate-200">Habib Frambudi</strong> (<code class="font-mono text-brand-600 dark:text-brand-400 font-bold">@frambudi75</code>) — Full-Stack Developer & System Architect.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="https://github.com/frambudi75" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold shadow-2xs transition-all">
                        <svg class="w-4 h-4 mr-1.5 fill-current" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                        </svg>
                        GitHub @frambudi75
                    </a>
                    <a href="https://github.com/frambudi75/Cloud-Sharing" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold shadow-2xs transition-all">
                        Repo Cloud-Sharing ↗
                    </a>
                    <a href="?action=about"
                       class="inline-flex items-center h-9 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-all">
                        Buka Halaman About Lengkap →
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-slate-400 block mb-0.5">Nama Aplikasi</span>
                    <strong class="text-slate-900 dark:text-white">Cloud Sharing Drive (Self-Hosted)</strong>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-slate-400 block mb-0.5">Pengembang Utama</span>
                    <strong class="text-slate-900 dark:text-white">Habib Frambudi (@frambudi75)</strong>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-slate-400 block mb-0.5">Repository Resmi</span>
                    <a href="https://github.com/frambudi75/Cloud-Sharing" target="_blank" rel="noopener noreferrer" class="font-mono font-bold text-brand-600 dark:text-brand-400 hover:underline">github.com/frambudi75/Cloud-Sharing</a>
                </div>
            </div>
        </div>

    </main>

</div>

<script>
const csrfToken = document.getElementById('serverSettingsApp').dataset.csrf;

function showMaintenanceAlert(type, message) {
    const box = document.getElementById('maintenanceToolAlert');
    box.className = 'p-3.5 rounded-xl text-xs font-medium ' + (type === 'success' 
        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' 
        : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800');
    box.innerHTML = message;
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showWebhookAlert(type, message) {
    const box = document.getElementById('webhookAlert');
    box.className = 'p-3.5 rounded-xl text-xs font-medium ' + (type === 'success' 
        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' 
        : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800');
    box.innerHTML = message;
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showGlobalAlert(type, message) {
    const box = document.getElementById('globalSettingsAlert');
    box.className = 'p-3.5 rounded-xl text-xs font-medium ' + (type === 'success' 
        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' 
        : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800');
    box.innerHTML = message;
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 1. Tool: Clean Orphaned Chunks
async function cleanChunks() {
    const btn = document.getElementById('btnCleanChunks');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ Membersihkan...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=clean_chunks', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showMaintenanceAlert('success', '✓ ' + json.message);
            document.getElementById('chunkBadge').innerText = '0 folder';
        } else {
            showMaintenanceAlert('error', '✗ ' + (json.error || 'Gagal membersihkan chunk.'));
        }
    } catch (e) {
        showMaintenanceAlert('error', '✗ Terjadi kesalahan koneksi saat membersihkan chunk.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// 2. Tool: Purge Cache
async function purgeCache() {
    const btn = document.getElementById('btnPurgeCache');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ Membersihkan...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=purge_cache', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showMaintenanceAlert('success', '✓ ' + json.message);
        } else {
            showMaintenanceAlert('error', '✗ ' + (json.error || 'Gagal membersihkan cache.'));
        }
    } catch (e) {
        showMaintenanceAlert('error', '✗ Terjadi kesalahan koneksi saat membersihkan cache.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// 3. Tool: Optimize Database
async function optimizeDatabase() {
    const btn = document.getElementById('btnOptimizeDb');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ Mengoptimalkan...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=optimize_db', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showMaintenanceAlert('success', '✓ ' + json.message);
        } else {
            showMaintenanceAlert('error', '✗ ' + (json.error || 'Gagal optimasi database.'));
        }
    } catch (e) {
        showMaintenanceAlert('error', '✗ Terjadi kesalahan koneksi saat optimasi database.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// 4. Tool: Benchmark Disk
async function benchmarkDisk() {
    const btn = document.getElementById('btnBenchmark');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ Menguji...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=benchmark_disk', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            document.getElementById('benchBadge').innerText = json.write_speed;
            document.getElementById('benchDesc').innerHTML = `Tulis: <strong>${json.write_speed}</strong> | Baca: <strong>${json.read_speed}</strong> (Waktu: ${json.total_time_ms} ms)`;
            showMaintenanceAlert('success', `✓ Uji Benchmark Selesai! Kecepatan Tulis: <strong>${json.write_speed}</strong>, Kecepatan Baca: <strong>${json.read_speed}</strong> (Durasi: ${json.total_time_ms} ms).`);
        } else {
            showMaintenanceAlert('error', '✗ ' + (json.error || 'Gagal benchmark disk.'));
        }
    } catch (e) {
        showMaintenanceAlert('error', '✗ Terjadi kesalahan saat pengujian kecepatan disk.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// 5. Global Settings Form
async function saveGlobalSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveGlobal');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⌛ Menyimpan...';

    const form = document.getElementById('globalSettingsForm');
    const formData = new FormData(form);
    formData.append('csrf_token', csrfToken);

    try {
        const res = await fetch('?action=save_system_settings', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showGlobalAlert('success', '✓ ' + json.message);
            const noticeBanner = document.getElementById('maintenanceNoticeBanner');
            if (json.maintenance_mode) {
                noticeBanner.classList.remove('hidden');
            } else {
                noticeBanner.classList.add('hidden');
            }
        } else {
            showGlobalAlert('error', '✗ ' + (json.error || 'Gagal menyimpan pengaturan global.'));
        }
    } catch (e) {
        showGlobalAlert('error', '✗ Terjadi kesalahan koneksi ke server.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}

// 6. Webhook Settings Form
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
            showWebhookAlert('success', '✓ ' + (json.message || 'Pengaturan webhook berhasil disimpan.'));
            const isEnabled = document.getElementById('webhook_enabled').checked;
            const badge = document.getElementById('webhookStatusBadge');
            badge.innerHTML = isEnabled 
                ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"><span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>Webhook Aktif</span>'
                : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700"><span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span>Webhook Dinonaktifkan</span>';
        } else {
            showWebhookAlert('error', '✗ ' + (json.error || 'Gagal menyimpan pengaturan webhook.'));
        }
    } catch (err) {
        showWebhookAlert('error', '✗ Terjadi kesalahan koneksi atau server tidak merespons.');
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
        showWebhookAlert('error', '✗ Masukkan URL webhook terlebih dahulu sebelum mengirim tes.');
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
            showWebhookAlert('success', '✓ ' + (json.message || 'Notifikasi tes berhasil dikirim! Silakan periksa channel Anda.'));
        } else {
            showWebhookAlert('error', '✗ ' + (json.error || 'Gagal mengirim webhook. Pastikan URL endpoint valid.'));
        }
    } catch (err) {
        showWebhookAlert('error', '✗ Gagal menghubungi server pengujian webhook.');
    } finally {
        testBtn.disabled = false;
        testBtn.innerHTML = origHtml;
    }
}

// 7. Global Logs Fetcher
async function loadGlobalLogs(page = 1) {
    const search = document.getElementById('globalSearchInput').value.trim();
    const tbody = document.getElementById('globalLogsTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-slate-400">Memuat data log...</td></tr>';

    try {
        const res = await fetch(`?action=get_global_logs&page=${page}&search=${encodeURIComponent(search)}`);
        const json = await res.json();
        if (json.success && json.logs.length > 0) {
            tbody.innerHTML = json.logs.map(log => `
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-3 px-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                            <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xs">
                                ${escapeHtml(log.username.charAt(0).toUpperCase())}
                            </span>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block">${escapeHtml(log.username)}</span>
                                <span class="text-[10px] text-slate-400 uppercase font-mono">${escapeHtml(log.role)}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold ${log.badge.color}">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 ${log.badge.dot}"></span>
                            ${escapeHtml(log.badge.label)}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="font-bold text-slate-800 dark:text-slate-200 max-w-xs truncate">
                            ${escapeHtml(log.file_name)}
                        </div>
                        ${log.details ? `<div class="text-[11px] text-slate-400 mt-0.5 max-w-sm truncate">${escapeHtml(log.details)}</div>` : ''}
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <span class="font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                            ${escapeHtml(log.ip_address)}
                        </span>
                        <div class="text-[10px] text-slate-400 mt-0.5">${escapeHtml(log.device)}</div>
                    </td>
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="font-semibold text-slate-800 dark:text-slate-200">${escapeHtml(log.time_ago)}</div>
                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">${escapeHtml(log.created_at)}</div>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-slate-400">Tidak ada log yang sesuai dengan pencarian.</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-rose-500">Gagal memuat log aktivitas.</td></tr>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
