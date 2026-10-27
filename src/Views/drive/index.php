<?php
$pageTitle = ($activeTab === 'shared_with_me' ? 'Dibagikan ke Saya' : ($activeTab === 'recent' ? 'Terbaru' : 'Drive Saya')) . ' - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$totalFiles = $totalFilesCount ?? count($files);
$totalFolders = $totalFoldersCount ?? count($folders);
$totalShared = $totalSharedCount ?? 0;
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

    /* Dragover active animation */
    .dragover-active {
        animation: pulseBorder 1.5s infinite;
        background-color: #f5f3ff !important;
        border-color: #6366f1 !important;
        border-style: dashed !important;
        box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.2) !important;
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

            <!-- Right: Admin Links, User Info & Logout -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="?action=users" 
                       class="inline-flex items-center h-9 px-3.5 text-xs font-semibold rounded-xl text-slate-700 hover:text-brand-600 hover:bg-slate-100 border border-slate-200 transition-colors">
                        <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">Kelola Pengguna</span>
                    </a>
                <?php endif; ?>

                <!-- User Profile & Avatar -->
                <a href="?action=profile" class="flex items-center space-x-2.5 pl-2 group p-1.5 rounded-xl hover:bg-slate-100 transition-colors" title="Kelola Profil Saya">
                    <?php if (!empty($user['avatar_path'])): ?>
                        <img src="?action=avatar&user_id=<?= $user['id'] ?>&t=<?= !empty($user['updated_at']) ? strtotime($user['updated_at']) : time() ?>" 
                             alt="Avatar" 
                             class="h-9 w-9 rounded-xl object-cover shadow-xs ring-2 ring-white group-hover:ring-brand-400 transition-all">
                    <?php else: ?>
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white font-bold flex items-center justify-center text-xs shadow-xs ring-2 ring-white group-hover:ring-brand-400 transition-all">
                            <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="hidden sm:flex flex-col text-left leading-tight">
                        <span class="text-xs font-bold text-slate-800 group-hover:text-brand-600 transition-colors"><?= htmlspecialchars($user['username'] ?? 'User') ?></span>
                        <span class="text-[10px] text-slate-400 font-semibold uppercase"><?= htmlspecialchars($user['role'] ?? 'Member') ?></span>
                    </div>
                </a>

                <!-- Logout Button -->
                <a href="?action=logout" 
                   class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors" title="Keluar dari akun">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Application Body: Sidebar + Main Content (2-Column Modern Shell) -->
    <div class="flex-1 max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ============================================================= -->
            <!-- 1. LEFT SIDEBAR NAVIGATION (Mockup Aligned)                    -->
            <!-- ============================================================= -->
            <aside class="col-span-12 lg:col-span-3 xl:col-span-2 space-y-1">
                <nav class="space-y-1 bg-white p-2 rounded-2xl border border-slate-200/80 shadow-xs" aria-label="Sidebar">
                    
                    <!-- Drive Saya -->
                    <a href="?action=drive&tab=my_drive" 
                       class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'my_drive' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-3 <?= $activeTab === 'my_drive' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        Drive Saya
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

                    <!-- Berbintang -->
                    <a href="?action=drive&tab=starred" 
                       class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'starred' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Berbintang
                    </a>

                    <!-- Sampah -->
                    <a href="?action=drive&tab=trash" 
                       class="flex items-center px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all <?= $activeTab === 'trash' ? 'sidebar-nav-active' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Sampah
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
            </aside>

            <!-- ============================================================= -->
            <!-- 2. MAIN DRIVE CONTENT AREA (Mockup Aligned)                    -->
            <!-- ============================================================= -->
            <main class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-5">

                <!-- Header Row: Title & 4 Summary Overview Cards (Mockup Aligned) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            <?= $activeTab === 'shared_with_me' ? 'Dibagikan ke Saya' : ($activeTab === 'recent' ? 'Terbaru' : ($activeTab === 'starred' ? 'Berbintang' : ($activeTab === 'trash' ? 'Sampah' : 'Drive Saya'))) ?>
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= $activeTab === 'shared_with_me' ? 'Berkas yang dibagikan pengguna lain kepada Anda' : 'Kelola file dan folder Anda' ?>
                        </p>
                    </div>

                    <!-- 4 Summary Metric Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 w-full xl:w-auto">
                        
                        <!-- Card 1: Kapasitas Storage with mini bar & hover breakdown -->
                        <div class="relative group bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs flex items-center space-x-3 cursor-help">
                            <div class="h-9 w-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold text-slate-700 truncate">Kapasitas Storage</div>
                                <div class="text-[10px] text-slate-500 font-mono truncate">
                                    <?= StorageService::formatBytes($storageUsed) ?> / <?= StorageService::formatBytes($storageQuota) ?>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="storage-bar-glow h-1.5 rounded-full transition-all duration-500" style="width: <?= $quotaPercentage ?>%"></div>
                                </div>
                            </div>

                            <!-- Hover Tooltip -->
                            <div class="hidden group-hover:block absolute left-0 top-full mt-2 w-64 bg-slate-900 text-white rounded-xl p-3 shadow-2xl z-40 text-xs border border-slate-700 animate-item-in pointer-events-none">
                                <div class="font-bold border-b border-slate-800 pb-1.5 mb-1.5 flex justify-between">
                                    <span>Rincian Storage</span>
                                    <span class="text-brand-300 font-mono"><?= $quotaPercentage ?>%</span>
                                </div>
                                <div class="space-y-1 font-mono text-[10px]">
                                    <?php foreach (($storageBreakdown ?? []) as $catKey => $catData): ?>
                                        <?php if ($catData['count'] > 0): ?>
                                            <div class="flex items-center justify-between">
                                                <span><?= $catData['label'] ?>:</span>
                                                <span class="text-slate-300"><?= StorageService::formatBytes($catData['size']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Berkas Count -->
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs flex items-center space-x-3">
                            <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900 leading-none"><?= $totalFiles ?></div>
                                <div class="text-[11px] text-slate-500 font-semibold mt-0.5">Berkas</div>
                            </div>
                        </div>

                        <!-- Card 3: Folder Count -->
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs flex items-center space-x-3">
                            <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900 leading-none"><?= $totalFolders ?></div>
                                <div class="text-[11px] text-slate-500 font-semibold mt-0.5">Folder</div>
                            </div>
                        </div>

                        <!-- Card 4: Dibagikan Count -->
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-xs flex items-center space-x-3">
                            <div class="h-9 w-9 rounded-xl bg-indigo-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900 leading-none"><?= $totalShared ?></div>
                                <div class="text-[11px] text-slate-500 font-semibold mt-0.5">Dibagikan</div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Action Bar (Search, Sort, Grid/List Switcher, Folder Baru, Upload) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <input type="text" id="searchInput" placeholder="Cari berkas..." 
                               class="w-full h-10 pl-9 pr-8 text-xs rounded-xl border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 shadow-xs transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span class="hidden sm:inline-block absolute right-2.5 top-2.5 px-1.5 py-0.5 text-[9px] text-slate-400 bg-slate-100 border border-slate-200 rounded font-mono pointer-events-none" title="Tekan / untuk mencari">/</span >
                    </div>

                    <!-- Right Controls: Sort, View Switcher, Folder Baru, Upload Berkas -->
                    <div class="flex flex-wrap items-center gap-2">
                        
                        <!-- Sort Dropdown -->
                        <div class="relative">
                            <select id="sortSelect" onchange="sortFiles(this.value)" 
                                    class="h-10 pl-8 pr-7 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-slate-300 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 cursor-pointer appearance-none shadow-xs">
                                <option value="created_desc">Terbaru</option>
                                <option value="created_asc">Terlama</option>
                                <option value="name_asc">Nama (A - Z)</option>
                                <option value="name_desc">Nama (Z - A)</option>
                                <option value="size_desc">Ukuran (Besar)</option>
                                <option value="size_asc">Ukuran (Kecil)</option>
                            </select>
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                            </svg>
                            <svg class="w-3 h-3 text-slate-400 absolute right-2.5 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>

                        <!-- View Mode Switcher (Grid vs List) -->
                        <div class="inline-flex p-0.5 bg-slate-100 rounded-xl border border-slate-200 shadow-xs">
                            <button type="button" id="btnViewGrid" onclick="setViewMode('grid')" 
                                    class="h-9 px-2.5 rounded-lg text-xs font-semibold flex items-center transition-all bg-white text-brand-600 shadow-xs" title="Tampilan Kartu / Grid">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                            </button>
                            <button type="button" id="btnViewList" onclick="setViewMode('list')" 
                                    class="h-9 px-2.5 rounded-lg text-xs font-semibold flex items-center transition-all text-slate-500 hover:text-slate-800" title="Tampilan Daftar / Tabel">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                        </div>

                        <?php if ($activeTab === 'my_drive'): ?>
                            <!-- Folder Baru Button -->
                            <button type="button" id="btnNewFolderModal" 
                                    class="inline-flex items-center h-10 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs hover:border-slate-300 transition-all">
                                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                </svg>
                                Folder Baru
                            </button>

                            <!-- Upload Berkas Button (Primary Indigo) -->
                            <label for="fileUploadInput" 
                                   class="inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-bold shadow-md shadow-brand-500/20 transition-all cursor-pointer">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Upload Berkas
                            </label>
                            <input type="file" id="fileUploadInput" class="hidden">
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Breadcrumbs (when inside subfolders) -->
                <?php if (!empty($breadcrumbs) && count($breadcrumbs) > 1): ?>
                    <nav class="flex items-center space-x-1.5 text-xs text-slate-500 pt-1">
                        <?php foreach ($breadcrumbs as $index => $crumb): ?>
                            <?php if ($index > 0): ?>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <?php endif; ?>
                            <?php if ($index === count($breadcrumbs) - 1): ?>
                                <span class="font-bold text-slate-900 px-2 py-0.5 rounded bg-slate-200/80"><?= htmlspecialchars($crumb['name']) ?></span>
                            <?php else: ?>
                                <a href="?action=drive<?= $crumb['id'] !== null ? '&folder=' . $crumb['id'] : '' ?>" class="hover:text-brand-600 font-semibold px-1 py-0.5 rounded hover:bg-slate-100">
                                    <?= htmlspecialchars($crumb['name']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>

                <!-- Animated Drag & Drop Zone Area -->
                <?php if ($activeTab === 'my_drive'): ?>
                    <div id="dropZone" class="border-2 border-dashed border-slate-200 bg-white rounded-2xl p-5 text-center transition-all duration-300 hover:border-brand-400 hover:bg-brand-50/20">
                        <div class="flex items-center justify-center space-x-3">
                            <div class="h-9 w-9 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <span class="text-xs font-bold text-slate-800">Tarik & Jatuhkan berkas ke sini</span>
                                <span class="text-[11px] text-slate-400 block">atau gunakan tombol Upload Berkas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar for Uploads -->
                    <div id="uploadProgressContainer" class="hidden bg-white border border-slate-200 rounded-xl p-4 shadow-xs animate-item-in">
                        <div class="flex items-center justify-between text-xs mb-1.5 font-bold">
                            <span id="uploadFileName" class="text-slate-800 truncate max-w-sm">Nama Berkas</span>
                            <span id="uploadPercentage" class="text-brand-600 font-mono">0%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div id="uploadProgressBar" class="h-2 rounded-full storage-bar-glow transition-all duration-200" style="width: 0%"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ========================================================= -->
                <!-- CONTENT 1: TAB DIBAGIKAN KE SAYA                          -->
                <!-- ========================================================= -->
                <?php if ($activeTab === 'shared_with_me'): ?>
                    
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
                <!-- CONTENT 2: TAB DRIVE SAYA / RECENT                        -->
                <!-- ========================================================= -->
                <?php else: ?>

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
                                <label for="fileUploadInput" class="cursor-pointer inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Upload Berkas Sekarang
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>

                        <!-- GRID VIEW (Mockup Aligned: Clean Cards with Big Centered Icons) -->
                        <div id="filesGridView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            
                            <!-- 1. Folder Cards -->
                            <?php foreach ($folders as $folder): ?>
                                <div class="file-card group relative bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-lg hover:-translate-y-0.5 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 animate-item-in">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Folder</span>
                                        <button type="button" onclick="deleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                                class="text-slate-400 hover:text-red-600 transition-colors p-1 rounded-lg hover:bg-red-50" title="Hapus folder">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Big Centered Folder Icon (Mockup Aligned) -->
                                    <a href="?action=drive&folder=<?= $folder['id'] ?>" class="my-3 flex flex-col items-center text-center">
                                        <div class="h-16 w-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                                            <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xs font-bold text-slate-900 mt-2 truncate w-full group-hover:text-brand-600" title="<?= htmlspecialchars($folder['name']) ?>">
                                            <?= htmlspecialchars($folder['name']) ?>
                                        </h3>
                                    </a>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                        <span>Folder</span>
                                        <span><?= date('d M Y', strtotime($folder['updated_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- 2. File Cards (Mockup Aligned: Big Icon, Three-dot Menu, Size & Date) -->
                            <?php foreach ($files as $file): ?>
                                <?php
                                $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']);
                                $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                $ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                ?>
                                <div class="file-card group relative bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-lg hover:-translate-y-0.5 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 animate-item-in"
                                     data-id="<?= $file['id'] ?>"
                                     data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                     data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                     data-filesize="<?= (int)$file['file_size'] ?>"
                                     data-created="<?= strtotime($file['created_at']) ?>"
                                     data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>"
                                     data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>"
                                     oncontextmenu="handleFileContextMenu(event, <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')">
                                    
                                    <!-- Card Header: Checkbox & Three-dot Actions Menu -->
                                    <div class="flex items-center justify-between">
                                        <input type="checkbox" 
                                               class="file-select-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer"
                                               data-id="<?= $file['id'] ?>" 
                                               onchange="onFileCheckboxChange(this)"
                                               onclick="event.stopPropagation()">

                                        <!-- Three-dot menu button & popover (Mockup Aligned) -->
                                        <div class="relative">
                                            <button type="button" 
                                                    onclick="toggleCardMenu(event, 'cardMenu_<?= $file['id'] ?>')" 
                                                    class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                </svg>
                                            </button>

                                            <!-- In-Card Action Dropdown (Mockup Aligned) -->
                                            <div id="cardMenu_<?= $file['id'] ?>" class="card-action-menu hidden absolute right-0 top-8 z-30 w-36 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs font-semibold text-slate-700 animate-item-in">
                                                <button type="button" onclick="previewFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>')" 
                                                        class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    Pratinjau
                                                </button>
                                                <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($file['share_token'] ?? '') ?>', this)" 
                                                        class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"></path></svg>
                                                    Salin Link
                                                </button>
                                                <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                        class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path></svg>
                                                    Bagikan
                                                </button>
                                                <a href="?action=download_direct&file_id=<?= $file['id'] ?>" 
                                                   class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center text-slate-700">
                                                    <svg class="w-3.5 h-3.5 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    Download
                                                </a>
                                                <div class="my-1 border-t border-slate-100"></div>
                                                <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                        class="w-full text-left px-3 py-1.5 hover:bg-red-50 text-red-600 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Big Centered Distinctive File Icon (Mockup Aligned) -->
                                    <div class="my-3 flex flex-col items-center text-center cursor-pointer" 
                                         onclick="previewFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>')">
                                        
                                        <?php if ($typeInfo['type'] === 'pdf'): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-red-500 text-white flex flex-col items-center justify-center font-black shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                <span class="text-[9px] tracking-wider font-extrabold leading-none">PDF</span>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'image'): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-sky-400 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'video'): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'archive'): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-purple-500 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                            </div>
                                        <?php elseif (in_array($ext, ['xls', 'xlsx', 'csv'])): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                                            </div>
                                        <?php elseif ($typeInfo['type'] === 'document'): ?>
                                            <div class="h-16 w-16 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="h-16 w-16 rounded-2xl bg-slate-200 text-slate-600 flex items-center justify-center group-hover:scale-105 transition-transform">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                                            </div>
                                        <?php endif; ?>

                                        <h3 class="text-xs font-bold text-slate-900 mt-2 truncate w-full group-hover:text-brand-600" title="<?= htmlspecialchars($file['original_name']) ?>">
                                            <?= htmlspecialchars($file['original_name']) ?>
                                        </h3>
                                    </div>

                                    <!-- Bottom Meta: Size & Date (Mockup Aligned) -->
                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-mono">
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
                                            <?php $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']); ?>
                                            <tr class="file-row hover:bg-slate-50/70 transition-colors" 
                                                data-id="<?= $file['id'] ?>"
                                                data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                                data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                                data-filesize="<?= (int)$file['file_size'] ?>"
                                                data-created="<?= strtotime($file['created_at']) ?>"
                                                data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>"
                                                data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>">
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
                                                    <?php if (!empty($file['is_shared']) && (int)$file['is_shared'] === 1): ?>
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
                                                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                                    <button type="button" onclick="previewFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>')" 
                                                            class="h-7 w-7 rounded text-slate-500 hover:text-brand-600" title="Pratinjau">
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    </button>
                                                    <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                            class="h-7 w-7 rounded text-slate-500 hover:text-emerald-600" title="Bagikan">
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316"></path></svg>
                                                    </button>
                                                    <a href="?action=download_direct&file_id=<?= $file['id'] ?>" class="h-7 w-7 rounded text-slate-500 hover:text-brand-600 inline-flex items-center justify-center" title="Unduh">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>
                                                    <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" class="h-7 w-7 rounded text-slate-500 hover:text-red-600" title="Hapus">
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
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

                    <div id="shareResultBox" class="hidden pt-3 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-800 mb-1">Tautan Publik Siap Dibagikan:</label>
                        <div class="flex gap-2">
                            <input type="text" id="shareGeneratedUrl" readonly 
                                   class="flex-1 h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-xs font-mono">
                            <button type="button" onclick="copyGeneratedShareUrl(this)" 
                                    class="copy-btn h-9 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all whitespace-nowrap">
                                Salin
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

    <!-- Modal 3: File Previewer Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-item-in">
            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <span class="font-bold text-slate-800 text-sm truncate" id="previewModalTitle">Pratinjau Berkas</span>
                <button type="button" onclick="closeModal('previewModal')" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex-1 p-6 overflow-auto flex items-center justify-center bg-slate-100" id="previewModalBody"></div>
        </div>
    </div>

</div>

<!-- Script Drive Client Interactivity -->
<script src="assets/js/drive.js"></script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
