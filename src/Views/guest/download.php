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
$isArchive = in_array($mimeType, ['application/zip', 'application/x-rar-compressed', 'application/x-tar', 'application/x-7z-compressed', 'application/gzip']);
$canPreview = !empty($share['allow_preview']) && ($isImage || $isPdf || $isVideo || $isAudio || $isText);

// Theme color per file type
$fileTheme = [
    'bg'    => 'bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 border-brand-200/60 dark:border-brand-900/40',
    'badge' => 'FILE',
    'ring'  => 'ring-brand-500/10',
];
if ($isImage) {
    $fileTheme = ['bg' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-200/60 dark:border-emerald-900/40', 'badge' => 'GAMBAR', 'ring' => 'ring-emerald-500/10'];
} elseif ($isPdf) {
    $fileTheme = ['bg' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-200/60 dark:border-rose-900/40', 'badge' => 'PDF', 'ring' => 'ring-rose-500/10'];
} elseif ($isVideo) {
    $fileTheme = ['bg' => 'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border-purple-200/60 dark:border-purple-900/40', 'badge' => 'VIDEO', 'ring' => 'ring-purple-500/10'];
} elseif ($isAudio) {
    $fileTheme = ['bg' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-200/60 dark:border-amber-900/40', 'badge' => 'AUDIO', 'ring' => 'ring-amber-500/10'];
} elseif ($isArchive) {
    $fileTheme = ['bg' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-200/60 dark:border-indigo-900/40', 'badge' => 'ARSIP ZIP', 'ring' => 'ring-indigo-500/10'];
}
?>

<div class="min-h-screen relative flex flex-col justify-between py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 overflow-hidden">

    <!-- Subtle Ambient Background Grid (Anti-Slop Modern Texture) -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000_60%,transparent_100%)] opacity-40 pointer-events-none"></div>

    <!-- Top Floating Bar: Logo, Title & Theme Toggle -->
    <div class="relative z-10 max-w-4xl w-full mx-auto flex items-center justify-between pb-4">
        <a href="?action=drive" class="flex items-center space-x-2.5 group">
            <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" class="h-8 w-8 rounded-xl shadow-xs group-hover:scale-105 transition-transform">
            <span class="font-extrabold text-slate-900 dark:text-white text-base tracking-tight">Cloud Sharing</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/40">Tautan Berbagi</span>
        </a>

        <!-- Dark Mode Toggle Button -->
        <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                class="h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors shadow-xs" 
                title="Ganti Mode Terang / Gelap">
            <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        </button>
    </div>

    <!-- Center Card Area (Fully Responsive) -->
    <div class="relative z-10 w-full max-w-lg mx-auto my-auto py-2">

        <!-- Main Card Container -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none p-6 sm:p-8 transition-all">

            <?php if (!$isUnlocked): ?>
                <!-- STATE: Password Protected File -->
                <div class="text-center mb-6">
                    <div class="h-14 w-14 mx-auto mb-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40 flex items-center justify-center shadow-xs">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60 mb-2">
                        🔒 Akses Terproteksi
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Berkas Memerlukan Kata Sandi</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto">
                        Masukkan kata sandi rahasia yang diberikan oleh pemilik berkas untuk mengunduh.
                    </p>
                </div>

                <?php if (!empty($passwordError)): ?>
                    <div class="mb-5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 p-3.5 border border-rose-200 dark:border-rose-900/60" role="alert">
                        <div class="flex items-center space-x-2 text-rose-800 dark:text-rose-200 text-xs font-semibold">
                            <svg class="h-4 w-4 shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                            <span><?= htmlspecialchars($passwordError) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="?action=share&token=<?= htmlspecialchars($share['share_token']) ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div>
                        <label for="unlock_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Kata Sandi Berkas
                        </label>
                        <div class="relative">
                            <input type="password" id="unlock_password" name="unlock_password" required autofocus
                                   placeholder="Ketik kata sandi di sini..." 
                                   class="w-full h-12 pl-4 pr-11 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" 
                            class="w-full h-12 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 active:scale-[0.99] text-white text-sm font-bold shadow-md shadow-brand-500/20 transition-all flex items-center justify-center">
                        Buka Akses Berkas
                    </button>
                </form>

            <?php else: ?>
                <!-- STATE: File Unlocked & Ready for Download -->
                
                <!-- Status Badge -->
                <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Berkas Publik Aktif
                    </span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                        <?= $fileTheme['badge'] ?>
                    </span>
                </div>

                <!-- Hero File Display (Responsive Flex Stack) -->
                <div class="py-6 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
                    
                    <!-- File Large Icon with Color Glow -->
                    <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl <?= $fileTheme['bg'] ?> border flex items-center justify-center shrink-0 shadow-sm transition-transform hover:scale-105">
                        <?php if ($isImage): ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <?php elseif ($isPdf): ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <?php elseif ($isVideo): ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <?php elseif ($isAudio): ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        <?php elseif ($isArchive): ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        <?php else: ?>
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <?php endif; ?>
                    </div>

                    <!-- File Details -->
                    <div class="flex-1 min-w-0 w-full">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white break-words leading-snug" title="<?= htmlspecialchars($share['original_name']) ?>">
                            <?= htmlspecialchars($share['original_name']) ?>
                        </h1>
                        <div class="mt-2 flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold font-mono">
                                <?= StorageService::formatBytes((int)$share['file_size']) ?>
                            </span>
                            <span class="text-slate-400">•</span>
                            <span class="text-slate-500 dark:text-slate-400">
                                Oleh <strong class="text-slate-700 dark:text-slate-200 font-semibold"><?= htmlspecialchars($share['owner_username']) ?></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadata Cards Grid -->
                <div class="py-3 px-4 rounded-2xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200/70 dark:border-slate-800 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                    <?php if (!empty($share['expires_at'])): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Masa Berlaku:
                            </span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                <?= date('d M Y, H:i', strtotime($share['expires_at'])) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($share['max_downloads'] !== null): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Kuota Unduhan:
                            </span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                Sisa <?= max(0, (int)$share['max_downloads'] - (int)$share['download_count']) ?> dari <?= (int)$share['max_downloads'] ?> kali
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Keamanan:
                        </span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                            Terverifikasi Bebas Virus
                        </span>
                    </div>
                </div>

                <!-- Action Buttons (High Touch Targets for Mobile) -->
                <div class="pt-6 space-y-3">
                    <a href="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>"
                       class="w-full h-12 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-brand-500/25 transition-all flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Unduh Berkas Sekarang
                    </a>

                    <?php if ($canPreview): ?>
                        <button type="button" onclick="openGuestPreview()"
                                class="w-full h-11 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Pratinjau di Browser
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Modal Preview Guest -->
                <?php if ($canPreview): ?>
                    <div id="guestPreviewModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-6">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="h-14 px-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950">
                                <span class="font-bold text-slate-800 dark:text-white text-xs sm:text-sm truncate mr-3"><?= htmlspecialchars($share['original_name']) ?></span>
                                <button type="button" onclick="closeGuestPreview()" class="h-8 w-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div class="flex-1 p-3 sm:p-6 overflow-auto flex items-center justify-center bg-slate-100 dark:bg-slate-950/80">
                                <?php if ($isImage): ?>
                                    <img src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="max-w-full max-h-[70vh] rounded-xl shadow-sm object-contain" alt="Preview">
                                <?php elseif ($isPdf): ?>
                                    <iframe src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="w-full h-[70vh] rounded-xl border-0"></iframe>
                                <?php elseif ($isVideo): ?>
                                    <video src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" controls class="max-w-full max-h-[70vh] rounded-xl"></video>
                                <?php elseif ($isAudio): ?>
                                    <audio src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" controls class="w-full max-w-md"></audio>
                                <?php else: ?>
                                    <iframe src="?action=download_share&token=<?= htmlspecialchars($share['share_token']) ?>&preview=1" class="w-full h-[70vh] bg-white dark:bg-slate-900 rounded-xl p-4 font-mono text-xs"></iframe>
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
        
        <!-- Clean Footer Info -->
        <div class="text-center mt-6 space-y-1">
            <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">
                Dihosting mandiri dengan Cloud Sharing Drive • Cepat, Aman & Terenkripsi
            </p>
        </div>
    </div>

    <!-- Empty bottom space to center nicely -->
    <div class="hidden sm:block"></div>
</div>

<script>
function togglePasswordVisibility() {
    const input = document.getElementById('unlock_password');
    if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
