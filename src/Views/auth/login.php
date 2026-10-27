<?php
$pageTitle = 'Masuk ke Akun - Cloud Sharing Drive';
require_once dirname(__DIR__) . '/layouts/header.php';
?>

<div class="min-h-screen relative flex flex-col justify-between py-8 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 overflow-hidden">

    <!-- Subtle Ambient Background Grid (Anti-Slop Modern Texture) -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000_60%,transparent_100%)] opacity-40 pointer-events-none"></div>

    <!-- Top Floating Bar: Logo Text & Theme Toggle -->
    <div class="relative z-10 max-w-5xl w-full mx-auto flex items-center justify-between pb-4">
        <div class="flex items-center space-x-2.5">
            <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" class="h-8 w-8 rounded-xl shadow-xs">
            <span class="font-extrabold text-slate-900 dark:text-white text-base tracking-tight">Cloud Sharing</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/40">v1.2</span>
        </div>

        <!-- Dark Mode Toggle Button -->
        <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                class="h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors shadow-xs" 
                title="Ganti Mode Terang / Gelap">
            <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        </button>
    </div>

    <!-- Center Login Form Area -->
    <div class="relative z-10 w-full max-w-md mx-auto my-auto py-4">

        <!-- Login Card -->
        <div class="bg-white dark:bg-slate-900 py-8 px-6 sm:px-8 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all">

            <!-- Card Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-brand-50 dark:bg-brand-950/80 text-brand-600 dark:text-brand-400 border border-brand-100 dark:border-brand-900/60 shadow-xs mb-3.5">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" class="h-9 w-9">
                </div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Masuk ke Cloud Drive
                </h1>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Penyimpanan berkas mandiri berkecepatan tinggi & aman
                </p>
            </div>

            <!-- Error Notice -->
            <?php if (!empty($error)): ?>
                <div class="mb-5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 p-3.5 border border-rose-200 dark:border-rose-900/60" role="alert">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-rose-600 dark:text-rose-400 mt-0.5">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-2.5">
                            <p class="text-xs font-semibold text-rose-800 dark:text-rose-200 leading-snug"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-4" action="?action=do_login" method="POST" id="loginForm" onsubmit="handleLoginSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Field 1: Username / Email -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nama Pengguna atau Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input id="username" name="username" type="text" autocomplete="username" required autofocus
                            class="block w-full h-11 pl-10 pr-3.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 text-sm transition-colors"
                            placeholder="admin">
                    </div>
                </div>

                <!-- Field 2: Password with Show/Hide Toggle -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full h-11 pl-10 pr-10 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 text-sm transition-colors"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none"
                                title="Tampilkan atau sembunyikan kata sandi">
                            <svg id="eyeIconOpen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eyeIconClosed" class="h-4 w-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" id="submitBtn"
                        class="relative flex w-full justify-center items-center h-11 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-sm font-semibold text-white shadow-xs transition-colors">
                        <span id="btnText">Masuk ke Drive</span>
                        <svg id="btnSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

        </div>

        <!-- System Capability Pills (Informative, Delightful) -->
        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-slate-200/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs">
                ⚡ Unggah Bertahap (Chunking)
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-slate-200/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs">
                🔒 Tautan Proteksi Sandi
            </span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-slate-200/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs">
                🚀 Akselerasi Redis
            </span>
        </div>

    </div>

    <!-- Footer Copyright -->
    <div class="relative z-10 text-center text-xs text-slate-400 dark:text-slate-500 py-2 font-mono">
        &copy; <?= date('Y') ?> Cloud Sharing • PHP 8.4 Self-Hosted
    </div>

</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eyeIconOpen');
        const eyeClosed = document.getElementById('eyeIconClosed');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }

    function handleLoginSubmit(e) {
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btnText.textContent = 'Memverifikasi...';
        spinner.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-wait');
    }
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
