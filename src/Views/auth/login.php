<?php
$pageTitle = 'Masuk ke Akun Drive';
require_once dirname(__DIR__) . '/layouts/header.php';
?>

<style>
    /* Animasi Halus & Responsif */
    @keyframes cardEntrance {
        from {
            opacity: 0;
            transform: translateY(18px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes ambientAura {
        0%, 100% {
            opacity: 0.4;
            transform: scale(1);
        }
        50% {
            opacity: 0.65;
            transform: scale(1.08);
        }
    }

    .animate-card-enter {
        animation: cardEntrance 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .animate-ambient-glow {
        animation: ambientAura 8s ease-in-out infinite;
    }
</style>

<div class="relative min-h-full flex flex-col justify-center items-center py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 overflow-hidden">

    <!-- Ambient Subtle Background Elements -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-brand-100 rounded-full blur-3xl pointer-events-none animate-ambient-glow"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-50 rounded-full blur-3xl pointer-events-none animate-ambient-glow" style="animation-delay: -4s;"></div>

    <!-- Main Container -->
    <div class="relative w-full max-w-md animate-card-enter">
        
        <!-- Brand Header with Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-brand-600 text-white shadow-md shadow-brand-500/20 mb-4 transition-transform hover:scale-105 duration-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Cloud Sharing
            </h1>
            <p class="mt-2 text-sm text-slate-600 max-w-xs mx-auto">
                Kelola, simpan, dan bagikan berkas Anda dengan cepat dan aman
            </p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-sm py-8 px-6 sm:px-10 rounded-2xl border border-slate-200/90 shadow-xl shadow-slate-200/50">
            
            <?php if (!empty($error)): ?>
                <div class="mb-5 rounded-xl bg-red-50 p-4 border border-red-200 animate-fadeIn" role="alert">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-red-600 mt-0.5">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs sm:text-sm font-semibold text-red-800 leading-relaxed"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-5" action="?action=do_login" method="POST" id="loginForm" onsubmit="handleLoginSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Field 1: Username / Email -->
                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Pengguna atau Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input id="username" name="username" type="text" autocomplete="username" required autofocus
                            class="block w-full h-11 pl-10 pr-3.5 rounded-xl border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 text-sm transition-all"
                            placeholder="Masukkan nama pengguna">
                    </div>
                </div>

                <!-- Field 2: Password with Show/Hide Toggle -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full h-11 pl-10 pr-11 rounded-xl border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 text-sm transition-all"
                            placeholder="Masukkan kata sandi">
                        <button type="button" onclick="togglePasswordVisibility()" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                                title="Tampilkan atau sembunyikan kata sandi">
                            <svg id="eyeIconOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eyeIconClosed" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button with Interactive States -->
                <div class="pt-2">
                    <button type="submit" id="submitBtn"
                        class="relative flex w-full justify-center items-center h-12 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 active:scale-[0.99] text-sm font-bold text-white shadow-md shadow-brand-500/25 transition-all duration-150">
                        <span id="btnText">Masuk ke Drive</span>
                        <svg id="btnSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            Sistem File Sharing Cloud Mandiri. Akses terlindungi dan terenkripsi.
        </p>

    </div>
</div>

<script>
    // Fitur Toggle Show/Hide Password
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

    // Animasi Feedback saat Submit
    function handleLoginSubmit(e) {
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btnText.textContent = 'Memverifikasi...';
        spinner.classList.remove('hidden');
        btn.classList.add('opacity-90', 'cursor-wait');
    }
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
