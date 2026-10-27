<?php
$pageTitle = ($activeTab === 'shared_with_me' ? 'Dibagikan ke Saya' : 'Drive Saya') . ' - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;
?>

<style>
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(12px);
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
        animation: slideInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Gradient glow bar */
    .storage-bar-glow {
        background: linear-gradient(90deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%);
    }

    /* Tab active pill */
    .tab-active {
        background-color: #ffffff;
        color: #4f46e5;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
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
<div class="min-h-full flex flex-col bg-slate-50/80" id="driveApp" 
     data-csrf="<?= htmlspecialchars($csrfToken) ?>" 
     data-folder-id="<?= htmlspecialchars((string)($folderId ?? '')) ?>"
     data-active-tab="<?= htmlspecialchars($activeTab) ?>">

    <!-- Top Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-10 w-10 rounded-xl shadow-sm shadow-brand-500/20 group-hover:scale-105 transition-all duration-200">
                    <div>
                        <span class="font-extrabold text-slate-900 text-lg tracking-tight block leading-tight">Cloud Sharing</span>
                        <span class="text-[10px] text-slate-400 font-medium">PHP 8.4 • Redis Powered</span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links & User Menu -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="?action=users" 
                       class="inline-flex items-center h-9 px-3 text-xs font-semibold rounded-lg text-slate-700 hover:text-brand-600 hover:bg-slate-100 border border-slate-200 transition-colors">
                        <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">Kelola Pengguna</span>
                    </a>
                <?php endif; ?>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-xs font-bold text-slate-800"><?= htmlspecialchars($user['username'] ?? 'User') ?></span>
                    <span class="text-[10px] text-slate-400 uppercase font-semibold"><?= htmlspecialchars($user['role'] ?? 'Member') ?></span>
                </div>

                <a href="?action=logout" 
                   class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-lg text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors" title="Keluar dari akun">
                    <svg class="w-4 h-4 sm:mr-1 text-slate-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Top Tab Switcher & Quota Widget -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            
            <!-- Section Tabs: Drive Saya vs Dibagikan ke Saya -->
            <div class="lg:col-span-2 flex items-center">
                <div class="inline-flex p-1 bg-slate-200/70 rounded-xl space-x-1 w-full sm:w-auto">
                    <a href="?action=drive&tab=my_drive" 
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center px-4 py-2 text-xs font-bold rounded-lg transition-all <?= $activeTab === 'my_drive' ? 'tab-active' : 'text-slate-600 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-2 <?= $activeTab === 'my_drive' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        Drive Saya
                    </a>
                    
                    <a href="?action=drive&tab=shared_with_me" 
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center px-4 py-2 text-xs font-bold rounded-lg transition-all <?= $activeTab === 'shared_with_me' ? 'tab-active' : 'text-slate-600 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 mr-2 <?= $activeTab === 'shared_with_me' ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Dibagikan ke Saya
                        <?php if (!empty($sharedWithMeFiles) && count($sharedWithMeFiles) > 0): ?>
                            <span class="ml-1.5 px-1.5 py-0.2 rounded-full text-[10px] bg-brand-100 text-brand-700"><?= count($sharedWithMeFiles) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Storage Quota Status Bar with Interactive Breakdown Tooltip -->
            <div class="relative group bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-xs flex items-center justify-between cursor-help">
                <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800 flex items-center">
                            Kapasitas Storage
                            <span class="ml-1 text-[10px] text-brand-600 font-normal underline">(Rincian)</span>
                        </div>
                        <div class="text-[11px] text-slate-500 font-mono">
                            <?= StorageService::formatBytes($storageUsed) ?> / <?= StorageService::formatBytes($storageQuota) ?> (<?= $quotaPercentage ?>%)
                        </div>
                    </div>
                </div>
                <div class="w-28 sm:w-36 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="storage-bar-glow h-2 rounded-full transition-all duration-500" style="width: <?= $quotaPercentage ?>%"></div>
                </div>

                <!-- Hover Storage Breakdown Tooltip -->
                <div class="hidden group-hover:block absolute right-0 top-full mt-2 w-72 bg-slate-900 text-white rounded-xl p-3.5 shadow-2xl z-40 text-xs border border-slate-700 animate-item-in pointer-events-none">
                    <div class="font-bold border-b border-slate-800 pb-2 mb-2 flex justify-between items-center">
                        <span>Rincian Penggunaan Berkas</span>
                        <span class="text-brand-300 font-mono"><?= StorageService::formatBytes($storageUsed) ?></span>
                    </div>
                    <div class="space-y-1.5 font-mono text-[11px]">
                        <?php foreach (($storageBreakdown ?? []) as $catKey => $catData): ?>
                            <?php if ($catData['count'] > 0): ?>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center">
                                        <span class="w-2.5 h-2.5 rounded-full mr-2" style="background-color: <?= $catData['color'] ?>"></span>
                                        <?= $catData['label'] ?> (<?= $catData['count'] ?>)
                                    </span>
                                    <span class="text-slate-300"><?= StorageService::formatBytes($catData['size']) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($files) && empty($folders)): ?>
                            <p class="text-slate-400 text-xs py-1">Belum ada berkas tersimpan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar: Breadcrumbs, Search, View Mode Toggle, Upload -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
            
            <!-- Breadcrumbs Navigation -->
            <div class="flex items-center space-x-2">
                <?php if ($activeTab === 'shared_with_me'): ?>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-slate-900 text-base flex items-center">
                            <svg class="w-5 h-5 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Berkas yang Dibagikan Pengguna Lain
                        </span>
                    </div>
                <?php else: ?>
                    <nav class="flex items-center space-x-1.5 text-sm overflow-x-auto py-1" aria-label="Breadcrumb">
                        <?php foreach ($breadcrumbs as $index => $crumb): ?>
                            <?php if ($index > 0): ?>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <?php endif; ?>
                            <?php if ($index === count($breadcrumbs) - 1): ?>
                                <span class="font-bold text-slate-900 px-2 py-1 rounded bg-slate-200/70 text-xs sm:text-sm whitespace-nowrap">
                                    <?= htmlspecialchars($crumb['name']) ?>
                                </span>
                            <?php else: ?>
                                <a href="?action=drive<?= $crumb['id'] !== null ? '&folder=' . $crumb['id'] : '' ?>" 
                                   class="text-slate-600 hover:text-brand-600 font-semibold px-2 py-1 rounded hover:bg-slate-100 transition-colors text-xs sm:text-sm whitespace-nowrap">
                                    <?= htmlspecialchars($crumb['name']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Controls: Search, Sort, View Switcher (Grid/List), Actions -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- Search bar with Keyboard Shortcut Hint (/) -->
                <div class="relative w-full sm:w-56">
                    <input type="text" id="searchInput" placeholder="Cari berkas..." 
                           class="w-full h-9 pl-8 pr-8 text-xs rounded-lg border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition-all">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span class="hidden sm:inline-block absolute right-2 top-2 px-1.5 py-0.5 text-[9px] text-slate-400 bg-slate-100 border border-slate-200 rounded font-mono pointer-events-none" title="Pencarian cepat: tekan /">/</span >
                </div>

                <!-- Instant Client-Side Sort Selector -->
                <div class="relative">
                    <select id="sortSelect" onchange="sortFiles(this.value)" 
                            class="h-9 pl-3 pr-7 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:border-slate-300 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 cursor-pointer appearance-none">
                        <option value="created_desc">Terbaru</option>
                        <option value="created_asc">Terlama</option>
                        <option value="name_asc">Nama (A - Z)</option>
                        <option value="name_desc">Nama (Z - A)</option>
                        <option value="size_desc">Ukuran (Besar - Kecil)</option>
                        <option value="size_asc">Ukuran (Kecil - Besar)</option>
                    </select>
                    <svg class="w-3 h-3 text-slate-400 absolute right-2.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <!-- View Mode Switcher (Grid vs List) -->
                <div class="inline-flex p-0.5 bg-slate-100 rounded-lg border border-slate-200">
                    <button type="button" id="btnViewGrid" onclick="setViewMode('grid')" 
                            class="h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all bg-white text-brand-600 shadow-xs" title="Tampilan Kartu / Grid">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>
                    <button type="button" id="btnViewList" onclick="setViewMode('list')" 
                            class="h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all text-slate-500 hover:text-slate-800" title="Tampilan Daftar / Tabel">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

                <?php if ($activeTab === 'my_drive'): ?>
                    <!-- New Folder Button (N shortcut) -->
                    <button type="button" id="btnNewFolderModal" 
                            class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs hover:border-slate-300 transition-all" title="Folder Baru (Shortcut: N)">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        Folder Baru
                    </button>

                    <!-- Upload File Button with Micro-Animation -->
                    <label for="fileUploadInput" 
                           class="inline-flex items-center h-9 px-3.5 rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-bold shadow-xs hover:shadow-brand-500/20 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Unggah Berkas
                    </label>
                    <input type="file" id="fileUploadInput" class="hidden">
                <?php endif; ?>

            </div>
        </div>

        <!-- Animated Drag-and-Drop Dropzone Zone -->
        <?php if ($activeTab === 'my_drive'): ?>
            <div id="dropZone" class="mb-6 border-2 border-dashed border-slate-200 bg-white rounded-2xl p-6 text-center transition-all duration-300 hover:border-brand-400 hover:bg-brand-50/20">
                <div class="h-12 w-12 mx-auto mb-2 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div class="text-xs font-bold text-slate-800">Tarik & Jatuhkan berkas ke sini</div>
                <div class="text-[11px] text-slate-400 mt-0.5">atau gunakan tombol Unggah Berkas di atas</div>
            </div>

            <!-- Upload Progress Bar Container (Hidden by default) -->
            <div id="uploadProgressContainer" class="hidden mb-6 bg-white border border-slate-200 rounded-xl p-4 shadow-xs animate-item-in">
                <div class="flex items-center justify-between text-xs mb-1.5 font-bold">
                    <span id="uploadFileName" class="text-slate-800 truncate max-w-sm">Nama Berkas</span>
                    <span id="uploadPercentage" class="text-brand-600 font-mono">0%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div id="uploadProgressBar" class="h-2 rounded-full storage-bar-glow transition-all duration-200" style="width: 0%"></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================================================================= -->
        <!-- CONTENT: TAB DIBAGIKAN KE SAYA -->
        <!-- ================================================================= -->
        <?php if ($activeTab === 'shared_with_me'): ?>
            
            <div class="mt-4">
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
                    <div class="bg-white border border-slate-200/80 rounded-xl overflow-hidden shadow-xs">
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

        <!-- ================================================================= -->
        <!-- CONTENT: TAB DRIVE SAYA -->
        <!-- ================================================================= -->
        <?php else: ?>
            
            <div class="mt-6 space-y-6">

                <!-- 1. Folders Section -->
                <?php if (!empty($folders)): ?>
                    <div>
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3 flex items-center">
                            Folder (<?= count($folders) ?>)
                        </h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <?php foreach ($folders as $folder): ?>
                                <div class="group relative bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-md hover:-translate-y-0.5 rounded-xl p-3.5 flex flex-col justify-between transition-all duration-200 animate-item-in">
                                    <a href="?action=drive&folder=<?= $folder['id'] ?>" class="flex items-center space-x-2.5">
                                        <div class="text-amber-500 flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold text-slate-800 truncate group-hover:text-brand-600" title="<?= htmlspecialchars($folder['name']) ?>">
                                            <?= htmlspecialchars($folder['name']) ?>
                                        </span>
                                    </a>
                                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-end">
                                        <button type="button" onclick="deleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                                class="text-slate-400 hover:text-red-600 transition-colors p-1" title="Hapus folder">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. Files Section -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Berkas (<?= count($files) ?>)</h2>
                    </div>

                    <?php if (empty($files) && empty($folders)): ?>
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs">
                            <div class="h-16 w-16 mx-auto mb-3 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 mb-1">Folder ini masih kosong</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                                Belum ada berkas yang diunggah ke direktori ini. Mulai dengan mengunggah berkas atau membuat folder baru.
                            </p>
                            <label for="fileUploadInput" class="cursor-pointer inline-flex items-center h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Upload Berkas Sekarang
                            </label>
                        </div>
                    <?php else: ?>
                        
                        <!-- GRID VIEW CONTAINER (Default) -->
                        <div id="filesGridView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($files as $file): ?>
                                <?php
                                $typeInfo = StorageService::getFileTypeInfo($file['mime_type'], $file['original_name']);
                                $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                ?>
                                <div class="file-card group relative bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-lg hover:-translate-y-1 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 animate-item-in cursor-default"
                                     data-id="<?= $file['id'] ?>"
                                     data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>"
                                     data-rawname="<?= htmlspecialchars($file['original_name']) ?>"
                                     data-filesize="<?= (int)$file['file_size'] ?>"
                                     data-created="<?= strtotime($file['created_at']) ?>"
                                     data-mimetype="<?= htmlspecialchars($file['mime_type']) ?>"
                                     data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>"
                                     oncontextmenu="handleFileContextMenu(event, <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')">
                                    
                                    <!-- Top Card: Multi-select Checkbox, Type Badge & Sharing Badges -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-2">
                                            <!-- Checkbox for Bulk Actions -->
                                            <input type="checkbox" 
                                                   class="file-select-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer"
                                                   data-id="<?= $file['id'] ?>" 
                                                   onchange="onFileCheckboxChange(this)"
                                                   onclick="event.stopPropagation()">
                                            
                                            <div class="h-9 w-9 rounded-xl flex items-center justify-center flex-shrink-0 <?= $typeInfo['icon_bg'] ?>">
                                                <span class="text-[10px] font-black uppercase tracking-wider"><?= $typeInfo['badge'] ?></span>
                                            </div>
                                        </div>

                                        <!-- Sharing Badges -->
                                        <div class="flex items-center space-x-1">
                                            <?php if ($directShareCount > 0): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Dibagikan ke <?= $directShareCount ?> pengguna terdaftar">
                                                    👥 <?= $directShareCount ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($hasPublicShare): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Link publik aktif">
                                                    🔗 Publik
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Filename & Details -->
                                    <div class="mb-4">
                                        <h3 class="text-xs font-bold text-slate-900 truncate" title="<?= htmlspecialchars($file['original_name']) ?>">
                                            <?= htmlspecialchars($file['original_name']) ?>
                                        </h3>
                                        <div class="flex items-center space-x-2 text-[11px] text-slate-500 mt-1 font-mono">
                                            <span><?= StorageService::formatBytes((int)$file['file_size']) ?></span>
                                            <span>•</span>
                                            <span><?= date('d M Y', strtotime($file['created_at'])) ?></span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons Bar -->
                                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                        <button type="button" onclick="previewFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>')" 
                                                class="text-xs font-bold text-slate-600 hover:text-brand-600 flex items-center transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Lihat
                                        </button>

                                        <div class="flex items-center space-x-1">
                                            <!-- Share modal trigger -->
                                            <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                    class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Bagikan berkas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                            </button>

                                            <!-- Download direct -->
                                            <a href="?action=download_direct&file_id=<?= $file['id'] ?>" 
                                               class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Unduh berkas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </a>

                                            <!-- Delete -->
                                            <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                    class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus berkas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- LIST / TABLE VIEW CONTAINER -->
                        <div id="filesListView" class="hidden bg-white border border-slate-200/80 rounded-xl overflow-hidden shadow-xs">
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
                                                data-sharetoken="<?= htmlspecialchars($file['share_token'] ?? '') ?>"
                                                oncontextmenu="handleFileContextMenu(event, <?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')">
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
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    </button>
                                                    <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                            class="h-7 w-7 rounded text-slate-500 hover:text-emerald-600" title="Bagikan">
                                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
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
                </div>

            </div>

        <?php endif; ?>

    </main>

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

    <!-- Custom Right-Click Context Menu -->
    <div id="fileContextMenu" class="hidden fixed z-50 bg-white border border-slate-200 rounded-xl shadow-xl py-1.5 w-48 text-xs font-semibold text-slate-700 animate-item-in">
        <button type="button" id="ctxBtnPreview" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            Pratinjau
        </button>
        <button type="button" id="ctxBtnShare" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
            Bagikan Berkas
        </button>
        <button type="button" id="ctxBtnCopyLink" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center text-slate-700">
            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
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

    <!-- Modal 2: Bagikan Berkas (Dual Tab: Tautan Publik & Berbagi Antar Pengguna) -->
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
