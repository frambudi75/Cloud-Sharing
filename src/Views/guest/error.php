<?php
$pageTitle = 'Tautan Tidak Tersedia';
require_once dirname(__DIR__) . '/layouts/header.php';
?>

<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 text-center">
        <div class="h-16 w-16 mx-auto mb-4 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($title ?? 'Tautan Tidak Tersedia') ?></h1>
        <p class="text-sm text-slate-600 mt-2 mb-6"><?= htmlspecialchars($message ?? 'Tautan ini sudah tidak aktif atau berkas telah dihapus.') ?></p>
        <a href="?action=login" class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors">
            Kembali ke Beranda
        </a>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
