<?php
$pageTitle = 'Setup Database - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
?>

<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">
    <div class="sm:mx-auto sm:w-full sm:max-w-lg px-4 sm:px-0">
        
        <div class="flex justify-center mb-6">
            <div class="h-14 w-14 rounded-2xl bg-amber-500 flex items-center justify-center text-white shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-2xl sm:px-10">
            <h1 class="text-xl font-bold text-slate-900 text-center mb-1">
                Database Belum Terhubung
            </h1>
            <p class="text-xs text-slate-500 text-center mb-6">
                Sistem mendeteksi bahwa database MySQL belum aktif atau tabel belum diimpor.
            </p>

            <div class="mb-5 rounded-lg bg-amber-50 p-4 border border-amber-200 text-xs text-amber-800">
                <div class="font-bold mb-1">Pesan Error Database:</div>
                <div class="font-mono text-slate-700 bg-white/70 p-2 rounded border border-amber-200/50 break-words">
                    <?= htmlspecialchars($dbError ?? 'Tidak dapat terhubung ke server MySQL.') ?>
                </div>
            </div>

            <div class="space-y-4 text-xs text-slate-600">
                <div class="font-bold text-slate-800 text-sm">Langkah Penyelesaian Mudah di XAMPP:</div>
                <ol class="list-decimal pl-5 space-y-2">
                    <li>Buka <strong>XAMPP Control Panel</strong> dan pastikan modul <strong>Apache</strong> serta <strong>MySQL</strong> dalam status <em>Running</em>.</li>
                    <li>Buka <a href="http://localhost/phpmyadmin" target="_blank" class="text-brand-600 font-semibold underline">phpMyAdmin (http://localhost/phpmyadmin)</a>.</li>
                    <li>Buka menu <strong>Import</strong>, lalu pilih berkas <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-slate-800 font-bold">database.sql</code> yang ada di dalam folder proyek Anda (<code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-slate-800">c:\xampp\htdocs\file-sharing\database.sql</code>).</li>
                    <li>Klik <strong>Import / Go</strong> di phpMyAdmin untuk membuat database dan akun admin awal.</li>
                </ol>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-100 flex justify-center">
                <a href="index.php" 
                   class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-bold text-sm shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Cek Ulang Koneksi Sekarang
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
