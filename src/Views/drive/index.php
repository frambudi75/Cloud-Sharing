<?php
$pageTitle = 'Drive Saya - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;
?>

<!-- Main Layout Container -->
<div class="min-h-full flex flex-col bg-slate-50" id="driveApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>" data-folder-id="<?= htmlspecialchars((string)($folderId ?? '')) ?>">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <div class="h-9 w-9 rounded-lg bg-brand-600 flex items-center justify-center text-white shadow-sm group-hover:bg-brand-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-slate-900 text-lg tracking-tight">Cloud Sharing</span>
                </a>
            </div>

            <!-- User Menu & Logout -->
            <div class="flex items-center space-x-4">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($user['username'] ?? 'User') ?></span>
                    <span class="text-xs text-slate-500 uppercase font-medium tracking-wide"><?= htmlspecialchars($user['role'] ?? 'Member') ?></span>
                </div>
                <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="?action=users" class="inline-flex items-center h-9 px-3 text-xs font-semibold rounded-lg text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Kelola Pengguna
                    </a>
                <?php endif; ?>
                <a href="?action=logout" class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-lg text-slate-700 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors" title="Keluar dari akun">
                    <svg class="w-4 h-4 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Action Bar: Breadcrumbs & Primary Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200">
            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center space-x-2 text-sm overflow-x-auto py-1" aria-label="Breadcrumb">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index > 0): ?>
                        <span class="text-slate-400">/</span>
                    <?php endif; ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <span class="font-bold text-slate-900 px-2 py-1 rounded bg-slate-100 whitespace-nowrap">
                            <?= htmlspecialchars($crumb['name']) ?>
                        </span>
                    <?php else: ?>
                        <a href="?action=drive<?= $crumb['id'] !== null ? '&folder=' . $crumb['id'] : '' ?>" 
                           class="text-slate-600 hover:text-brand-600 font-medium px-2 py-1 rounded hover:bg-slate-100 transition-colors whitespace-nowrap">
                            <?= htmlspecialchars($crumb['name']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <!-- Actions: Upload, New Folder, Search -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Search input -->
                <div class="relative w-full sm:w-60">
                    <input type="text" id="searchInput" placeholder="Cari berkas..." 
                           class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- New Folder Button -->
                <button type="button" id="btnNewFolderModal" 
                        class="inline-flex items-center h-10 px-3.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    </svg>
                    Folder Baru
                </button>

                <!-- Upload File Button -->
                <label for="fileUploadInput" 
                       class="cursor-pointer inline-flex items-center h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Upload Berkas
                </label>
                <input type="file" id="fileUploadInput" class="hidden" multiple>
            </div>
        </div>

        <!-- Storage Quota Status Bar -->
        <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="h-9 w-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-800">Kapasitas Penyimpanan</div>
                    <div class="text-xs text-slate-500">
                        <?= StorageService::formatBytes($storageUsed) ?> terpakai dari <?= StorageService::formatBytes($storageQuota) ?> (<?= $quotaPercentage ?>%)
                    </div>
                </div>
            </div>
            <div class="w-full sm:w-64 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div class="bg-brand-600 h-2.5 rounded-full transition-all duration-300" style="width: <?= $quotaPercentage ?>%"></div>
            </div>
        </div>

        <!-- Upload Progress Alert (Hidden by default) -->
        <div id="uploadProgressContainer" class="hidden mt-4 bg-white border border-brand-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-slate-800" id="uploadStatusText">Mengunggah berkas...</span>
                <span class="text-xs font-mono font-medium text-brand-600" id="uploadPercentText">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div id="uploadProgressBar" class="bg-brand-600 h-2 rounded-full transition-all duration-200" style="width: 0%"></div>
            </div>
        </div>

        <!-- Drag & Drop Dropzone Notice -->
        <div id="dropzoneOverlay" class="hidden fixed inset-0 z-50 bg-brand-900/40 backdrop-blur-sm flex items-center justify-center p-6">
            <div class="bg-white border-2 border-dashed border-brand-500 rounded-2xl p-12 text-center max-w-lg shadow-xl">
                <div class="h-16 w-16 mx-auto mb-4 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Lepaskan Berkas di Sini</h3>
                <p class="text-sm text-slate-600">Berkas Anda akan otomatis diunggah ke folder saat ini.</p>
            </div>
        </div>

        <!-- Content Area: Folders & Files -->
        <div class="mt-6 space-y-6">

            <!-- 1. Folder Section (Jika ada) -->
            <?php if (!empty($folders)): ?>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Folder (<?= count($folders) ?>)</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($folders as $folder): ?>
                            <div class="group relative bg-white border border-slate-200 hover:border-brand-300 hover:shadow-sm rounded-xl p-3 flex flex-col justify-between transition-all">
                                <a href="?action=drive&folder=<?= $folder['id'] ?>" class="flex items-center space-x-2.5">
                                    <div class="text-amber-500 flex-shrink-0">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-800 truncate group-hover:text-brand-600" title="<?= htmlspecialchars($folder['name']) ?>">
                                        <?= htmlspecialchars($folder['name']) ?>
                                    </span>
                                </a>
                                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-end">
                                    <button type="button" onclick="deleteFolder(<?= $folder['id'] ?>, '<?= htmlspecialchars(addslashes($folder['name'])) ?>')" 
                                            class="text-xs text-slate-400 hover:text-red-600 transition-colors p-1" title="Hapus folder">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
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
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Berkas (<?= count($files) ?>)</h2>
                </div>

                <?php if (empty($files) && empty($folders)): ?>
                    <!-- Empty State (Wajib sesuai antislop R-27) -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
                        <div class="h-16 w-16 mx-auto mb-4 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Folder ini masih kosong</h3>
                        <p class="text-sm text-slate-500 max-w-sm mx-auto mb-5">
                            Belum ada berkas yang diunggah ke direktori ini. Mulai dengan mengunggah berkas atau membuat folder baru.
                        </p>
                        <div class="flex justify-center gap-3">
                            <label for="fileUploadInput" class="cursor-pointer inline-flex items-center h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Upload Berkas Sekarang
                            </label>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Files Table View -->
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse" id="filesTable">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        <th class="py-3 px-4">Nama Berkas</th>
                                        <th class="py-3 px-4">Ukuran</th>
                                        <th class="py-3 px-4">Status Berbagi</th>
                                        <th class="py-3 px-4">Tanggal Unggah</th>
                                        <th class="py-3 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <?php foreach ($files as $file): ?>
                                        <tr class="file-row hover:bg-slate-50/60 transition-colors" data-filename="<?= htmlspecialchars(strtolower($file['original_name'])) ?>">
                                            <td class="py-3.5 px-4 font-medium text-slate-800 flex items-center space-x-3">
                                                <!-- Icon type -->
                                                <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 text-slate-600">
                                                    <?php if (str_starts_with($file['mime_type'], 'image/')): ?>
                                                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    <?php elseif (str_starts_with($file['mime_type'], 'video/')): ?>
                                                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    <?php elseif (str_starts_with($file['mime_type'], 'audio/')): ?>
                                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                                    <?php elseif ($file['mime_type'] === 'application/pdf'): ?>
                                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                    <?php else: ?>
                                                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="truncate max-w-xs sm:max-w-md" title="<?= htmlspecialchars($file['original_name']) ?>">
                                                    <?= htmlspecialchars($file['original_name']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                                <?= StorageService::formatBytes((int)$file['file_size']) ?>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if (!empty($file['is_shared']) && (int)$file['is_shared'] === 1): ?>
                                                    <button type="button" onclick="copyShareUrl('<?= htmlspecialchars($file['share_token']) ?>')" 
                                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                                            title="Klik untuk salin tautan">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                        Dibagikan (Salin)
                                                    </button>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-500">
                                                        Pribadi
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-500 text-xs whitespace-nowrap">
                                                <?= date('d M Y, H:i', strtotime($file['created_at'])) ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end space-x-1">
                                                    <!-- Preview Button -->
                                                    <button type="button" onclick="previewFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['mime_type']) ?>')" 
                                                            class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Lihat pratinjau">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    </button>

                                                    <!-- Share Link Button -->
                                                    <button type="button" onclick="openShareModal(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>', '<?= htmlspecialchars($file['share_token'] ?? '') ?>')" 
                                                            class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Bagikan tautan">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                                    </button>

                                                    <!-- Direct Download Button -->
                                                    <a href="?action=download_direct&file_id=<?= $file['id'] ?>" 
                                                       class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Unduh berkas">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>

                                                    <!-- Delete Button -->
                                                    <button type="button" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars(addslashes($file['original_name'])) ?>')" 
                                                            class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus berkas">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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
        </div>
    </main>

    <!-- Modal 1: Buat Folder Baru -->
    <div id="newFolderModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Buat Folder Baru</h3>
            <p class="text-sm text-slate-500 mb-4">Masukkan nama untuk mengorganisir berkas Anda.</p>
            <form id="newFolderForm" onsubmit="handleCreateFolder(event)">
                <input type="text" id="newFolderName" required placeholder="Contoh: Dokumen Proyek" 
                       class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-1 focus:ring-brand-600 text-sm mb-4">
                <div class="flex justify-end gap-2.5">
                    <button type="button" onclick="closeModal('newFolderModal')" 
                            class="h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        Buat Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Bagikan Berkas (Share Link Generator) -->
    <div id="shareModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Bagikan Berkas</h3>
            <p class="text-sm text-slate-500 mb-4 truncate" id="shareModalFileName"></p>

            <form id="shareForm" onsubmit="handleCreateShare(event)" class="space-y-4">
                <input type="hidden" id="shareFileId">

                <!-- Masa Berlaku -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Masa Berlaku Tautan</label>
                    <select id="shareExpiry" class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                        <option value="never">Tanpa Batas Waktu</option>
                        <option value="1h">1 Jam</option>
                        <option value="24h">24 Jam</option>
                        <option value="7d">7 Hari</option>
                        <option value="30d">30 Hari</option>
                    </select>
                </div>

                <!-- Proteksi Kata Sandi -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Proteksi Kata Sandi (Opsional)</label>
                    <input type="password" id="sharePassword" placeholder="Kosongkan jika tautan publik" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 placeholder:text-slate-400 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <!-- Batas Maksimum Unduhan -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Batas Maksimum Unduhan (Opsional)</label>
                    <input type="number" id="shareMaxDownloads" min="1" placeholder="Kosongkan untuk tanpa batas" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 placeholder:text-slate-400 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>

                <!-- Izinkan Preview -->
                <div class="flex items-center">
                    <input type="checkbox" id="shareAllowPreview" checked class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <label for="shareAllowPreview" class="ml-2 text-sm text-slate-700">Izinkan pratinjau berkas di browser</label>
                </div>

                <!-- Output URL box (Jika sudah tergenerate) -->
                <div id="shareResultBox" class="hidden pt-3 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tautan Siap Dibagikan:</label>
                    <div class="flex gap-2">
                        <input type="text" id="shareGeneratedUrl" readonly 
                               class="flex-1 h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-xs font-mono">
                        <button type="button" onclick="copyGeneratedShareUrl()" 
                                class="h-10 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors whitespace-nowrap">
                            Salin Link
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" onclick="closeModal('shareModal')" 
                            class="h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Tutup
                    </button>
                    <button type="submit" id="btnSubmitShare"
                            class="h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        Buat Tautan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: File Previewer Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <span class="font-bold text-slate-800 text-sm truncate" id="previewModalTitle">Pratinjau Berkas</span>
                <button type="button" onclick="closeModal('previewModal')" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex-1 p-6 overflow-auto flex items-center justify-center bg-slate-100" id="previewModalBody">
                <!-- Konten dinamis dimuat via drive.js -->
            </div>
        </div>
    </div>

</div>

<!-- Script Drive Client Interactivity -->
<script src="assets/js/drive.js"></script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
