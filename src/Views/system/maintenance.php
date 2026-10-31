<?php
declare(strict_types=1);
$pageTitle = 'Pemeliharaan Sistem - Cloud Sharing';
$message = $maintenanceMessage ?? 'Sistem Cloud Sharing sedang dalam proses pemeliharaan rutin untuk meningkatkan performa dan keandalan. Silakan coba kembali dalam beberapa saat.';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased flex flex-col justify-between p-4 sm:p-8">

    <!-- Ambient Texture Grid -->
    <div class="fixed inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000_60%,transparent_100%)] opacity-40 pointer-events-none"></div>

    <!-- Top Header -->
    <header class="relative z-10 max-w-4xl w-full mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" class="w-9 h-9 rounded-xl shadow-xs">
            <span class="font-extrabold text-slate-900 dark:text-white text-base tracking-tight">Cloud Sharing</span>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60">
            <span class="w-2 h-2 rounded-full bg-amber-500 mr-2 animate-ping"></span>
            Mode Pemeliharaan
        </span>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-lg w-full mx-auto my-auto text-center py-12">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-8 sm:p-10 shadow-xl shadow-slate-200/50 dark:shadow-none space-y-6">
            
            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                🛠️
            </div>

            <div class="space-y-2">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Sedang Dalam Pemeliharaan
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed max-w-md mx-auto">
                    <?= nl2br(htmlspecialchars($message)) ?>
                </p>
            </div>

            <!-- Server Status Card -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-800 text-left space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">Status Server:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400">Maintenance Routine</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">Waktu Server:</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300"><?= date('d M Y, H:i:s T') ?></span>
                </div>
            </div>

            <!-- Refresh Button -->
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.location.reload()" 
                        class="w-full sm:w-auto inline-flex items-center justify-center h-11 px-6 rounded-xl bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-100 text-white dark:text-slate-900 text-xs font-bold shadow-xs transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Coba Muat Ulang Halaman
                </button>
                <a href="?action=login" 
                   class="w-full sm:w-auto inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all"
                   title="Masuk sebagai Administrator">
                    Login Admin →
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 max-w-4xl w-full mx-auto text-center text-xs text-slate-400 dark:text-slate-600 py-4">
        Cloud Sharing Platform &bull; Dikembangkan oleh <a href="https://github.com/frambudi75" target="_blank" rel="noopener noreferrer" class="font-bold hover:underline">Habib Frambudi (@frambudi75)</a>
    </footer>

</body>
</html>
