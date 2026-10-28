<?php
$pageTitle = 'Tentang Aplikasi & Pengembang - Cloud Sharing Drive';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\AuthService;

$isLoggedIn = AuthService::check();
$currentUser = $isLoggedIn ? AuthService::user() : null;
$isAdmin = !empty($currentUser['role']) && $currentUser['role'] === 'admin';
?>

<div class="min-h-full relative flex flex-col bg-slate-50 dark:bg-slate-950 overflow-x-hidden">

    <!-- Subtle Ambient Background Grid -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_25%,#000_60%,transparent_100%)] opacity-35 pointer-events-none"></div>

    <!-- Top Navigation Bar -->
    <header class="relative z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="<?= $isLoggedIn ? '?action=drive' : '?action=login' ?>" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-xl shadow-xs group-hover:scale-105 transition-all duration-200">
                    <span class="font-extrabold text-slate-900 dark:text-white text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300 text-xs sm:text-sm">Tentang Aplikasi</span>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <?php if ($isAdmin): ?>
                    <a href="?action=server" 
                       class="hidden sm:inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors">
                        <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                        Pengaturan Server
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="?action=drive" 
                       class="inline-flex items-center h-9 px-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Buka Drive Saya
                    </a>
                <?php else: ?>
                    <a href="?action=login" 
                       class="inline-flex items-center h-9 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                        Masuk ke Drive
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- 1. Hero Application Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 md:p-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 bg-gradient-to-br from-brand-50/70 via-white to-indigo-50/50 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/30">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 max-w-2xl">
                    <div class="h-20 w-20 rounded-3xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 shadow-md flex items-center justify-center shrink-0 p-3">
                        <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-600 text-white shadow-2xs">
                                v1.3.0 Production
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Self-Hosted Cloud Storage
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Cloud Sharing Drive
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-1.5 leading-relaxed">
                            Platform penyimpanan awan mandiri (<em>self-hosted file sharing & cloud drive</em>) berkecepatan tinggi untuk mengelola, melakukan pratinjau media, mengedit catatan/kode secara langsung, dan membagikan berkas besar secara aman tanpa batas pihak ketiga.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full md:w-auto">
                    <a href="https://github.com/frambudi75/Cloud-Sharing" target="_blank" rel="noopener noreferrer"
                       class="flex-1 md:flex-initial inline-flex items-center justify-center h-11 px-4 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-bold shadow-xs transition-all">
                        <svg class="w-4 h-4 mr-2 fill-current" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                        </svg>
                        Repository GitHub
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Developer Profile & Credits Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-8 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-start sm:items-center space-x-4">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 text-white font-extrabold text-xl flex items-center justify-center shrink-0 shadow-md shadow-brand-500/20">
                        HF
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/50 mb-1">
                            Pengembang & Arsitek Sistem
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">
                            Habib Frambudi
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Full-Stack Developer • Self-Hosted Infrastructure & Cloud Systems (<code class="font-mono text-brand-600 dark:text-brand-400 font-bold">@frambudi75</code>)
                        </p>
                    </div>
                </div>

                <!-- Developer Social & GitHub Links -->
                <div class="flex flex-wrap items-center gap-3">
                    <a href="https://github.com/frambudi75" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-brand-400 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-white text-xs font-bold shadow-2xs transition-all">
                        <svg class="w-4 h-4 mr-2 text-slate-700 dark:text-slate-200 fill-current" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                        </svg>
                        github.com/frambudi75
                    </a>

                    <a href="https://github.com/frambudi75/Cloud-Sharing" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs shadow-brand-500/20 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Proyek Cloud-Sharing
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Key Features Grid (8 Core Capabilities) -->
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Fitur & Kapabilitas Utama Aplikasi</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Dirancang untuk produktivitas tinggi, kemudahan berbagi, dan kendali penuh di server Anda sendiri.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Feature 1 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 flex items-center justify-center font-bold">
                        ⚡
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Chunked Upload & Auto-Retry</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Unggah berkas berukuran besar secara bertahap (per 8 MB) dengan pemulihan otomatis jika koneksi jaringan tersendat.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        🔗
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tautan Publik, Sandi & QR Code</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Bagikan tautan unduhan dengan proteksi kata sandi, batas jumlah unduhan, masa berlaku waktu, serta unduhan gambar QR Code PNG.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                        👥
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Berbagi Antar Pengguna</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Kirim akses berkas langsung ke pengguna terdaftar lainnya dengan pengaturan izin <em>Boleh Unduh</em> atau <em>Hanya Lihat</em>.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                        📝
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Editor Catatan & Kode Inline</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Buat dan edit berkas teks (<code>.txt</code>, <code>.md</code>, <code>.json</code>, <code>.csv</code>) maupun kode sumber langsung dari browser.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                        📊
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Analisis Kuota & Berkas Terbesar</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Pantau rincian pemakaian kuota per kategori berkas dan temukan 25 berkas paling memakan ruang di seluruh folder.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold">
                        🕒
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Log Aktivitas & Audit Trail</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Rekam lengkap setiap unggahan, unduhan langsung, unduhan publik, modifikasi berkas, alamat IP, dan perangkat pengakses.
                    </p>
                </div>

                <!-- Feature 7 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                        🖼️
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pratinjau Media & Galeri</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Pratinjau langsung untuk gambar, video, audio, PDF, dan teks baik di Drive maupun di halaman unduh tamu.
                    </p>
                </div>

                <!-- Feature 8 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                        📦
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Unduh ZIP, Duplikat & Sampah</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Unduh satu folder penuh sebagai arsip ZIP, buat salinan berkas dalam 1 klik, dan pulihkan berkas dari Keranjang Sampah.
                    </p>
                </div>

                <!-- Feature 9 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-2">
                    <div class="h-10 w-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold">
                        🔔
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Panel Admin & Integrasi Webhook</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Kelola pengguna, atur kuota per akun, pantau disk fisik server, dan kirim notifikasi otomatis ke Discord atau Slack.
                    </p>
                </div>
            </div>
        </div>

        <!-- 4. Tech Stack & Keyboard Shortcuts (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Technical Architecture -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    Spesifikasi Teknologi & Arsitektur
                </h3>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">Backend Engine</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">PHP <?= PHP_VERSION ?> (Strict Types MVC)</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">Database & Storage</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">MariaDB / MySQL + UUID Binary Store</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">In-Memory Acceleration</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">Redis Cache & Session Driver</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">Antarmuka Pengguna (UI)</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">Tailwind CSS + Plus Jakarta Sans (Dark/Light)</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">Keamanan & Integritas</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">CSRF Guard • Bcrypt • SHA-256 Checksum</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500">Deployment Stack</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">Docker Compose + Nginx FastCGI</span>
                    </div>
                </div>
            </div>

            <!-- Right: Keyboard Shortcuts -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Pintasan Keyboard Cepat
                </h3>
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">Command Palette & Cari Cepat</span>
                        <kbd class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200">Ctrl + K</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">Fokus Pencarian Berkas</span>
                        <kbd class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200">/</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">Unggah Berkas Baru</span>
                        <kbd class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200">U</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">Buat Folder Baru</span>
                        <kbd class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200">N</kbd>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">Navigasi Galeri Pratinjau</span>
                        <kbd class="px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono font-bold text-slate-800 dark:text-slate-200">← / →</kbd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Copyright -->
        <div class="pt-4 pb-6 text-center text-xs text-slate-400 dark:text-slate-500">
            Dikembangkan oleh <a href="https://github.com/frambudi75" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-600 dark:text-slate-300 hover:text-brand-600 underline">Habib Frambudi (@frambudi75)</a> • Cloud Sharing Drive &copy; <?= date('Y') ?>
        </div>

    </main>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>

