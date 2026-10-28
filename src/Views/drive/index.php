<?php
$pageTitle = ($activeTab === 'my_shares' ? 'Tautan Publik Saya' : ($activeTab === 'shared_with_me' ? 'Dibagikan ke Saya' : ($activeTab === 'recent' ? 'Terbaru' : 'Drive Saya'))) . ' - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$totalFiles = $totalFilesCount ?? count($files);
$totalFolders = $totalFoldersCount ?? count($folders);
$totalShared = $totalSharedCount ?? 0;
$myPublicSharesCount = isset($myPublicShares) ? count($myPublicShares) : 0;
?>

<style>
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulseBorder {
        0%, 100% { border-color: #4f46e5; transform: scale(1); }
        50% { border-color: #818cf8; transform: scale(1.005); }
    }

    .animate-item-in {
        animation: slideInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Gradient glow bar */
    .storage-bar-glow {
        background: linear-gradient(90deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%);
    }

    /* Sidebar Nav Active Pill */
    .sidebar-nav-active {
        background-color: #eef2ff;
        color: #4f46e5;
        font-weight: 700;
    }
    html.dark .sidebar-nav-active {
        background-color: rgba(79, 70, 229, 0.2) !important;
        color: #818cf8 !important;
    }

    /* Dragover active animation in dark & light */
    .dragover-active {
        animation: pulseBorder 1.5s infinite;
        background-color: #f5f3ff !important;
        border-color: #6366f1 !important;
        border-style: dashed !important;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.2) !important;
    }
    html.dark .dragover-active {
        background-color: rgba(99, 102, 241, 0.15) !important;
        border-color: #818cf8 !important;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.35) !important;
    }

    /* Dark mode explicit override for dropzone */
    html.dark #dropZone {
        background-color: rgba(17, 24, 39, 0.8) !important;
        border-color: #374151 !important;
        color: #94a3b8 !important;
    }
    html.dark #dropZone:hover {
        background-color: rgba(30, 27, 75, 0.5) !important;
        border-color: #6366f1 !important;
        color: #c7d2fe !important;
    }
    html.dark #uploadProgressContainer {
        background-color: #111827 !important;
        border-color: #1f2937 !important;
    }
    html.dark .shared-alert-banner {
        background: linear-gradient(90deg, #111827 0%, #1e1b4b 100%) !important;
        border-color: #3730a3 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
    }

    /* Card & Folder hover micro-interactions (Anti-Slop Modern Feel) */
    .file-card, .droppable-folder {
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .file-card:hover, .droppable-folder:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
    }

    /* Card selected highlight */
    .file-card.is-selected {
        border-color: #6366f1 !important;
        background-color: #faf5ff !important;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3) !important;
    }

    /* Row selected highlight */
    .file-row.is-selected {
        background-color: #f5f3ff !important;
    }
</style>

<!-- Main Layout Container -->
<div class="min-h-full flex flex-col bg-slate-50/70" id="driveApp" 
     data-csrf="<?= htmlspecialchars($csrfToken) ?>" 
     data-folder-id="<?= htmlspecialchars((string)($folderId ?? '')) ?>"
     data-active-tab="<?= htmlspecialchars($activeTab) ?>">

    <!-- Global Drag & Drop Overlay -->
    <div id="globalDropOverlay" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center p-6 border-4 border-dashed border-brand-400 pointer-events-none transition-all">
        <div class="h-20 w-20 rounded-2xl bg-brand-500/20 text-brand-400 flex items-center justify-center mb-4 animate-bounce">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-white tracking-tight">Lepaskan Berkas di Sini</h2>
        <p class="text-xs text-slate-300 mt-1">Berkas akan langsung diunggah ke folder aktif</p>
    </div>

    <!-- Top Navigation Bar (Mockup Aligned) -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <!-- Left: Logo & Subtitle -->
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-3 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-10 w-10 rounded-xl shadow-sm shadow-brand-500/20 group-hover:scale-105 transition-all duration-200">
                    <div>
                        <span class="font-extrabold text-slate-900 text-lg tracking-tight block leading-tight">Cloud Sharing</span>
                        <span class="text-[10px] text-slate-400 font-medium">PHP 8.4 • Redis Powered</span>
                    </div>
                </a>
            </div>

            <!-- Right: Quick Command Palette, Theme Toggle & Avatar Dropdown -->
            <div class="flex items-center space-x-2 sm:space-x-2.5">
                <!-- Command Palette Trigger (Ctrl + K) -->
                <button type="button" onclick="openCommandPalette()" 
                        class="inline-flex items-center h-9 px-3 rounded-xl border border-slate-200 hover:border-brand-300 bg-slate-50/80 hover:bg-white text-slate-500 hover:text-slate-800 text-xs font-medium transition-all shadow-2xs"
                        title="Pencarian & Perintah Cepat (Ctrl+K)">
                    <svg class="w-3.5 h-3.5 sm:mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span class="hidden md:inline mr-2 text-slate-400">Cari atau perintah...</span>
                    <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono font-bold text-slate-500 bg-white rounded border border-slate-200">Ctrl K</kbd>
                </button>

                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <!-- User Profile Avatar Dropdown -->
                <div class="relative" id="userDropdownWrapper">
                    <button type="button" onclick="toggleUserDropdown(event)" 
                            class="flex items-center space-x-2.5 p-1 pr-2.5 rounded-xl border border-slate-200/80 hover:border-slate-300 bg-white hover:bg-slate-50 transition-all shadow-2xs"
                            title="Menu Akun & Administrasi">
                        <?php if (!empty($user['avatar_path'])): ?>
                            <img src="?action=avatar&user_id=<?= $user['id'] ?>&t=<?= !empty($user['updated_at']) ? strtotime($user['updated_at']) : time() ?>" 
                                 alt="Avatar" 
                                 class="h-7 w-7 rounded-lg object-cover shadow-2xs">
                        <?php else: ?>
                            <div class="h-7 w-7 rounded-lg bg-gradient-to-tr from-brand-600 to-indigo-500 text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="hidden sm:flex flex-col text-left leading-none">
                            <span class="text-xs font-bold text-slate-800"><?= htmlspecialchars($user['username'] ?? 'User') ?></span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase mt-0.5"><?= htmlspecialchars($user['role'] ?? 'Member') ?></span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Card -->
                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-60 bg-white rounded-2xl border border-slate-200 shadow-xl py-2 z-50 text-xs font-semibold text-slate-700 animate-item-in">
                        <div class="px-4 py-2.5 border-b border-slate-100">
                            <p class="font-bold text-slate-900 truncate"><?= htmlspecialchars($user['username'] ?? 'User') ?></p>
                            <p class="text-[11px] text-slate-400 font-normal truncate mt-0.5"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                        </div>

                        <div class="py-1">
                            <a href="?action=profile" class="flex items-center px-4 py-2 hover:bg-slate-50 text-slate-700">
                                <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Profil & Kata Sandi
                            </a>
                            <a href="?action=drive&tab=my_shares" class="flex items-center justify-between px-4 py-2 hover:bg-slate-50 text-slate-700">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                    Tautan Publik Saya
                                </span>
                                <?php if ($myPublicSharesCount > 0): ?>
                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-700 font-bold"><?= $myPublicSharesCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="?action=drive&tab=storage_analyzer" class="flex items-center justify-between px-4 py-2 hover:bg-slate-50 text-slate-700">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    Analisis Penyimpanan
                                </span>
                                <span class="text-[10px] font-mono text-brand-600 font-bold"><?= $quotaPercentage ?>%</span>
                            </a>
                        </div>

                        <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
                            <div class="py-1 border-t border-slate-100">
                                <span class="block px-4 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Panel Admin</span>
                                <a href="?action=users" class="flex items-center px-4 py-2 hover:bg-slate-50 text-slate-700">
                                    <svg class="w-4 h-4 mr-2.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    Kelola Pengguna
                                </a>
                                <a href="?action=server" class="flex items-center px-4 py-2 hover:bg-slate-50 text-slate-700">
                                    <svg class="w-4 h-4 mr-2.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                                    Pengaturan Server & Webhook
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="pt-1 border-t border-slate-100">
                            <a href="?action=logout" class="flex items-center px-4 py-2 hover:bg-rose-50 text-rose-600">
                                <svg class="w-4 h-4 mr-2.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Keluar dari Akun
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Application Body: Sidebar + Main Content (2-Column Modern Shell) -->
    <div class="flex-1 max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 lg:pb-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ============================================================= -->
            <!-- 1. LEFT SIDEBAR NAVIGATION (Mockup Aligned)                    -->
            <!-- ============================================================= -->
            <aside class="col-span-12 lg:col-span-3 xl:col-span-2 space-y-2.5">
                <nav class="hidden lg:block space-y-1 bg-white p-2 rounded-2xl border border-slate-200/80 shadow-xs" aria-label="Sidebar">
                    
                    <!-- Drive Saya -->
                    <a href="?action=drive&tab=my_drive" 
                       class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'my_drive' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-3 <?= $activeTab === 'my_drive' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        Drive Saya
                    </a>

                    <!-- Tautan Publik Saya (Active Public Shares Manager) -->
                    <a href="?action=drive&tab=my_shares" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'my_shares' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-3 <?= $activeTab === 'my_shares' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            Tautan Publik
                        </span>
                        <?php if ($myPublicSharesCount > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-700 font-bold"><?= $myPublicSharesCount ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Dibagikan ke Saya -->
                    <a href="?action=drive&tab=shared_with_me" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'shared_with_me' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-3 <?= $activeTab === 'shared_with_me' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Dibagikan ke Saya
                        </span>
                        <?php if (!empty($sharedWithMeFiles) && count($sharedWithMeFiles) > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-brand-100 text-brand-700 font-bold"><?= count($sharedWithMeFiles) ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Terbaru -->
                    <a href="?action=drive&tab=recent" 
                       class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'recent' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-3 <?= $activeTab === 'recent' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Terbaru
                    </a>

                    <!-- Analisis Penyimpanan & Berkas Terbesar -->
                    <a href="?action=drive&tab=storage_analyzer" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'storage_analyzer' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-3 <?= $activeTab === 'storage_analyzer' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Analisis Kuota
                        </span>
                    </a>

                    <!-- Sampah -->
                    <a href="?action=drive&tab=trash" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'trash' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-3 <?= $activeTab === 'trash' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Sampah
                        </span>
                        <?php if (!empty($trashSummary['cnt']) && (int)$trashSummary['cnt'] > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-700 font-bold"><?= (int)$trashSummary['cnt'] ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Profil Saya -->
                    <div class="pt-2 mt-2 border-t border-slate-100">
                        <a href="?action=profile" 
                           class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all">
                            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profil Saya
                        </a>
                    </div>
                </nav>

                <!-- Storage Metric Card in Sidebar (Clickable -> Opens Storage Cleaner) -->
                <a href="?action=drive&tab=storage_analyzer" class="block p-3.5 bg-white hover:border-brand-300 rounded-2xl border border-slate-200/80 shadow-xs transition-all group">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800 mb-1.5">
                        <span class="flex items-center text-[11px] group-hover:text-brand-600 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path></svg>
                            Penyimpanan
                        </span>
                        <span class="text-[10px] font-mono text-brand-600 font-bold"><?= $quotaPercentage ?>%</span>
                    </div>
                    <!-- Multi-Color Segmented Storage Bar (Apple iCloud style) -->
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden mb-1.5 flex">
                        <?php if ($storageUsed > 0 && !empty($storageBreakdown)): ?>
                            <?php foreach ($storageBreakdown as $key => $cat): ?>
                                <?php if ($cat['size'] > 0): ?>
                                    <?php $segPercent = ($cat['size'] / $storageQuota) * 100; ?>
                                    <div class="h-full transition-all duration-300" 
                                         style="width: <?= max(1, $segPercent) ?>%; background-color: <?= $cat['color'] ?>;" 
                                         title="<?= $cat['label'] ?>: <?= StorageService::formatBytes($cat['size']) ?>"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="storage-bar-glow h-full rounded-full transition-all duration-500" style="width: <?= $quotaPercentage ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono">
                        <span><?= StorageService::formatBytes($storageUsed) ?> terpakai</span>
                        <span><?= StorageService::formatBytes($storageQuota) ?></span>
                    </div>

                    <!-- Mini Category Legend -->
                    <?php if ($storageUsed > 0 && !empty($storageBreakdown)): ?>
                        <div class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-[9px] text-slate-500 font-medium">
                            <?php foreach (['images', 'documents', 'videos', 'archives'] as $k): ?>
                                <?php if (!empty($storageBreakdown[$k]['size'])): ?>
                                    <span class="inline-flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1" style="background-color: <?= $storageBreakdown[$k]['color'] ?>"></span>
                                        <?= $storageBreakdown[$k]['label'] ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-2 pt-2 border-t border-slate-100 grid grid-cols-3 gap-1 text-center font-mono">
                        <div title="<?= $totalFiles ?> Berkas">
                            <div class="font-bold text-slate-800 text-[11px]"><?= $totalFiles ?></div>
                            <div class="text-slate-400 text-[9px]">Berkas</div>
                        </div>
                        <div title="<?= $totalFolders ?> Folder">
                            <div class="font-bold text-slate-800 text-[11px]"><?= $totalFolders ?></div>
                            <div class="text-slate-400 text-[9px]">Folder</div>
                        </div>
                        <div title="<?= $totalShared ?> Dibagikan">
                            <div class="font-bold text-slate-800 text-[11px]"><?= $totalShared ?></div>
                            <div class="text-slate-400 text-[9px]">Berbagi</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] font-bold text-brand-600">
                        <span>Analisis Berkas Terbesar</span>
                        <span class="group-hover:translate-x-0.5 transition-transform">→</span>
                    </div>
                </a>

                <!-- Keyboard Shortcuts Quick Hint -->
                <div class="px-2 pt-2 text-[10px] text-slate-400 font-medium flex items-center justify-between">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[9px]">/</kbd> Cari
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[9px]">U</kbd> Unggah
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[9px]">N</kbd> Folder
                    </span>
                </div>
            </aside>

            <!-- ============================================================= -->
            <!-- 2. MAIN DRIVE CONTENT AREA (Clean, Spacious, Productive)       -->
            <!-- ============================================================= -->
            <main class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-3.5">

                <!-- ROW 1: Clean Header (Breadcrumbs as Title + Action Buttons) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80">
                    <!-- Interactive Breadcrumbs & Subfolder ZIP -->
                    <div class="flex items-center space-x-2 truncate min-w-0">
                        <nav class="flex items-center space-x-1.5 text-xs text-slate-500 truncate" aria-label="Breadcrumb">
                            <?php if (!empty($breadcrumbs)): ?>
                                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                    <?php if ($index > 0): ?>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    <?php endif; ?>
                                    <?php if ($index === count($breadcrumbs) - 1): ?>
                                        <h1 class="text-base sm:text-lg font-bold text-slate-900 truncate">
                                            <?= htmlspecialchars($crumb['name']) ?>
                                        </h1>
                                    <?php else: ?>
                                        <a href="?action=drive<?= $crumb['id'] !== null ? '&folder=' . $crumb['id'] : '' ?>" 
                                           class="folder-drop-zone hover:text-brand-600 font-semibold px-1.5 py-0.5 rounded hover:bg-slate-100 transition-all truncate"
                                           data-folder-id="<?= $crumb['id'] !== null ? $crumb['id'] : 'root' ?>"
                                           data-folder-name="<?= htmlspecialchars($crumb['name']) ?>">
                                            <?= htmlspecialchars($crumb['name']) ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <h1 class="text-base sm:text-lg font-bold text-slate-900">
                                    <?= $activeTab === 'shared_with_me' ? 'Dibagikan ke Saya' : ($activeTab === 'recent' ? 'Terbaru' : ($activeTab === 'starred' ? 'Berbintang' : ($activeTab === 'trash' ? 'Keranjang Sampah' : 'Drive Saya'))) ?>
                                </h1>
                            <?php endif; ?>
                        </nav>

                        <?php if (!empty($folderId) && $activeTab === 'my_drive'): ?>
                            <a href="?action=download_folder_zip&folder_id=<?= $folderId ?>" 
                               class="inline-flex items-center h-6 px-2 rounded-md text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-all flex-shrink-0" 
                               title="Unduh seluruh folder ini sebagai ZIP">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh ZIP
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Right Header Actions (Catatan Baru, Folder Baru & Upload Berkas) -->
                    <?php if ($activeTab === 'my_drive'): ?>
                        <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                            <button type="button" onclick="openNewTextFileModal()" 
                                    class="inline-flex items-center h-8 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs hover:border-slate-300 transition-all"
                                    title="Buat berkas teks, catatan, atau kode baru">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Catatan / Teks Baru
                            </button>
                            <button type="button" id="btnNewFolderModal" 
                                    class="inline-flex items-center h-8 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs hover:border-slate-300 transition-all">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                </svg>
                                Folder Baru
                            </button>
                            <label for="fileUploadInput" 
                                   class="inline-flex items-center h-8 px-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-bold shadow-xs shadow-brand-500/20 transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Upload Berkas
                            </label>
                            <input type="file" id="fileUploadInput" class="hidden" multiple>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ROW 2: Unified Controls & Filter Bar (Compact 1-Card Toolbar) -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 bg-white p-2.5 rounded-2xl border border-slate-200/80 shadow-xs">
                    
                    <!-- Search Input & Quick Filter Chips -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1 min-w-0">
                        <div class="relative w-full sm:w-60 flex-shrink-0">
                            <input type="text" id="searchInput" placeholder="Cari berkas atau folder..." 
                                   class="w-full h-8 pl-8 pr-7 text-xs rounded-xl border border-slate-200 bg-slate-50/70 placeholder:text-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition-all">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span class="hidden sm:inline-block absolute right-2 top-2 px-1 text-[9px] text-slate-400 bg-slate-100 border border-slate-200 rounded font-mono pointer-events-none">/</span>
                        </div>

                        <!-- Filter Chips -->
                        <div class="flex items-center gap-1 overflow-x-auto no-scrollbar text-xs">
                            <button type="button" onclick="setFilterCategory('all', this)" class="filter-chip active px-2.5 py-1 rounded-lg font-bold bg-brand-600 text-white shadow-xs transition-all text-[11px]" data-category="all">Semua</button>
                            <button type="button" onclick="setFilterCategory('image', this)" class="filter-chip px-2.5 py-1 rounded-lg font-semibold bg-slate-50 text-slate-600 hover:bg-slate-100 transition-all text-[11px]" data-category="image">🖼️ Gambar</button>
                            <button type="button" onclick="setFilterCategory('document', this)" class="filter-chip px-2.5 py-1 rounded-lg font-semibold bg-slate-50 text-slate-600 hover:bg-slate-100 transition-all text-[11px]" data-category="document">📄 Dokumen</button>
                            <button type="button" onclick="setFilterCategory('media', this)" class="filter-chip px-2.5 py-1 rounded-lg font-semibold bg-slate-50 text-slate-600 hover:bg-slate-100 transition-all text-[11px]" data-category="media">🎬 Media</button>
                            <button type="button" onclick="setFilterCategory('archive', this)" class="filter-chip px-2.5 py-1 rounded-lg font-semibold bg-slate-50 text-slate-600 hover:bg-slate-100 transition-all text-[11px]" data-category="archive">📦 Arsip</button>
                        </div>
                    </div>

                    <!-- Right Controls: Sort & Grid/List View Switcher -->
                    <div class="flex items-center space-x-2 self-end lg:self-auto flex-shrink-0">
                        <select id="sortSelect" onchange="sortFiles(this.value)" 
                                class="h-8 pl-2.5 pr-6 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/70 text-slate-700 hover:border-slate-300 focus:border-brand-600 cursor-pointer appearance-none">
                            <option value="created_desc">Terbaru</option>
                            <option value="created_asc">Terlama</option>
                            <option value="name_asc">Nama (A - Z)</option>
                            <option value="name_desc">Nama (Z - A)</option>
                            <option value="size_desc">Ukuran (Besar)</option>
                            <option value="size_asc">Ukuran (Kecil)</option>
                        </select>

                        <div class="inline-flex p-0.5 bg-slate-100 rounded-xl border border-slate-200">
                            <button type="button" id="btnViewGrid" onclick="setViewMode('grid')" 
                                    class="h-7 px-2 rounded-lg text-xs font-semibold flex items-center transition-all bg-white text-brand-600 shadow-xs" title="Tampilan Kartu">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            </button>
                            <button type="button" id="btnViewList" onclick="setViewMode('list')" 
                                    class="h-7 px-2 rounded-lg text-xs font-semibold flex items-center transition-all text-slate-500 hover:text-slate-800" title="Tampilan Tabel">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Compact Drop Strip -->
                <?php if ($activeTab === 'my_drive'): ?>
                    <div id="dropZone" class="group border border-dashed border-slate-300/90 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-400 bg-white/70 dark:bg-slate-900/60 hover:bg-brand-50/30 dark:hover:bg-brand-950/40 rounded-xl px-4 py-2.5 text-center transition-all duration-200 cursor-pointer flex items-center justify-center space-x-2 text-xs text-slate-500 dark:text-slate-400 hover:text-brand-600 dark:hover:text-brand-300">
                        <svg class="w-4 h-4 text-brand-600 dark:text-brand-400 flex-shrink-0 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <span>Tarik & jatuhkan berkas ke sini, atau <span class="text-brand-600 dark:text-brand-400 font-bold underline">pilih dari komputer</span></span>
                    </div>

                    <!-- Progress Bar for Uploads -->
                    <div id="uploadProgressContainer" class="hidden bg-white border border-slate-200 rounded-xl p-3 shadow-xs animate-item-in">
                        <div class="flex items-center justify-between text-xs mb-1 font-bold">
                            <span id="uploadFileName" class="text-slate-800 truncate max-w-sm">Nama Berkas</span>
                            <span id="uploadPercentage" class="text-brand-600 font-mono">0%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div id="uploadProgressBar" class="h-1.5 rounded-full storage-bar-glow transition-all duration-200" style="width: 0%"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Trash Notice Banner -->
                <?php if ($activeTab === 'trash'): ?>
                    <div class="bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-2xl p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs animate-item-in">
                        <div class="flex items-center space-x-3">
                            <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-900/80 text-amber-700 dark:text-amber-300 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-amber-950 dark:text-amber-200">Keranjang Sampah</h3>
                                <p class="text-[11px] text-amber-800 dark:text-amber-300/80 mt-0.5">Berkas di sini dapat dipulihkan atau dihapus permanen untuk mengembalikan kuota penyimpanan.</p>
                            </div>
                        </div>
                        <?php if (!empty($files) || !empty($folders)): ?>
                            <button type="button" onclick="emptyTrashConfirm()" 
                                    class="h-8 px-3.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors whitespace-nowrap">
                                Kosongkan Sampah
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ========================================================= -->
                <!-- CONTENT 0: TAB TAUTAN PUBLIK SAYA (ACTIVE SHARES MANAGER) -->
                <!-- ========================================================= -->
                <?php if ($activeTab === 'my_shares'): ?>

                    <div class="space-y-4">
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50/70 dark:from-slate-900 dark:to-emerald-950/30 border border-emerald-200/80 dark:border-emerald-900/50 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs animate-item-in">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Pusat Kendali Tautan Publik</h3>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">Kelola semua tautan unduhan tamu yang sedang aktif, pantau kuota unduhan, tampilkan QR Code, atau cabut akses kapan saja.</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 self-start sm:self-auto">
                                <?= count($myPublicShares ?? []) ?> Tautan Aktif
                            </span>
                        </div>

                        <?php if (empty($myPublicShares)): ?>
                            <div class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs animate-item-in">
                                <div class="h-16 w-16 mx-auto mb-3 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Tautan Publik Aktif</h3>
                                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                                    Anda belum membagikan berkas apa pun melalui tautan publik. Klik ikon Bagikan pada berkas di Drive Saya untuk membuat tautan.
                                </p>
                                <a href="?action=drive" class="inline-flex items-center h-9 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                                    Buka Drive Saya
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs animate-item-in">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse" id="filesTable">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                <th class="py-3 px-4">Berkas</th>
                                                <th class="py-3 px-4">Proteksi</th>
                                                <th class="py-3 px-4">Unduhan</th>
                                                <th class="py-3 px-4">Masa Berlaku</th>
                                                <th class="py-3 px-4 text-right">Aksi Cepat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-sm">
                                            <?php foreach ($myPublicShares as $sItem): ?>
                                                <?php
                                                $typeInfo = StorageService::getFileTypeInfo($sItem['mime_type'], $sItem['original_name']);
                                                $isExpired = !empty($sItem['expires_at']) && strtotime($sItem['expires_at']) < time();
                                                $isQuotaFull = $sItem['max_downloads'] !== null && (int)$sItem['download_count'] >= (int)$sItem['max_downloads'];
                                                ?>
                                                <tr class="file-row hover:bg-slate-50/70 transition-colors"
                                                    data-filename="<?= htmlspecialchars(strtolower($sItem['original_name'])) ?>"
                                                    data-rawname="<?= htmlspecialchars($sItem['original_name']) ?>"
                                                    data-category="<?= htmlspecialchars($typeInfo['type']) ?>"
                                                    data-filesize="<?= (int)$sItem['file_size'] ?>"
                                                    data-created="<?= strtotime($sItem['shared_at']) ?>">
                                                    <td class="py-3.5 px-4">
                                                        <div class="flex items-center space-x-3">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>">
                                                                <?= $typeInfo['badge'] ?>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <p class="font-semibold text-slate-800 truncate max-w-xs sm:max-w-md" title="<?= htmlspecialchars($sItem['original_name']) ?>">
                                                                    <?= htmlspecialchars($sItem['original_name']) ?>
                                                                </p>
                                                                <span class="text-[11px] text-slate-400 font-mono"><?= StorageService::formatBytes((int)$sItem['file_size']) ?></span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                                        <?php if (!empty($sItem['has_password'])): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                🔒 Sandi Aktif
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                🌐 Publik Terbuka
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs">
                                                        <span class="font-bold text-slate-800"><?= (int)$sItem['download_count'] ?></span>
                                                        <span class="text-slate-400">/ <?= $sItem['max_downloads'] !== null ? (int)$sItem['max_downloads'] . ' maks' : '∞' ?></span>
                                                        <?php if ($isQuotaFull): ?>
                                                            <span class="ml-1 px-1.5 py-0.2 rounded text-[10px] bg-rose-50 text-rose-600 font-sans font-bold">Habis</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                                                        <?php if (!empty($sItem['expires_at'])): ?>
                                                            <span class="<?= $isExpired ? 'text-rose-600 font-bold' : 'text-slate-600' ?>">
                                                                <?= date('d M Y, H:i', strtotime($sItem['expires_at'])) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-slate-400">Selamanya</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                        <div class="inline-flex items-center space-x-1.5 justify-end">
                                                            <button type="button" onclick="openQrModal('<?= htmlspecialchars($sItem['share_token']) ?>', '<?= htmlspecialchars(addslashes($sItem['original_name'])) ?>')"
                                                                    class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold inline-flex items-center transition-colors"
                                                                    title="Tampilkan QR Code">
                                                                <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                                                QR
                                                            </button>
                                                            <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($sItem['share_token']) ?>', this)"
                                                                    class="copy-btn h-8 px-2.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold inline-flex items-center transition-colors">
                                                                Salin Link
                                                            </button>
                                                            <a href="?action=share&token=<?= htmlspecialchars($sItem['share_token']) ?>" target="_blank"
                                                               class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 inline-flex items-center justify-center transition-colors"
                                                               title="Buka Halaman Unduh Tamu">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                            </a>
                                                            <button type="button" onclick="openShareModal(<?= (int)$sItem['file_id'] ?>, '<?= htmlspecialchars(addslashes($sItem['original_name'])) ?>', '<?= htmlspecialchars($sItem['share_token']) ?>')"
                                                                    class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 inline-flex items-center justify-center transition-colors"
                                                                    title="Ubah Pengaturan Link">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                            </button>
                                                            <button type="button" onclick="revokePublicShareLink(<?= (int)$sItem['share_id'] ?>, <?= (int)$sItem['file_id'] ?>, '<?= htmlspecialchars(addslashes($sItem['original_name'])) ?>')"
                                                                    class="h-8 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-xs font-bold inline-flex items-center transition-colors"
                                                                    title="Matikan & Cabut Tautan Publik">
                                                                Cabut
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- ========================================================= -->
                <!-- CONTENT 1: TAB DIBAGIKAN KE SAYA                          -->
                <!-- ========================================================= -->
                <?php elseif ($activeTab === 'shared_with_me'): ?>
                    
                    <div>
                        <?php if (empty($sharedWithMeFiles)): ?>
                            <div class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs">
                                <div class="h-16 w-16 mx-auto mb-3 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Berkas yang Dibagikan</h3>
                                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                                    Pengguna terdaftar lain belum membagikan berkas mereka secara langsung ke akun Anda.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                <th class="py-3 px-4">Nama Berkas</th>
                                                <th class="py-3 px-4">Dibagikan Oleh</th>
                                                <th class="py-3 px-4">Hak Akses</th>
                                                <th class="py-3 px-4">Ukuran</th>
                                                <th class="py-3 px-4">Tanggal Terima</th>
                                                <th class="py-3 px-4 text-right">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-sm">
                                            <?php foreach ($sharedWithMeFiles as $item): ?>
                                                <?php $typeInfo = StorageService::getFileTypeInfo($item['mime_type'], $item['original_name']); ?>
                                                <tr class="file-row hover:bg-slate-50/70 transition-colors" data-filename="<?= htmlspecialchars(strtolower($item['original_name'])) ?>">
                                                    <td class="py-3.5 px-4 font-semibold text-slate-800 flex items-center space-x-3">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>">
                                                            <?= $typeInfo['badge'] ?>
                                                        </span>
                                                        <span class="truncate max-w-xs sm:max-w-md"><?= htmlspecialchars($item['original_name']) ?></span>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-xs">
                                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($item['shared_by_username']) ?></span>
                                                        <span class="text-slate-400 block"><?= htmlspecialchars($item['shared_by_email']) ?></span>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                                        <?php if ($item['permission'] === 'download'): ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                Boleh Unduh
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                Hanya Lihat
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-slate-600 text-xs font-mono">
                                                        <?= StorageService::formatBytes((int)$item['file_size']) ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-slate-500 text-xs">
                                                        <?= date('d M Y, H:i', strtotime($item['shared_at'])) ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                                        <button type="button" onclick="previewFile(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['original_name'])) ?>', '<?= htmlspecialchars($item['mime_type']) ?>')"
                                                                class="h-8 px-2.5 rounded-lg border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                                                            Pratinjau
                                                        </button>
                                                        <?php if ($item['permission'] === 'download'): ?>
                                                            <a href="?action=download_direct&file_id=<?= $item['id'] ?>" 
                                                               class="inline-flex items-center h-8 px-3 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                                                                Unduh
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- ========================================================= -->
                <!-- CONTENT 1B: TAB ANALISIS PENYIMPANAN & BERKAS TERBESAR    -->
                <!-- ========================================================= -->
                <?php elseif ($activeTab === 'storage_analyzer'): ?>

                    <div class="space-y-4 animate-item-in">
                        <!-- Storage Breakdown Overview Card -->
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Rincian Penggunaan Kuota Penyimpanan</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Analisis distribusi kapasitas ruang penyimpanan Anda berdasarkan kategori berkas.</p>
                                </div>
                                <div class="text-left sm:text-right font-mono">
                                    <span class="text-base font-extrabold text-brand-600"><?= StorageService::formatBytes($storageUsed) ?></span>
                                    <span class="text-xs text-slate-400"> / <?= StorageService::formatBytes($storageQuota) ?> (<?= $quotaPercentage ?>%)</span>
                                </div>
                            </div>

                            <!-- Large Segmented Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-3.5 overflow-hidden flex p-0.5 gap-0.5">
                                <?php if ($storageUsed > 0 && !empty($storageBreakdown)): ?>
                                    <?php foreach ($storageBreakdown as $cat): ?>
                                        <?php if ($cat['size'] > 0): ?>
                                            <?php $segPercent = ($cat['size'] / max(1, $storageQuota)) * 100; ?>
                                            <div class="h-full first:rounded-l-full last:rounded-r-full transition-all duration-500"
                                                 style="width: <?= max(1.5, $segPercent) ?>%; background-color: <?= $cat['color'] ?>;"
                                                 title="<?= $cat['label'] ?>: <?= StorageService::formatBytes($cat['size']) ?> (<?= $cat['count'] ?> berkas)"></div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="storage-bar-glow h-full rounded-full" style="width: <?= $quotaPercentage ?>%"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Category Breakdown Cards Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 pt-1">
                                <?php foreach ($storageBreakdown as $key => $cat): ?>
                                    <div class="p-3 rounded-xl border border-slate-200/70 bg-slate-50/60 flex flex-col justify-between">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="inline-flex items-center text-[11px] font-bold text-slate-700">
                                                <span class="w-2.5 h-2.5 rounded-full mr-1.5 shrink-0" style="background-color: <?= $cat['color'] ?>"></span>
                                                <?= htmlspecialchars($cat['label']) ?>
                                            </span>
                                        </div>
                                        <div class="font-mono text-xs font-bold text-slate-900"><?= StorageService::formatBytes((int)$cat['size']) ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><?= (int)$cat['count'] ?> berkas</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Trash Space Reclaim Notice (If Trash has files) -->
                        <?php if (!empty($trashSummary['cnt']) && (int)$trashSummary['cnt'] > 0): ?>
                            <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                <div class="flex items-center space-x-3">
                                    <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-amber-950">Terdapat <?= (int)$trashSummary['cnt'] ?> berkas (<?= StorageService::formatBytes((int)$trashSummary['total_bytes']) ?>) di Keranjang Sampah</h4>
                                        <p class="text-[11px] text-amber-800 mt-0.5">Mengosongkan keranjang sampah akan langsung membebaskan ruang penyimpanan Anda secara permanen.</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="?action=drive&tab=trash" class="h-8 px-3 rounded-xl border border-amber-300 bg-white hover:bg-amber-50 text-amber-900 text-xs font-semibold inline-flex items-center transition-colors">
                                        Lihat Sampah
                                    </a>
                                    <button type="button" onclick="emptyTrashConfirm()" class="h-8 px-3.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                                        Kosongkan Sekarang
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Top 25 Largest Files Table -->
                        <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">25 Berkas Terbesar di Seluruh Folder</h4>
                                    <p class="text-[11px] text-slate-400">Urutan berkas dari ukuran terbesar untuk memudahkan pembersihan ruang penyimpanan.</p>
                                </div>
                                <span class="text-[11px] font-mono font-bold text-slate-500"><?= count($files) ?> berkas</span>
                            </div>

                            <?php if (empty($files)): ?>
                                <div class="p-10 text-center text-xs text-slate-400">Belum ada berkas yang tersimpan di akun Anda.</div>
                            <?php else: ?>
                                <?php $maxFileBytes = max(1, (int)($files[0]['file_size'] ?? 1)); ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse" id="filesTable">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                                <th class="py-3 px-4 w-12">#</th>
                                                <th class="py-3 px-4">Nama Berkas</th>
                                                <th class="py-3 px-4">Lokasi Folder</th>
                                                <th class="py-3 px-4 w-56">Proporsi Ukuran</th>
                                                <th class="py-3 px-4">Diunggah</th>
                                                <th class="py-3 px-4 text-right">Aksi Cepat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-sm">
                                            <?php foreach ($files as $idx => $file): ?>
                                                <?php
                                                $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']);
                                                $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                                $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                                $relBarPercent = min(100, max(4, round(((int)$file['file_size'] / $maxFileBytes) * 100)));
                                                $fileJson = htmlspecialchars(json_encode([
                                                    'id' => (int)$file['id'],
                                                    'name' => $file['original_name'],
                                                    'size' => (int)$file['file_size'],
                                                    'formatted_size' => StorageService::formatBytes((int)$file['file_size']),
                                                    'mime' => $file['mime_type'],
                                                    'type' => $typeInfo['type'],
                                                    'badge' => $typeInfo['badge'],
                                                    'bg' => $typeInfo['bg'],
                                                    'created_at' => date('d M Y, H:i', strtotime($file['created_at'])),
                                                    'is_shared' => $hasPublicShare,
                                                    'share_token' => $file['share_token'] ?? '',
                                                    'user_shares_count' => $directShareCount,
                                                ]), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr class="file-row hover:bg-slate-50/70 transition-colors"
                                                    data-id="<?= $file['id'] ?>"
                                                    data-category="<?= htmlspecialchars($typeInfo['type']) ?>"
                                                    data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                                    data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                                    data-filesize="<?= (int)$file['file_size'] ?>"
                                                    data-created="<?= strtotime($file['created_at']) ?>"
                                                    data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>">
                                                    <td class="py-3 px-4 font-mono text-xs font-bold text-slate-400">
                                                        #<?= $idx + 1 ?>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center space-x-2.5">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>">
                                                                <?= $typeInfo['badge'] ?>
                                                            </span>
                                                            <button type="button" onclick="openFileDetail(<?= $fileJson ?>)" class="font-semibold text-slate-800 hover:text-brand-600 truncate max-w-xs sm:max-w-sm text-left">
                                                                <?= htmlspecialchars($file['original_name']) ?>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4 text-xs">
                                                        <?php if (!empty($file['folder_id'])): ?>
                                                            <a href="?action=drive&folder=<?= (int)$file['folder_id'] ?>" class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 font-semibold transition-colors">
                                                                📁 <?= htmlspecialchars($file['folder_name'] ?? 'Subfolder') ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=drive" class="text-slate-400 hover:text-slate-600">Drive Utama</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center justify-between text-xs font-mono font-bold text-slate-800 mb-1">
                                                            <span><?= StorageService::formatBytes((int)$file['file_size']) ?></span>
                                                            <span class="text-[10px] text-slate-400 font-normal"><?= $storageQuota > 0 ? round(((int)$file['file_size'] / $storageQuota) * 100, 1) : 0 ?>% kuota</span>
                                                        </div>
                                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                            <div class="h-1.5 rounded-full bg-brand-600" style="width: <?= $relBarPercent ?>%"></div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4 text-xs text-slate-500 whitespace-nowrap">
                                                        <?= date('d M Y', strtotime($file['created_at'])) ?>
                                                    </td>
                                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                                        <div class="inline-flex items-center space-x-1.5">
                                                            <button type="button" onclick="openInspectorDrawer(<?= $fileJson ?>)" class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 inline-flex items-center justify-center" title="Detail Inspektur">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            </button>
                                                            <button type="button" onclick="openFileDetail(<?= $fileJson ?>)" class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 inline-flex items-center justify-center" title="Pratinjau">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                            </button>
                                                            <a href="?action=download_direct&file_id=<?= $file['id'] ?>" class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 inline-flex items-center justify-center" title="Unduh">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                            </a>
                                                            <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" class="h-8 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-xs font-bold inline-flex items-center transition-colors" title="Pindahkan ke Sampah">
                                                                Hapus
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- ========================================================= -->
                <!-- CONTENT 2: TAB DRIVE SAYA / RECENT                        -->
                <!-- ========================================================= -->
                <?php else: ?>

                    <!-- Notification if there are files shared to this user -->
                    <?php if ($activeTab === 'my_drive' && !empty($sharedWithMeFiles) && count($sharedWithMeFiles) > 0): ?>
                        <div class="shared-alert-banner p-4 rounded-2xl bg-gradient-to-r from-brand-50 to-indigo-50/80 dark:from-slate-900 dark:to-indigo-950/50 border border-brand-200/90 dark:border-indigo-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs animate-item-in">
                            <div class="flex items-center space-x-3.5">
                                <div class="h-10 w-10 rounded-xl bg-brand-600 dark:bg-brand-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-brand-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Anda memiliki <?= count($sharedWithMeFiles) ?> berkas yang dibagikan kepada Anda</h4>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">Berkas dibagikan oleh <strong class="font-semibold text-slate-800 dark:text-white"><?= htmlspecialchars($sharedWithMeFiles[0]['shared_by_username'] ?? 'pengguna lain') ?></strong>. Anda dapat melihat dan mengunduhnya langsung.</p>
                                </div>
                            </div>
                            <a href="?action=drive&tab=shared_with_me" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white text-xs font-bold shadow-xs shadow-brand-500/20 transition-all whitespace-nowrap">
                                Buka Berkas Dibagikan →
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Empty State -->
                    <?php if (empty($files) && empty($folders)): ?>
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs animate-item-in">
                            <div class="h-16 w-16 mx-auto mb-3 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 mb-1">
                                <?= $activeTab === 'starred' ? 'Belum Ada Berkas Berbintang' : ($activeTab === 'trash' ? 'Sampah Kosong' : 'Folder ini masih kosong') ?>
                            </h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                                <?= $activeTab === 'trash' ? 'Tidak ada berkas yang berada di tempat sampah.' : 'Belum ada berkas yang diunggah ke direktori ini. Mulai dengan mengunggah berkas atau membuat folder.' ?>
                            </p>
                            <?php if ($activeTab === 'my_drive'): ?>
                                <div class="flex flex-wrap items-center justify-center gap-2.5">
                                    <label for="fileUploadInput" class="cursor-pointer inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white text-xs font-bold shadow-xs transition-all">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Upload Berkas
                                    </label>
                                    <button type="button" onclick="document.getElementById('btnNewFolderModal').click()" class="inline-flex items-center h-10 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 active:scale-[0.98] text-slate-700 text-xs font-bold shadow-xs transition-all">
                                        <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                        Folder Baru
                                    </button>
                                    <button type="button" onclick="openNewTextFileModal()" class="inline-flex items-center h-10 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 active:scale-[0.98] text-slate-700 text-xs font-bold shadow-xs transition-all">
                                        <svg class="w-4 h-4 mr-1.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Catatan Baru
                                    </button>
                                </div>
                                <span class="block text-[11px] text-slate-400 mt-3 font-medium">atau tarik dan lepaskan (drag & drop) berkas ke layar ini</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>

                        <!-- GRID VIEW (Mockup Aligned: Clean Cards with Big Centered Icons) -->
                        <div id="filesGridView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            
                            <!-- 1. Folder Cards (macOS Modern Layered Style with Item Counts) -->
                            <?php foreach ($folders as $folder): ?>
                                <?php
                                $fFilesCnt = (int)($folder['files_count'] ?? 0);
                                $fSubCnt = (int)($folder['subfolders_count'] ?? 0);
                                $folderSummaryParts = [];
                                if ($fFilesCnt > 0) $folderSummaryParts[] = "{$fFilesCnt} berkas";
                                if ($fSubCnt > 0) $folderSummaryParts[] = "{$fSubCnt} folder";
                                $folderCountText = !empty($folderSummaryParts) ? implode(' • ', $folderSummaryParts) : 'Kosong';
                                ?>
                                <div class="file-card folder-drop-zone group relative bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-lg hover:-translate-y-0.5 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 animate-item-in"
                                     data-folder-id="<?= $folder['id'] ?>"
                                     data-category="all"
                                     data-folder-name="<?= htmlspecialchars(strtolower($folder['name'])) ?>">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-600 uppercase tracking-wider">
                                            Folder
                                        </span>
                                        <div class="flex items-center space-x-1">
                                            <?php if ($activeTab === 'trash'): ?>
                                                <button type="button" onclick="restoreFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                                        class="text-slate-400 hover:text-emerald-600 transition-colors p-1 rounded-lg hover:bg-emerald-50" title="Pulihkan folder">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </button>
                                                <button type="button" onclick="permanentDeleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                                        class="text-slate-400 hover:text-rose-600 transition-colors p-1 rounded-lg hover:bg-rose-50" title="Hapus permanen">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            <?php else: ?>
                                                <a href="?action=download_folder_zip&folder_id=<?= $folder['id'] ?>" 
                                                   class="text-slate-400 hover:text-amber-600 transition-colors p-1 rounded-lg hover:bg-amber-50" title="Unduh Folder sebagai ZIP">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </a>
                                                <button type="button" onclick="openRenameModal('folder', <?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')"
                                                        class="text-slate-400 hover:text-brand-600 transition-colors p-1 rounded-lg hover:bg-slate-100" title="Ganti nama folder">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </button>
                                                <button type="button" onclick="deleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                                        class="text-slate-400 hover:text-rose-600 transition-colors p-1 rounded-lg hover:bg-rose-50" title="Hapus folder">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Big Centered Folder Icon with Layered Depth -->
                                    <a href="?action=drive&folder=<?= $folder['id'] ?>" class="my-3 flex flex-col items-center text-center">
                                        <div class="relative h-16 w-16 rounded-2xl bg-gradient-to-br from-indigo-50 to-brand-100/70 text-brand-600 border border-indigo-200/60 flex items-center justify-center group-hover:scale-105 transition-transform shadow-2xs">
                                            <svg class="w-10 h-10 drop-shadow-2xs" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                            <?php if ($fFilesCnt > 0): ?>
                                                <span class="absolute -bottom-1 -right-1 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-brand-600 text-white shadow-2xs">
                                                    <?= $fFilesCnt ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-xs font-bold text-slate-900 mt-2.5 truncate w-full group-hover:text-brand-600" title="<?= htmlspecialchars($folder['name']) ?>">
                                            <?= htmlspecialchars($folder['name']) ?>
                                        </h3>
                                    </a>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                        <span class="font-semibold text-slate-500"><?= htmlspecialchars($folderCountText) ?></span>
                                        <span><?= date('d M Y', strtotime($folder['updated_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- 2. File Cards (Rich Category Visuals, Share Status Badges & Smooth Shimmer) -->
                            <?php foreach ($files as $file): ?>
                                <?php
                                $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']);
                                $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                $hasSharePassword = !empty($file['has_password']);
                                $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                $ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                $fileJson = htmlspecialchars(json_encode([
                                    'id' => (int)$file['id'],
                                    'name' => $file['original_name'],
                                    'size' => (int)$file['file_size'],
                                    'formatted_size' => StorageService::formatBytes((int)$file['file_size']),
                                    'mime' => $file['mime_type'],
                                    'type' => $typeInfo['type'],
                                    'badge' => $typeInfo['badge'],
                                    'bg' => $typeInfo['bg'],
                                    'created_at' => date('d M Y, H:i', strtotime($file['created_at'])),
                                    'is_shared' => $hasPublicShare,
                                    'share_token' => $file['share_token'] ?? '',
                                    'user_shares_count' => $directShareCount,
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="file-card draggable-file group relative bg-white border border-slate-200/90 hover:border-brand-400 hover:shadow-md rounded-2xl p-3.5 flex flex-col justify-between transition-all duration-150 animate-item-in cursor-grab active:cursor-grabbing"
                                     draggable="true"
                                     data-id="<?= $file['id'] ?>"
                                     data-category="<?= htmlspecialchars($typeInfo['type']) ?>"
                                     data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                     data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                     data-filesize="<?= (int)$file['file_size'] ?>"
                                     data-created="<?= strtotime($file['created_at']) ?>"
                                     data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>"
                                     data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>"
                                     data-filejson="<?= $fileJson ?>"
                                     oncontextmenu="handleFileContextMenu(event, <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')">
                                    
                                    <!-- Card Header: Checkbox, Share Pill & Actions Dropdown -->
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <input type="checkbox" 
                                                   class="file-select-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer"
                                                   data-id="<?= $file['id'] ?>" 
                                                   onchange="onFileCheckboxChange(this)"
                                                   onclick="event.stopPropagation()">
                                            <?php if ($hasPublicShare): ?>
                                                <button type="button" onclick="event.stopPropagation(); copyShareUrl('<?= htmlspecialchars($file['share_token']) ?>', this)"
                                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/90 hover:bg-emerald-100 transition-colors"
                                                        title="Tautan publik aktif — Klik untuk salin link">
                                                    <?= $hasSharePassword ? '🔒 Publik' : '🔗 Publik' ?>
                                                </button>
                                            <?php elseif ($directShareCount > 0): ?>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/90" title="Dibagikan ke <?= $directShareCount ?> pengguna">
                                                    👥 <?= $directShareCount ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="relative">
                                            <button type="button" 
                                                    onclick="toggleCardMenu(event, 'cardMenu_<?= $file['id'] ?>')" 
                                                    class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors"
                                                    title="Opsi berkas">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                </svg>
                                            </button>

                                            <!-- In-Card Action Dropdown -->
                                            <div id="cardMenu_<?= $file['id'] ?>" class="card-action-menu hidden absolute right-0 top-8 z-40 w-44 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs font-semibold text-slate-700">
                                                <?php if ($activeTab === 'trash'): ?>
                                                    <button type="button" onclick="restoreFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-emerald-50 text-emerald-600 flex items-center">
                                                        <svg class="w-4 h-4 mr-2.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        Pulihkan Berkas
                                                    </button>
                                                    <button type="button" onclick="permanentDeleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-rose-50 text-rose-600 flex items-center">
                                                        <svg class="w-4 h-4 mr-2.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        Hapus Permanen
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" onclick="openInspectorDrawer(<?= $fileJson ?>)" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-brand-600">
                                                        <svg class="w-4 h-4 mr-2.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Inspektur & Info
                                                    </button>
                                                    <button type="button" onclick="openFileDetail(<?= $fileJson ?>)" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
                                                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        Pratinjau Penuh
                                                    </button>
                                                    <a href="?action=download_direct&file_id=<?= $file['id'] ?>" 
                                                       class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
                                                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                        Unduh Berkas
                                                    </a>
                                                    <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
                                                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12a3 3 0 100-6 3 3 0 000 6zM18 6a3 3 0 100-6 3 3 0 000 6zM18 20a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                                                        Bagikan
                                                    </button>
                                                    <?php if ($hasPublicShare): ?>
                                                        <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($file['share_token']) ?>', this)" 
                                                                class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-emerald-600">
                                                            <svg class="w-4 h-4 mr-2.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"></path></svg>
                                                            Salin Link
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" onclick="openRenameModal('file', <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
                                                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                        Ganti Nama
                                                    </button>
                                                    <button type="button" onclick="duplicateFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
                                                        <svg class="w-4 h-4 mr-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                        Duplikat Berkas
                                                    </button>
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                    <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="w-full text-left px-3.5 py-2 hover:bg-rose-50 text-rose-600 flex items-center">
                                                        <svg class="w-4 h-4 mr-2.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        Hapus
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Body: Thumbnail or Category-Specific Rich Illustration -->
                                    <div class="cursor-pointer" onclick="openFileDetail(<?= $fileJson ?>)">
                                        <?php if ($typeInfo['type'] === 'image'): ?>
                                            <div class="h-28 w-full rounded-xl thumb-skeleton overflow-hidden relative mb-2.5 flex items-center justify-center border border-slate-200/60 group/thumb">
                                                <img src="?action=download_direct&file_id=<?= $file['id'] ?>&thumb=1" 
                                                     alt="<?= htmlspecialchars($file['original_name']) ?>" 
                                                     class="h-full w-full object-cover group-hover/thumb:scale-105 transition-all duration-300" 
                                                     loading="lazy" 
                                                     onload="this.parentElement.classList.remove('thumb-skeleton')"
                                                     onerror="this.style.display='none'; this.parentElement.classList.remove('thumb-skeleton'); this.nextElementSibling.classList.remove('hidden');">
                                                <div class="hidden h-full w-full flex items-center justify-center bg-rose-50 text-rose-500">
                                                    <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                                                </div>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'video'): ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-amber-50/90 to-orange-50/60 border border-amber-200/60 mb-2.5 flex flex-col items-center justify-center group-hover:scale-[1.01] transition-all">
                                                <div class="h-11 w-11 rounded-2xl bg-amber-500/15 text-amber-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'audio'): ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-indigo-50/90 to-violet-50/60 border border-indigo-200/60 mb-2.5 flex flex-col items-center justify-center group-hover:scale-[1.01] transition-all">
                                                <div class="h-11 w-11 rounded-2xl bg-indigo-500/15 text-indigo-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'archive'): ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border border-emerald-200/60 mb-2.5 flex flex-col items-center justify-center group-hover:scale-[1.01] transition-all">
                                                <div class="h-11 w-11 rounded-2xl bg-emerald-500/15 text-emerald-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'pdf'): ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-red-50/90 to-rose-50/60 border border-red-200/60 mb-2.5 flex flex-col items-center justify-center group-hover:scale-[1.01] transition-all">
                                                <div class="h-11 w-11 rounded-2xl bg-red-500/15 text-red-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'code'): ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-violet-50/90 to-purple-50/60 border border-violet-200/60 mb-2.5 flex flex-col items-center justify-center group-hover:scale-[1.01] transition-all">
                                                <div class="h-11 w-11 rounded-2xl bg-violet-500/15 text-violet-600 flex items-center justify-center mb-1.5 shadow-2xs">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="h-28 w-full rounded-xl bg-gradient-to-br from-sky-50/70 to-slate-50 border border-slate-200/70 mb-2.5 flex flex-col items-center justify-center group-hover:bg-brand-50/30 transition-colors">
                                                <div class="h-11 w-11 rounded-2xl bg-sky-500/10 text-sky-600 flex items-center justify-center mb-1.5">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>">
                                                    <?= $typeInfo['badge'] ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <h3 class="text-xs font-semibold text-slate-900 truncate w-full group-hover:text-brand-600 mb-1" title="<?= htmlspecialchars($file['original_name']) ?>">
                                            <?= htmlspecialchars($file['original_name']) ?>
                                        </h3>
                                    </div>

                                    <!-- Bottom Meta: Size & Date -->
                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-mono">
                                        <span><?= StorageService::formatBytes((int)$file['file_size']) ?></span>
                                        <span>•</span>
                                        <span><?= date('d M Y', strtotime($file['created_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>

                        <!-- LIST / TABLE VIEW CONTAINER -->
                        <div id="filesListView" class="hidden bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse" id="filesTable">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            <th class="py-3 px-4 w-10">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                            </th>
                                            <th class="py-3 px-4">Nama Berkas</th>
                                            <th class="py-3 px-4">Ukuran</th>
                                            <th class="py-3 px-4">Status Berbagi</th>
                                            <th class="py-3 px-4">Tanggal Unggah</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-sm">
                                        <?php foreach ($files as $file): ?>
                                            <?php 
                                            $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']); 
                                            $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                            $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                            $fileJson = htmlspecialchars(json_encode([
                                                'id' => (int)$file['id'],
                                                'name' => $file['original_name'],
                                                'size' => (int)$file['file_size'],
                                                'formatted_size' => StorageService::formatBytes((int)$file['file_size']),
                                                'mime' => $file['mime_type'],
                                                'type' => $typeInfo['type'],
                                                'badge' => $typeInfo['badge'],
                                                'bg' => $typeInfo['bg'],
                                                'created_at' => date('d M Y, H:i', strtotime($file['created_at'])),
                                                'is_shared' => $hasPublicShare,
                                                'share_token' => $file['share_token'] ?? '',
                                                'user_shares_count' => $directShareCount,
                                            ]), ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <tr class="file-row draggable-file hover:bg-slate-50/70 transition-colors cursor-grab active:cursor-grabbing" 
                                                draggable="true"
                                                data-id="<?= $file['id'] ?>"
                                                data-category="<?= htmlspecialchars($typeInfo['type']) ?>"
                                                data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                                data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                                data-filesize="<?= (int)$file['file_size'] ?>"
                                                data-created="<?= strtotime($file['created_at']) ?>"
                                                data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>"
                                                data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>"
                                                data-filejson="<?= $fileJson ?>">
                                                <td class="py-3 px-4">
                                                    <input type="checkbox" class="file-select-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer" 
                                                           data-id="<?= $file['id'] ?>" 
                                                           onchange="onFileCheckboxChange(this)"
                                                           onclick="event.stopPropagation()">
                                                </td>
                                                <td class="py-3 px-4 font-semibold text-slate-800 flex items-center space-x-2.5">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold <?= $typeInfo['bg'] ?>">
                                                        <?= $typeInfo['badge'] ?>
                                                    </span>
                                                    <span class="truncate max-w-xs sm:max-w-md"><?= htmlspecialchars($file['original_name']) ?></span>
                                                </td>
                                                <td class="py-3 px-4 text-xs font-mono text-slate-600">
                                                    <?= StorageService::formatBytes((int)$file['file_size']) ?>
                                                </td>
                                                <td class="py-3 px-4 whitespace-nowrap">
                                                    <?php if ($hasPublicShare): ?>
                                                        <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($file['share_token']) ?>', this)" 
                                                                class="copy-btn inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 transition-all">
                                                            Salin Link
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-xs text-slate-400">Pribadi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-4 text-xs text-slate-500">
                                                    <?= date('d M Y, H:i', strtotime($file['created_at'])) ?>
                                                </td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                                    <div class="inline-flex items-center space-x-1.5 justify-end">
                                                        <?php if ($activeTab === 'trash'): ?>
                                                            <button type="button" 
                                                                    onclick="restoreFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                                    class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Pulihkan Berkas">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    onclick="permanentDeleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                                    class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Hapus Permanen">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" 
                                                                    onclick="openInspectorDrawer(<?= $fileJson ?>)" 
                                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Panel Inspektur Info">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    onclick="openFileDetail(<?= $fileJson ?>)" 
                                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-600 hover:text-brand-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Pratinjau Penuh">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Bagikan">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12a3 3 0 100-6 3 3 0 000 6zM18 6a3 3 0 100-6 3 3 0 000 6zM18 20a3 3 0 100-6 3 3 0 000 6z"></path>
                                                                </svg>
                                                            </button>
                                                            <a href="?action=download_direct&file_id=<?= $file['id'] ?>" 
                                                               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                               title="Unduh">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                </svg>
                                                            </a>
                                                            <button type="button" 
                                                                    onclick="openRenameModal('file', <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-amber-50 text-slate-600 hover:text-amber-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Ganti Nama">
                                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200/80 inline-flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                                                    title="Pindahkan ke Sampah">
                                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Floating Bulk Actions Bar (Sticky bottom, animated slide-up when files are selected) -->
    <div id="bulkActionBar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 backdrop-blur-md text-white border border-slate-700/80 rounded-2xl px-5 py-3 shadow-2xl flex items-center space-x-4 transition-all duration-300 transform translate-y-24 opacity-0 pointer-events-none">
        <div class="flex items-center space-x-2">
            <span class="h-6 w-6 rounded-lg bg-brand-600 flex items-center justify-center text-xs font-bold font-mono" id="bulkSelectedCount">0</span>
            <span class="text-xs font-semibold text-slate-200">berkas dipilih</span>
        </div>
        <div class="h-4 w-px bg-slate-700"></div>
        <button type="button" onclick="clearBulkSelection()" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            Batal
        </button>
        <button type="button" onclick="bulkDeleteSelected()" class="inline-flex items-center h-8 px-3 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-colors">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Hapus Terpilih
        </button>
    </div>

    <!-- Toast Notification (Mockup Aligned: Bottom-Right with Checkmark & Close Button) -->
    <div id="appToast" class="fixed bottom-6 right-6 z-50 bg-white border border-slate-200 text-slate-800 rounded-2xl px-4 py-3 shadow-2xl flex items-center space-x-3 transition-all duration-300 transform translate-y-12 opacity-0 pointer-events-none">
        <div class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <span class="text-xs font-bold text-slate-800" id="appToastMsg">Link berhasil disalin</span>
        <button type="button" onclick="hideToast()" class="text-slate-400 hover:text-slate-600 p-0.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- Right-Click Context Menu -->
    <div id="fileContextMenu" class="hidden fixed z-50 bg-white border border-slate-200 rounded-xl shadow-2xl py-1.5 w-48 text-xs font-semibold text-slate-700 animate-item-in">
        <button type="button" id="ctxBtnPreview" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Pratinjau
        </button>
        <button type="button" id="ctxBtnShare" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path></svg>
            Bagikan Berkas
        </button>
        <button type="button" id="ctxBtnCopyLink" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"></path></svg>
            Salin Link Berbagi
        </button>
        <button type="button" id="ctxBtnRename" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Ganti Nama
        </button>
        <button type="button" id="ctxBtnDuplicate" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            Duplikat Berkas
        </button>
        <a id="ctxBtnDownload" href="#" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Unduh Berkas
        </a>
        <div class="my-1 border-t border-slate-100"></div>
        <button type="button" id="ctxBtnDelete" class="w-full text-left px-3.5 py-2 hover:bg-red-50 flex items-center text-red-600">
            <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            Hapus Berkas
        </button>
    </div>

    <!-- Modal 1: Buat Folder Baru -->
    <div id="newFolderModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 animate-item-in">
            <h3 class="text-base font-bold text-slate-900 mb-1">Buat Folder Baru</h3>
            <p class="text-xs text-slate-500 mb-4">Masukkan nama untuk mengorganisir berkas Anda.</p>
            <form id="newFolderForm" onsubmit="handleCreateFolder(event)">
                <input type="text" id="newFolderName" required placeholder="Contoh: Dokumen Proyek" 
                       class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 text-sm mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('newFolderModal')" 
                            class="h-9 px-4 rounded-lg border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-9 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                        Buat Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Bagikan Berkas (Dual Tab) -->
    <div id="shareModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 animate-item-in">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Bagikan Berkas</h3>
                    <p class="text-xs text-slate-500 truncate max-w-sm" id="shareModalFileName"></p>
                </div>
                <button type="button" onclick="closeModal('shareModal')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Tab Selector di dalam Modal Share -->
            <div class="flex p-1 bg-slate-100 rounded-xl my-4">
                <button type="button" id="tabBtnPublicShare" onclick="switchShareTab('public')" 
                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-brand-600 shadow-xs">
                    Tautan Publik / Tamu
                </button>
                <button type="button" id="tabBtnUserShare" onclick="switchShareTab('user')" 
                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900">
                    Kirim ke Pengguna Terdaftar
                </button>
            </div>

            <!-- TAB 1: TAUTAN PUBLIK -->
            <div id="sharePanelPublic" class="space-y-4">
                <form id="shareForm" onsubmit="handleCreateShare(event)" class="space-y-3.5">
                    <input type="hidden" id="shareFileId">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Masa Berlaku</label>
                            <select id="shareExpiry" class="w-full h-9 px-3 rounded-lg border border-slate-300 text-slate-800 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                                <option value="never">Tanpa Batas Waktu</option>
                                <option value="1h">1 Jam</option>
                                <option value="24h">24 Jam</option>
                                <option value="7d">7 Hari</option>
                                <option value="30d">30 Hari</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Maksimum Unduhan</label>
                            <input type="number" id="shareMaxDownloads" min="1" placeholder="Tanpa batas" 
                                   class="w-full h-9 px-3 rounded-lg border border-slate-300 text-slate-800 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Proteksi Kata Sandi (Opsional)</label>
                        <input type="password" id="sharePassword" placeholder="Kosongkan jika tautan publik" 
                               class="w-full h-9 px-3 rounded-lg border border-slate-300 text-slate-800 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="shareAllowPreview" checked class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <label for="shareAllowPreview" class="ml-2 text-xs font-medium text-slate-700">Izinkan pratinjau berkas di browser</label>
                    </div>

                    <div id="shareResultBox" class="hidden pt-3 border-t border-slate-100 space-y-3">
                        <label class="block text-xs font-bold text-slate-800">Tautan Publik Siap Dibagikan:</label>
                        <div class="flex gap-2">
                            <input type="text" id="shareGeneratedUrl" readonly 
                                   class="flex-1 h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-xs font-mono">
                            <button type="button" onclick="copyGeneratedShareUrl(this)" 
                                    class="copy-btn h-9 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all whitespace-nowrap">
                                Salin
                            </button>
                        </div>

                        <!-- Instant QR Code & Quick Share Strip -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="h-16 w-16 rounded-lg bg-white p-1 border border-slate-200 shrink-0 flex items-center justify-center">
                                    <img id="shareModalQrImg" src="" alt="QR Code" class="h-full w-full object-contain">
                                </div>
                                <div class="text-xs">
                                    <span class="font-bold text-slate-800 block">Pindai QR dengan Kamera HP</span>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">Buka tautan unduhan secara instan tanpa mengetik URL.</span>
                                    <button type="button" onclick="downloadQrFromImg('shareModalQrImg')" class="mt-1 text-[11px] font-bold text-brand-600 hover:underline inline-flex items-center">
                                        ⬇ Unduh Gambar QR (PNG)
                                    </button>
                                </div>
                            </div>
                            <button type="button" id="btnRevokeModalShare"
                                    class="h-8 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-[11px] font-bold transition-colors whitespace-nowrap"
                                    title="Nonaktifkan tautan publik ini">
                                Cabut Link
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" id="btnSubmitShare"
                                class="h-9 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                            Buat Tautan Publik
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: BERBAGI ANTAR PENGGUNA TERDAFTAR -->
            <div id="sharePanelUser" class="hidden space-y-4">
                <form id="directUserShareForm" onsubmit="handleDirectUserShare(event)" class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Pilih Pengguna Tujuan</label>
                        <select id="directShareUserId" required class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 text-xs focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="">-- Pilih Pengguna Terdaftar --</option>
                            <?php foreach (($allOtherUsers ?? []) as $ou): ?>
                                <option value="<?= $ou['id'] ?>"><?= htmlspecialchars($ou['username']) ?> (<?= htmlspecialchars($ou['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">Tingkat Izin</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="directPermission" value="download" checked class="text-brand-600 focus:ring-brand-500">
                                <span class="ml-2 text-xs font-semibold text-slate-800">Boleh Unduh</span>
                            </label>
                            <label class="flex items-center p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="directPermission" value="view" class="text-brand-600 focus:ring-brand-500">
                                <span class="ml-2 text-xs font-semibold text-slate-800">Hanya Lihat (View)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="h-9 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                            Kirim Akses ke Pengguna
                        </button>
                    </div>
                </form>

                <!-- Daftar User yang Sudah Memiliki Akses -->
                <div class="pt-3 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-800 mb-2">Pengguna yang Memiliki Akses:</h4>
                    <div id="fileUserSharesList" class="space-y-1.5 max-h-40 overflow-y-auto">
                        <p class="text-xs text-slate-400">Memuat daftar...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal 3: File Detail & Preview Modal (With Gallery Navigation) -->
    <div id="fileDetailModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-item-in">
            <!-- Header -->
            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center space-x-2.5 truncate max-w-[70%]">
                    <span id="detailFileBadge" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700">FILE</span>
                    <h3 class="font-bold text-slate-800 text-sm truncate" id="detailFileName">Nama Berkas</h3>
                    <span id="detailGalleryCounter" class="hidden px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-200/70 text-slate-600 shrink-0">1 / 1</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <button type="button" id="detailBtnPrev" onclick="navigatePreviewGallery(-1)" class="hidden h-8 w-8 rounded-lg items-center justify-center text-slate-500 hover:text-brand-600 hover:bg-slate-200 transition-colors" title="Berkas Sebelumnya (←)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button type="button" id="detailBtnNext" onclick="navigatePreviewGallery(1)" class="hidden h-8 w-8 rounded-lg items-center justify-center text-slate-500 hover:text-brand-600 hover:bg-slate-200 transition-colors" title="Berkas Berikutnya (→)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <a id="detailBtnOpenNewTab" href="#" target="_blank" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-brand-600 hover:bg-slate-200 transition-colors" title="Buka di Tab Baru">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <button type="button" onclick="closeModal('fileDetailModal')" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Content Area (Preview & Meta) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Preview Canvas -->
                <div class="relative rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-center min-h-[220px]" id="detailPreviewContainer">
                    <!-- Dynamic preview loaded by JS -->
                </div>

                <!-- File Metadata Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50/70 border border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Ukuran Berkas</span>
                        <span class="font-semibold text-slate-800 font-mono" id="detailFileSize">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tipe Konten</span>
                        <span class="font-semibold text-slate-800 truncate block font-mono" id="detailFileMime">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tanggal Diunggah</span>
                        <span class="font-semibold text-slate-800" id="detailFileDate">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Status Berbagi</span>
                        <span id="detailFileShareStatus">-</span>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="h-16 px-6 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <button type="button" id="detailBtnDelete" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" id="detailBtnRename" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Ganti Nama
                    </button>
                    <button type="button" id="detailBtnShare" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12a3 3 0 100-6 3 3 0 000 6zM18 6a3 3 0 100-6 3 3 0 000 6zM18 20a3 3 0 100-6 3 3 0 000 6z"></path></svg>
                        Bagikan
                    </button>
                    <a id="detailBtnDownload" href="#" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-xs transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Unduh Berkas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- RENAME MODAL (Clean & Minimalist) -->
    <div id="renameModal" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-item-in">
            <div class="h-14 px-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 flex items-center" id="renameModalTitle">
                    <svg class="w-4 h-4 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Ganti Nama
                </h3>
                <button type="button" onclick="closeModal('renameModal')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="renameForm" onsubmit="handleRenameSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" id="renameType" value="file">
                <input type="hidden" id="renameTargetId" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" id="renameInputLabel">
                        Nama Baru
                    </label>
                    <input type="text" id="renameInput" required
                           class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all font-medium">
                    <p class="text-[11px] text-slate-400 mt-1.5" id="renameInputHelp">Masukkan nama baru tanpa karakter terlarang.</p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeModal('renameModal')" 
                            class="h-10 px-4 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitRename"
                            class="h-10 px-5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TEXT / MARKDOWN / CODE FILE CREATOR & INLINE EDITOR MODAL             -->
    <!-- ===================================================================== -->
    <div id="textEditorModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-item-in">
            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center space-x-2">
                    <span class="h-7 w-7 rounded-lg bg-brand-50 text-brand-600 border border-brand-200/80 flex items-center justify-center font-bold text-xs">📝</span>
                    <h3 id="textEditorModalTitle" class="text-sm font-bold text-slate-900">Buat Catatan / Berkas Teks Baru</h3>
                </div>
                <button type="button" onclick="closeModal('textEditorModal')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="textEditorForm" onsubmit="handleSaveTextFile(event)" class="flex-1 flex flex-col overflow-hidden p-5 space-y-3.5">
                <input type="hidden" id="textEditorFileId" value="0">

                <div id="textEditorNameRow" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex-1">
                        <label for="textEditorFilename" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Berkas</label>
                        <input type="text" id="textEditorFilename" placeholder="misal: catatan-rapat.txt atau README.md"
                               class="w-full h-10 px-3.5 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm font-mono focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                    <div class="sm:pt-5 flex items-center gap-1.5 text-[11px]">
                        <span class="text-slate-400 font-medium mr-1">Format:</span>
                        <button type="button" onclick="setTextFileExt('.txt')" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 font-mono font-bold transition-colors">.txt</button>
                        <button type="button" onclick="setTextFileExt('.md')" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 font-mono font-bold transition-colors">.md</button>
                        <button type="button" onclick="setTextFileExt('.json')" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 font-mono font-bold transition-colors">.json</button>
                        <button type="button" onclick="setTextFileExt('.csv')" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-600 font-mono font-bold transition-colors">.csv</button>
                    </div>
                </div>

                <div class="flex-1 flex flex-col min-h-[260px]">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1 font-mono">
                        <span>Isi Teks / Kode</span>
                        <span id="textEditorStats">0 baris • 0 karakter</span>
                    </div>
                    <textarea id="textEditorContent" rows="12" placeholder="Ketik catatan, dokumentasi Markdown, atau konfigurasi JSON di sini..."
                              oninput="updateTextEditorStats()"
                              class="w-full flex-1 p-3.5 rounded-xl border border-slate-300 bg-slate-950 text-slate-100 font-mono text-xs leading-relaxed focus:border-brand-600 focus:ring-1 focus:ring-brand-600 resize-y"></textarea>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400">Tersimpan langsung di folder Drive Anda saat ini</span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeModal('textEditorModal')"
                                class="h-9 px-4 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitTextEditor"
                                class="h-9 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                            Simpan Berkas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CUSTOM MODERN CONFIRMATION MODAL (Replaces native window.confirm)     -->
    <!-- ===================================================================== -->
    <div id="confirmActionModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-item-in space-y-4">
            <div class="flex items-start space-x-3.5">
                <div id="confirmModalIconBox" class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200/80 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="confirmModalTitle" class="text-sm font-bold text-slate-900">Konfirmasi Tindakan</h3>
                    <p id="confirmModalMessage" class="text-xs text-slate-600 mt-1 leading-relaxed">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" id="confirmModalCancelBtn" class="h-9 px-4 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button type="button" id="confirmModalOkBtn" class="h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- SLIDE-OVER FILE INSPECTOR DRAWER (Right-Side Detail & Quick Actions)  -->
    <!-- ===================================================================== -->
    <div id="fileInspectorBackdrop" onclick="closeInspectorDrawer()" class="hidden fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-[1px] transition-opacity"></div>
    <aside id="fileInspectorDrawer" class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-sm bg-white border-l border-slate-200 shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-out">
        <!-- Drawer Header -->
        <div class="h-16 px-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-2 truncate pr-2">
                <span id="inspectorBadge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-700">FILE</span>
                <h3 id="inspectorTitle" class="text-sm font-bold text-slate-900 truncate">Detail Berkas</h3>
            </div>
            <button type="button" onclick="closeInspectorDrawer()" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Drawer Body -->
        <div class="flex-1 overflow-y-auto p-5 space-y-5">
            <!-- Thumbnail / Visual Box -->
            <div id="inspectorPreviewBox" class="h-44 w-full rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-center overflow-hidden p-3">
                <!-- Populated dynamically -->
            </div>

            <!-- Primary Action Grid -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="inspectorBtnPreview" class="h-9 px-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold flex items-center justify-center shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Pratinjau Layar
                </button>
                <a id="inspectorBtnDownload" href="#" class="h-9 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh
                </a>
            </div>

            <!-- Metadata Specifications -->
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 space-y-3 text-xs">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Informasi Berkas</h4>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-500">Nama Lengkap</span>
                    <span id="inspectorMetaName" class="font-semibold text-slate-900 text-right break-all max-w-[180px]">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Ukuran</span>
                    <span id="inspectorMetaSize" class="font-mono font-bold text-slate-800">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Format MIME</span>
                    <span id="inspectorMetaMime" class="font-mono text-slate-700 truncate max-w-[170px]">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Diunggah</span>
                    <span id="inspectorMetaDate" class="font-semibold text-slate-700">-</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-200/70">
                    <span class="text-slate-500">Status Akses</span>
                    <span id="inspectorMetaShare">-</span>
                </div>
            </div>

            <!-- Share & QR Section inside Inspector -->
            <div id="inspectorShareCard" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-900">Tautan Publik Aktif</span>
                    <button type="button" id="inspectorBtnQr" class="text-[11px] font-bold text-emerald-700 hover:underline flex items-center">
                        Lihat QR Code →
                    </button>
                </div>
                <div class="flex gap-1.5">
                    <input type="text" id="inspectorShareUrlInput" readonly class="flex-1 h-8 px-2.5 rounded-lg border border-emerald-200 bg-white text-[11px] font-mono text-slate-700">
                    <button type="button" id="inspectorBtnCopyShare" class="h-8 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
                        Salin
                    </button>
                </div>
            </div>
        </div>

        <!-- Drawer Footer -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-2">
            <button type="button" id="inspectorBtnShare" class="flex-1 h-9 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center justify-center">
                Bagikan
            </button>
            <button type="button" id="inspectorBtnRename" class="flex-1 h-9 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center justify-center">
                Ganti Nama
            </button>
            <button type="button" id="inspectorBtnDelete" class="h-9 w-9 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center" title="Pindahkan ke Sampah">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
    </aside>

    <!-- ===================================================================== -->
    <!-- COMMAND PALETTE MODAL (Ctrl + K Spotlight Navigation & File Search)   -->
    <!-- ===================================================================== -->
    <div id="commandPaletteModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-start justify-center pt-16 sm:pt-24 p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-item-in">
            <div class="relative flex items-center px-4 border-b border-slate-100">
                <svg class="w-4 h-4 text-brand-600 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="commandPaletteInput" placeholder="Ketik nama berkas, folder, atau perintah cepat..." 
                       class="w-full h-12 text-sm text-slate-900 placeholder:text-slate-400 bg-transparent border-0 focus:outline-none focus:ring-0">
                <button type="button" onclick="closeModal('commandPaletteModal')" class="px-1.5 py-0.5 text-[10px] font-mono font-bold text-slate-400 bg-slate-100 rounded border border-slate-200">ESC</button>
            </div>
            <div id="commandPaletteResults" class="max-h-80 overflow-y-auto p-2 divide-y divide-slate-100 text-xs">
                <!-- Populated dynamically by JS -->
            </div>
            <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                <span>Gunakan klik atau <kbd class="px-1 py-0.2 bg-white border rounded font-mono">Enter</kbd> untuk membuka</span>
                <span>Cloud Sharing Command</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- QR CODE QUICK MODAL                                                   -->
    <!-- ===================================================================== -->
    <div id="qrCodeModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xs w-full p-6 shadow-2xl border border-slate-200 text-center space-y-4 animate-item-in">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600">QR Code Unduhan</span>
                <button type="button" onclick="closeModal('qrCodeModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4 bg-white rounded-2xl border border-slate-200 inline-block mx-auto shadow-xs">
                <img id="qrModalImg" src="" alt="QR Code Share" class="w-44 h-44 object-contain mx-auto">
            </div>
            <div>
                <h4 id="qrModalFileName" class="text-xs font-bold text-slate-900 truncate">Nama Berkas</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Arahkan kamera ponsel untuk membuka halaman unduhan berkas ini.</p>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" id="qrModalCopyBtn" class="flex-1 h-9 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-colors">
                    Salin Tautan
                </button>
                <button type="button" onclick="downloadQrFromImg('qrModalImg')" class="h-9 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors" title="Unduh QR sebagai PNG">
                    ⬇ PNG
                </button>
                <button type="button" onclick="closeModal('qrCodeModal')" class="h-9 px-3 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MOBILE BOTTOM NAVIGATION BAR (Responsive Touch Bar for < lg screens)  -->
    <!-- ===================================================================== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-1.5 grid grid-cols-5 gap-1 text-[10px] font-semibold shadow-lg" aria-label="Navigasi Mobile">
        <a href="?action=drive&tab=my_drive" class="flex flex-col items-center justify-center py-1 rounded-xl <?= $activeTab === 'my_drive' ? 'text-brand-600 font-bold bg-brand-50/60 dark:bg-brand-950/50' : 'text-slate-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
            <span>Drive</span>
        </a>
        <a href="?action=drive&tab=my_shares" class="relative flex flex-col items-center justify-center py-1 rounded-xl <?= $activeTab === 'my_shares' ? 'text-brand-600 font-bold bg-brand-50/60 dark:bg-brand-950/50' : 'text-slate-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
            <span>Link Publik</span>
            <?php if ($myPublicSharesCount > 0): ?>
                <span class="absolute top-0.5 right-3 w-4 h-4 rounded-full bg-emerald-500 text-white text-[9px] flex items-center justify-center font-bold"><?= $myPublicSharesCount ?></span>
            <?php endif; ?>
        </a>
        <a href="?action=drive&tab=shared_with_me" class="relative flex flex-col items-center justify-center py-1 rounded-xl <?= $activeTab === 'shared_with_me' ? 'text-brand-600 font-bold bg-brand-50/60 dark:bg-brand-950/50' : 'text-slate-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Dibagikan</span>
            <?php if (!empty($sharedWithMeFiles) && count($sharedWithMeFiles) > 0): ?>
                <span class="absolute top-0.5 right-3 w-4 h-4 rounded-full bg-brand-600 text-white text-[9px] flex items-center justify-center font-bold"><?= count($sharedWithMeFiles) ?></span>
            <?php endif; ?>
        </a>
        <a href="?action=drive&tab=recent" class="flex flex-col items-center justify-center py-1 rounded-xl <?= $activeTab === 'recent' ? 'text-brand-600 font-bold bg-brand-50/60 dark:bg-brand-950/50' : 'text-slate-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>Terbaru</span>
        </a>
        <a href="?action=drive&tab=trash" class="flex flex-col items-center justify-center py-1 rounded-xl <?= $activeTab === 'trash' ? 'text-brand-600 font-bold bg-brand-50/60 dark:bg-brand-950/50' : 'text-slate-500' ?>">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            <span>Sampah</span>
        </a>
    </nav>

</div>

<!-- Script Drive Client Interactivity -->
<script src="assets/js/drive.js?v=<?= time() ?>"></script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
