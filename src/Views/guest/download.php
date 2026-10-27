<?php
$pageTitle = 'Unduh Berkas - ' . htmlspecialchars($share['original_name']);
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$mimeType = $share['mime_type'];
$isImage = str_starts_with($mimeType, 'image/');
$isPdf = $mimeType === 'application/pdf';
$isVideo = str_starts_with($mimeType, 'video/');
$isAudio = str_starts_with($mimeType, 'audio/');
$isText = str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/javascript']);
$canPreview = !empty($share['allow_preview']) && ($isImage || $isPdf || $isVideo || $isAudio || $isText);
?>

<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">
    <div class="sm:mx-auto sm:w-full sm:max-w-lg px-4 sm:px-0">
        
        <!-- Header Brand Icon -->
        <div class="flex justify-center mb-6">
            <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                 class="h-14 w-14 rounded-2xl shadow-md shadow-brand-500/20">
        </div>

        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
            
            <?php if (!$isUnlocked): ?>
                <!-- Form Masukkan Password (Jika diproteksi) -->
                <div class="text-center mb-6">
                    <div class="h-10 w-10 mx-auto mb-2 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">Berkas Ini Terproteksi</h2>
                    <p class="text-xs text-slate-500 mt-1">Masukkan kata sandi yang diberikan pemilik berkas untuk melanjutkan.</p>
                </div>

                <?php if (!empty($passwordError)): ?>
                    <div class="mb-4 rounded-lg bg-red-50 p-3.5 border border-red-200 text-xs font-semibold text-red-700">
                        <?= htmlspecialchars($passwordError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="?action=share&token=<?= htmlspecialchars($share['share_token']) ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div>
                        <label for="unlock_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Kata Sandi
                        </label>
                        <input type="password" id="unlock_password" name="unlock_password" required autofocus
                               placeholder="Masukkan kata sandi..." 
                               class="w-full h-11 px-3.5 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                    <button type="submit" 
                            class="w-full h-11 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors flex items-center justify-center">
                        Buka Akses Berkas
                    </button>
                </form>

            <?php else: ?>
                <!-- Tampilan Berkas Siap Diunduh -->
                <div class="flex items-start space-x-4 pb-6 border-b border-slate-100">
                    <!-- Icon File -->
                    <div class="h-14 w-14 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 flex-shrink-0">
                        <?php if ($isImage): ?>
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <?php elseif ($isPdf): ?>
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <?php elseif ($isVideo): ?>
                            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <?php else: ?>
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg font-bold text-slate-900 truncate" title="<?= htmlspecialchars($share['original_name']) ?>">
                            <?= htmlspecialchars($share['original_name']) ?>
                        </h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700"><?= StorageService::formatBytes((int)$share['file_size']) ?></span>
                            <span>•</span>
                            <span>Dibagikan oleh <strong class="text-slate-700"><?= htmlspecialchars($share['owner_username']) ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Informasi Batas Unduhan & Kedaluwarsa -->
                <div class="py-4 space-y-2 border-b border-slate-100 text-xs text-slate-600">
                    <?php if (!empty($share['expires_at'])): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Masa Berlaku Sampai:</span>
                            <span class="font-semibold text-slate-800"><?= date('d M Y, H:i', strtotime($share['expires_at'])) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($share['max_downloads'] !== null): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Sisa Kuota Unduhan:</span>
                            <span class="font-semibold text-slate-800">
                                <?= max(0, (int)$share['max_downloads'] - (int)$share['download_count']) ?> dari <?= (int)$share['max_downloads'] ?> kali
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tombol Aksi: Download & Preview -->
                <div class="pt-6 space-y-3">
                    <a href="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>"
                       class="w-full h-12 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-bold text-sm shadow-sm transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Unduh Berkas Sekarang
                    </a>

                    <?php if ($canPreview): ?>
                        <button type="button" onclick="openGuestPreview()"
                                class="w-full h-11 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Lihat Pratinjau di Browser
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Modal Preview Guest -->
                <?php if ($canPreview): ?>
                    <div id="guestPreviewModal" class="hidden fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
                            <div class="h-14 px-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                                <span class="font-bold text-slate-800 text-sm truncate"><?= htmlspecialchars($share['original_name']) ?></span>
                                <button type="button" onclick="closeGuestPreview()" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div class="flex-1 p-6 overflow-auto flex items-center justify-center bg-slate-100">
                                <?php if ($isImage): ?>
                                    <img src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="max-w-full max-h-[70vh] rounded-lg shadow-sm object-contain" alt="Preview">
                                <?php elseif ($isPdf): ?>
                                    <iframe src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="w-full h-[70vh] rounded-lg border-0"></iframe>
                                <?php elseif ($isVideo): ?>
                                    <video src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" controls class="max-w-full max-h-[70vh] rounded-lg"></video>
                                <?php elseif ($isAudio): ?>
                                    <audio src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" controls class="w-full max-w-md"></audio>
                                <?php else: ?>
                                    <iframe src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="w-full h-[70vh] bg-white rounded-lg p-4 font-mono text-xs"></iframe>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <script>
                        function openGuestPreview() {
                            document.getElementById('guestPreviewModal').classList.remove('hidden');
                        }
                        function closeGuestPreview() {
                            document.getElementById('guestPreviewModal').classList.add('hidden');
                        }
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') closeGuestPreview();
                        });
                    </script>
                <?php endif; ?>

            <?php endif; ?>

        </div>
        
        <p class="text-center text-xs text-slate-400 mt-6">
            Dihosting secara mandiri dengan sistem File Sharing Cloud yang aman dan cepat.
        </p>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
