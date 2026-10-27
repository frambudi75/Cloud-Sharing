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

            <!-- Storage Quota Status Bar (Compact & Animated) -->
            <div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-xs flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Kapasitas Storage</div>
                        <div class="text-[11px] text-slate-500 font-mono">
                            <?= StorageService::formatBytes($storageUsed) ?> / <?= StorageService::formatBytes($storageQuota) ?> (<?= $quotaPercentage ?>%)
                        </div>
                    </div>
                </div>
                <div class="w-28 sm:w-36 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="storage-bar-glow h-2 rounded-full transition-all duration-500" style="width: <?= $quotaPercentage ?>%"></div>
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

            <!-- Controls: Search, View Switcher (Grid/List), Actions -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- Search bar -->
                <div class="relative w-full sm:w-56">
                    <input type="text" id="searchInput" placeholder="Cari berkas..." 
                           class="w-full h-9 pl-8 pr-3 text-xs rounded-lg border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 transition-all">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
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
                    <!-- New Folder Button -->
                    <button type="button" id="btnNewFolderModal" 
                            class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs hover:border-slate-300 transition-all">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        Folder Baru
                    </button>

                    <!-- Upload File Button with Micro-Animation -->
                    <label for="fileUploadInput" 
                           class="cursor-pointer inline-flex items-center h-9 px-3.5 rounded-lg bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white text-xs font-bold shadow-xs shadow-brand-500/20 transition-all">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Upload Berkas
                    </label>
                    <input type="file" id="fileUploadInput" class="hidden" multiple>
                <?php endif; ?>

            </div>
        </div>

        <!-- Upload Progress Alert -->
        <div id="uploadProgressContainer" class="hidden mt-4 bg-white border border-brand-200 rounded-xl p-4 shadow-sm animate-item-in">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-800" id="uploadStatusText">Mengunggah berkas...</span>
                <span class="text-xs font-mono font-bold text-brand-600" id="uploadPercentText">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div id="uploadProgressBar" class="storage-bar-glow h-2 rounded-full transition-all duration-200" style="width: 0%"></div>
            </div>
        </div>

        <!-- Drag & Drop Dropzone Notice -->
        <div id="dropzoneOverlay" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-6">
            <div class="bg-white border-2 border-dashed border-brand-500 rounded-2xl p-10 text-center max-w-md shadow-2xl animate-item-in">
                <div class="h-16 w-16 mx-auto mb-3 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center animate-bounce">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-1">Lepaskan Berkas di Sini</h3>
                <p class="text-xs text-slate-500">Berkas Anda akan otomatis diunggah ke folder saat ini.</p>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CONTENT: TAB DIBAGIKAN KE SAYA -->
        <!-- ================================================================= -->
        <?php if ($activeTab === 'shared_with_me'): ?>
            <div class="mt-6">
                <?php if (empty($sharedWithMeFiles)): ?>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs">
                        <div class="h-16 w-16 mx-auto mb-3 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Belum ada berkas yang dibagikan</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Ketika pengguna lain membagikan berkas langsung ke akun Anda, berkas tersebut akan muncul di halaman ini.
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
                                        <th class="py-3 px-4">Izin Akses</th>
                                        <th class="py-3 px-4">Ukuran</th>
                                        <th class="py-3 px-4">Tanggal Berbagi</th>
                                        <th class="py-3 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <?php foreach ($sharedWithMeFiles as $item): ?>
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="py-3.5 px-4 font-semibold text-slate-900 flex items-center space-x-3">
                                                <div class="h-9 w-9 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 flex-shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                </div>
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
                            <div class="h-16 w-16 mx-auto mb-3 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 mb-1">Folder ini masih kosong</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                                Belum ada berkas yang diunggah ke direktori ini. Mulai dengan mengunggah berkas atau membuat folder baru.
                            </p>
                            <label for="fileUploadInput" class="cursor-pointer inline-flex items-center h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors">
                                Upload Berkas Sekarang
                            </label>
                        </div>
                    <?php else: ?>
                        
                        <!-- GRID VIEW CONTAINER (Default) -->
                        <div id="filesGridView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($files as $file): ?>
                                <?php
                                $isImg = str_starts_with($file['mime_type'], 'image/');
                                $isPdf = $file['mime_type'] === 'application/pdf';
                                $isVid = str_starts_with($file['mime_type'], 'video/');
                                $isAud = str_starts_with($file['mime_type'], 'audio/');
                                $hasPublicShare = !empty($file['is_shared']) && (int)$file['is_shared'] === 1;
                                $directShareCount = (int)($file['user_shares_count'] ?? 0);
                                ?>
                                <div class="file-card group bg-white border border-slate-200/80 hover:border-brand-300 hover:shadow-lg hover:-translate-y-1 rounded-2xl p-4 flex flex-col justify-between transition-all duration-200 animate-item-in"
                                     data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>">
                                    
                                    <!-- Top Card: File Type Badge & Actions -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 <?= $isImg ? 'bg-indigo-50 text-indigo-600' : ($isPdf ? 'bg-red-50 text-red-600' : ($isVid ? 'bg-rose-50 text-rose-600' : ($isAud ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-600'))) ?>">
                                            <?php if ($isImg): ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?php elseif ($isPdf): ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            <?php elseif ($isVid): ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            <?php else: ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <?php endif; ?>
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

                        <!-- LIST / TABLE VIEW CONTAINER (Tersedia opsi switch) -->
                        <div id="filesListView" class="hidden bg-white border border-slate-200/80 rounded-xl overflow-hidden shadow-xs">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse" id="filesTable">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                            <th class="py-3 px-4">Nama Berkas</th>
                                            <th class="py-3 px-4">Ukuran</th>
                                            <th class="py-3 px-4">Status Berbagi</th>
                                            <th class="py-3 px-4">Tanggal Unggah</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-sm">
                                        <?php foreach ($files as $file): ?>
                                            <tr class="file-row hover:bg-slate-50/70 transition-colors" data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>">
                                                <td class="py-3 px-4 font-semibold text-slate-800 flex items-center space-x-3">
                                                    <span class="truncate max-w-xs sm:max-w-md"><?= htmlspecialchars($file['original_name']) ?></span>
                                                </td>
                                                <td class="py-3 px-4 text-xs font-mono text-slate-600">
                                                    <?= StorageService::formatBytes((int)$file['file_size']) ?>
                                                </td>
                                                <td class="py-3 px-4 whitespace-nowrap">
                                                    <?php if (!empty($file['is_shared']) && (int)$file['is_shared'] === 1): ?>
                                                        <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($file['share_token']) ?>')" 
                                                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
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
                            <button type="button" onclick="copyGeneratedShareUrl()" 
                                    class="h-9 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors whitespace-nowrap">
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
                            <?php foreach ($allOtherUsers as $ou): ?>
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
