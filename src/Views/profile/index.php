<?php
$pageTitle = 'Profil Pengguna - Cloud Sharing';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$usedBytes = (int) ($user['storage_used'] ?? 0);
$quotaBytes = (int) ($user['storage_quota'] ?? 5368709120);
$percentage = $quotaBytes > 0 ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;
$avatarUrl = !empty($user['avatar_path']) 
    ? "?action=avatar&user_id={$user['id']}&t=" . (!empty($user['updated_at']) ? strtotime($user['updated_at']) : time())
    : null;
?>

<div class="min-h-full flex flex-col bg-slate-50 antialiased" id="profileApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Top Navigation Bar (Consistent with Drive Shell) -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-xl shadow-xs group-hover:scale-105 transition-all duration-200">
                    <span class="font-extrabold text-slate-900 text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="font-semibold text-slate-700 text-xs sm:text-sm">Profil Akun</span>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="?action=users" 
                       class="inline-flex items-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-700 hover:text-brand-600 hover:bg-slate-100 border border-slate-200 transition-colors">
                        <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">Kelola Pengguna</span>
                    </a>
                <?php endif; ?>

                <a href="?action=drive" 
                   class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Buka Drive Saya
                </a>

                <a href="?action=logout" 
                   class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors" title="Keluar dari akun">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Profile Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Notification Banner if redirected -->
        <?php if (isset($_GET['status']) && $_GET['status'] === 'profile_updated'): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs animate-item-in">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Profil dan informasi akun Anda berhasil diperbarui!</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'password_updated'): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs animate-item-in">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Kata sandi akun Anda berhasil diperbarui dengan aman!</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Hero Card: Profile Header & Overview -->
        <div class="relative bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Modern Crisp Slate Banner (Subtractive Design) -->
            <div class="h-32 w-full bg-slate-800 dark:bg-slate-900 border-b border-slate-700/60 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-brand-900/30 via-slate-900/40 to-slate-800/60"></div>
                <div class="absolute inset-0 bg-[linear-gradient(to_right,#334155_1px,transparent_1px),linear-gradient(to_bottom,#334155_1px,transparent_1px)] bg-[size:1.5rem_1.5rem] opacity-20"></div>
            </div>

            <!-- Profile Overview Row -->
            <div class="px-6 pb-6 pt-0 relative flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-14">
                <div class="flex flex-col sm:flex-row items-center sm:items-end space-y-3 sm:space-y-0 sm:space-x-5 text-center sm:text-left">
                    <!-- Avatar Preview Container -->
                    <div class="relative group">
                        <div id="avatarPreviewContainer" class="h-28 w-28 rounded-3xl bg-white p-1 shadow-lg ring-4 ring-white">
                            <?php if ($avatarUrl): ?>
                                <img id="avatarImagePreview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" 
                                     class="h-full w-full rounded-2xl object-cover">
                            <?php else: ?>
                                <div id="avatarFallbackBadge" class="h-full w-full rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white font-extrabold flex items-center justify-center text-3xl shadow-inner">
                                    <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                                </div>
                                <img id="avatarImagePreview" src="" alt="Avatar" class="h-full w-full rounded-2xl object-cover hidden">
                            <?php endif; ?>
                        </div>

                        <!-- Quick Change Button Overlay -->
                        <button type="button" onclick="document.getElementById('avatarFileInput').click()" 
                                class="absolute bottom-1 right-1 h-9 w-9 rounded-xl bg-slate-900/90 text-white hover:bg-brand-600 flex items-center justify-center shadow-md transition-all group-hover:scale-105" 
                                title="Pilih foto baru">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- User Name, Email & Role -->
                    <div class="mb-1">
                        <div class="flex items-center justify-center sm:justify-start space-x-2">
                            <h1 id="headerUsernameText" class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                <?= htmlspecialchars($user['username']) ?>
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200' ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </div>
                        <p id="headerEmailText" class="text-xs text-slate-500 font-medium mt-0.5">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Bergabung sejak <?= date('d F Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>

                <!-- Storage Usage Quick Stats -->
                <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-3 sm:w-64">
                    <div class="flex justify-between items-center text-xs font-semibold text-slate-700 mb-1.5">
                        <span>Penyimpanan Terpakai</span>
                        <span class="text-brand-600 font-bold"><?= $percentage ?>%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-brand-600 h-2 rounded-full transition-all duration-500" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-mono mt-1">
                        <span><?= StorageService::formatBytes($usedBytes) ?></span>
                        <span><?= StorageService::formatBytes($quotaBytes) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2-Column Management Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ================================================================= -->
            <!-- 1. Form Edit Informasi Akun & Foto Profil (7 Cols)                -->
            <!-- ================================================================= -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Informasi Pribadi & Foto Profil</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Ubah nama tampilan, email resmi, serta foto avatar akun Anda.</p>
                        </div>
                        <div class="h-8 w-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>

                    <form id="profileForm" action="?action=update_profile" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" id="removeAvatarFlag" name="remove_avatar" value="0">

                        <!-- Foto Profil File Upload & Drop Area -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Foto Profil (Avatar)</label>
                            <div class="flex items-center space-x-4">
                                <input type="file" id="avatarFileInput" name="avatar" accept="image/png, image/jpeg, image/webp, image/gif" class="hidden" onchange="handleAvatarFileSelect(this)">
                                
                                <button type="button" onclick="document.getElementById('avatarFileInput').click()" 
                                        class="inline-flex items-center h-10 px-4 rounded-xl border border-slate-200 hover:border-brand-500 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 shadow-xs transition-colors">
                                    <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    Unggah Foto Baru
                                </button>

                                <button type="button" id="btnRemoveAvatar" onclick="handleRemoveAvatar()" 
                                        class="inline-flex items-center h-10 px-3.5 rounded-xl border border-red-200 hover:bg-red-50 text-xs font-bold text-red-600 transition-colors <?= empty($user['avatar_path']) ? 'hidden' : '' ?>">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus Foto
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2">Format yang didukung: PNG, JPG, WEBP, atau GIF (Maksimal 5 MB).</p>
                        </div>

                        <!-- Nama Pengguna (Username) -->
                        <div>
                            <label for="usernameInput" class="block text-xs font-bold text-slate-700 mb-1">
                                Nama Pengguna (Username)
                            </label>
                            <div class="relative">
                                <input type="text" id="usernameInput" name="username" required
                                       value="<?= htmlspecialchars($user['username']) ?>"
                                       class="w-full h-11 px-3.5 text-xs font-semibold rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-slate-800"
                                       placeholder="admin atau nama_anda">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">3 - 50 karakter alfanumerik, garis bawah (_), atau titik.</p>
                        </div>

                        <!-- Alamat Email -->
                        <div>
                            <label for="emailInput" class="block text-xs font-bold text-slate-700 mb-1">
                                Alamat Email
                            </label>
                            <input type="email" id="emailInput" name="email" required
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   class="w-full h-11 px-3.5 text-xs font-semibold rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-slate-800"
                                   placeholder="nama@domain.com">
                            <p class="text-[10px] text-slate-400 mt-1">Digunakan untuk menerima berkas kiriman dan masuk ke aplikasi.</p>
                        </div>

                        <!-- Feedback Message -->
                        <div id="profileMsg" class="hidden text-xs font-semibold p-3 rounded-xl"></div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="btnSaveProfile"
                                    class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white text-xs font-bold shadow-md shadow-brand-500/20 transition-all">
                                <span id="saveProfileBtnText">Simpan Perubahan Profil</span>
                                <svg id="saveProfileSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- 2. Form Keamanan & Kata Sandi (5 Cols)                            -->
            <!-- ================================================================= -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Keamanan & Kata Sandi</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Perbarui kata sandi untuk melindungi akses akun Anda.</p>
                        </div>
                        <div class="h-8 w-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>

                    <form id="passwordForm" action="?action=update_password" method="POST" class="mt-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Kata Sandi Saat Ini -->
                        <div>
                            <label for="currentPasswordInput" class="block text-xs font-bold text-slate-700 mb-1">
                                Kata Sandi Saat Ini
                            </label>
                            <div class="relative">
                                <input type="password" id="currentPasswordInput" name="current_password" required
                                       class="w-full h-11 px-3.5 pr-10 text-xs font-semibold rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-slate-800"
                                       placeholder="••••••••">
                                <button type="button" onclick="togglePasswordVisibility('currentPasswordInput', this)" 
                                        class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Kata Sandi Baru -->
                        <div>
                            <label for="newPasswordInput" class="block text-xs font-bold text-slate-700 mb-1">
                                Kata Sandi Baru
                            </label>
                            <div class="relative">
                                <input type="password" id="newPasswordInput" name="new_password" required minlength="6"
                                       oninput="checkPasswordMatch()"
                                       class="w-full h-11 px-3.5 pr-10 text-xs font-semibold rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-slate-800"
                                       placeholder="Minimal 6 karakter">
                                <button type="button" onclick="togglePasswordVisibility('newPasswordInput', this)" 
                                        class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Konfirmasi Kata Sandi Baru -->
                        <div>
                            <label for="confirmPasswordInput" class="block text-xs font-bold text-slate-700 mb-1">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <div class="relative">
                                <input type="password" id="confirmPasswordInput" name="confirm_password" required minlength="6"
                                       oninput="checkPasswordMatch()"
                                       class="w-full h-11 px-3.5 pr-10 text-xs font-semibold rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-slate-800"
                                       placeholder="Ulangi kata sandi baru">
                                <button type="button" onclick="togglePasswordVisibility('confirmPasswordInput', this)" 
                                        class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </div>
                            <p id="passwordMatchIndicator" class="text-[10px] mt-1 hidden"></p>
                        </div>

                        <!-- Feedback Message -->
                        <div id="passwordMsg" class="hidden text-xs font-semibold p-3 rounded-xl"></div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="btnSavePassword"
                                    class="w-full inline-flex items-center justify-center h-11 px-6 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[0.98] text-white text-xs font-bold shadow-md transition-all">
                                <span id="savePasswordBtnText">Perbarui Kata Sandi</span>
                                <svg id="savePasswordSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Stats Card -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Aktivitas Akun</h3>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-lg font-black text-slate-800"><?= $filesCount ?></span>
                            <span class="text-[10px] text-slate-400 font-semibold">Berkas</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-lg font-black text-slate-800"><?= $foldersCount ?></span>
                            <span class="text-[10px] text-slate-400 font-semibold">Folder</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="block text-lg font-black text-slate-800"><?= $sharedCount ?></span>
                            <span class="text-[10px] text-slate-400 font-semibold">Dibagikan</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Global Toast Feedback Component -->
    <div id="profileToast" class="fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs font-semibold px-4 py-3 rounded-2xl shadow-xl flex items-center space-x-2 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-3">
        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span id="profileToastMsg"></span>
    </div>

</div>

<script>
// =============================================================================
// Profile Management Interactive Scripts
// =============================================================================

function showToast(message, isError = false) {
    const toast = document.getElementById('profileToast');
    const msg = document.getElementById('profileToastMsg');
    if (!toast || !msg) return;

    msg.textContent = message;
    toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-3');
    toast.classList.add('opacity-100', 'translate-y-0');

    clearTimeout(window.__profileToastTimer);
    window.__profileToastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
    }, 3500);
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('text-brand-600');
    } else {
        input.type = 'password';
        btn.classList.remove('text-brand-600');
    }
}

function checkPasswordMatch() {
    const newPass = document.getElementById('newPasswordInput').value;
    const confirmPass = document.getElementById('confirmPasswordInput').value;
    const indicator = document.getElementById('passwordMatchIndicator');
    if (!indicator) return;

    if (!confirmPass) {
        indicator.classList.add('hidden');
        return;
    }

    indicator.classList.remove('hidden');
    if (newPass === confirmPass) {
        indicator.textContent = '✓ Kata sandi cocok';
        indicator.className = 'text-[10px] mt-1 text-emerald-600 font-bold';
    } else {
        indicator.textContent = '✕ Kata sandi belum cocok';
        indicator.className = 'text-[10px] mt-1 text-red-500 font-bold';
    }
}

// Live Avatar Preview
function handleAvatarFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran berkas maksimal 5 MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImg = document.getElementById('avatarImagePreview');
            const fallbackBadge = document.getElementById('avatarFallbackBadge');
            const btnRemove = document.getElementById('btnRemoveAvatar');

            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
            }
            if (fallbackBadge) {
                fallbackBadge.classList.add('hidden');
            }
            if (btnRemove) {
                btnRemove.classList.remove('hidden');
            }
            document.getElementById('removeAvatarFlag').value = '0';
        };
        reader.readAsDataURL(file);
    }
}

function handleRemoveAvatar() {
    if (!confirm('Hapus foto profil saat ini dan gunakan inisial default?')) {
        return;
    }

    document.getElementById('avatarFileInput').value = '';
    document.getElementById('removeAvatarFlag').value = '1';

    const previewImg = document.getElementById('avatarImagePreview');
    const fallbackBadge = document.getElementById('avatarFallbackBadge');
    const btnRemove = document.getElementById('btnRemoveAvatar');

    if (previewImg) {
        previewImg.classList.add('hidden');
        previewImg.src = '';
    }
    if (fallbackBadge) {
        fallbackBadge.classList.remove('hidden');
    }
    if (btnRemove) {
        btnRemove.classList.add('hidden');
    }
}

// Profile Info Form Submission
document.getElementById('profileForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    const spinner = document.getElementById('saveProfileSpinner');
    const btnText = document.getElementById('saveProfileBtnText');
    const msg = document.getElementById('profileMsg');

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Menyimpan...';
    msg.classList.add('hidden');

    const formData = new FormData(this);

    try {
        const response = await fetch('?action=update_profile', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            showToast(result.message || 'Profil berhasil diperbarui!');
            msg.className = 'text-xs font-semibold p-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200';
            msg.textContent = result.message;
            msg.classList.remove('hidden');

            // Update Header Name & Email
            if (result.username) {
                document.getElementById('headerUsernameText').textContent = result.username;
            }
            if (result.email) {
                document.getElementById('headerEmailText').textContent = result.email;
            }
        } else {
            msg.className = 'text-xs font-semibold p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
            msg.textContent = result.error || 'Gagal memperbarui profil.';
            msg.classList.remove('hidden');
        }
    } catch (err) {
        msg.className = 'text-xs font-semibold p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
        msg.textContent = 'Terjadi kesalahan jaringan.';
        msg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.textContent = 'Simpan Perubahan Profil';
    }
});

// Password Form Submission
document.getElementById('passwordForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btnSavePassword');
    const spinner = document.getElementById('savePasswordSpinner');
    const btnText = document.getElementById('savePasswordBtnText');
    const msg = document.getElementById('passwordMsg');

    const newPass = document.getElementById('newPasswordInput').value;
    const confirmPass = document.getElementById('confirmPasswordInput').value;

    if (newPass !== confirmPass) {
        msg.className = 'text-xs font-semibold p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
        msg.textContent = 'Konfirmasi kata sandi baru tidak sesuai.';
        msg.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Memperbarui...';
    msg.classList.add('hidden');

    const formData = new FormData(this);

    try {
        const response = await fetch('?action=update_password', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            showToast(result.message || 'Kata sandi berhasil diperbarui!');
            msg.className = 'text-xs font-semibold p-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200';
            msg.textContent = result.message;
            msg.classList.remove('hidden');

            document.getElementById('currentPasswordInput').value = '';
            document.getElementById('newPasswordInput').value = '';
            document.getElementById('confirmPasswordInput').value = '';
            document.getElementById('passwordMatchIndicator').classList.add('hidden');
        } else {
            msg.className = 'text-xs font-semibold p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
            msg.textContent = result.error || 'Gagal memperbarui kata sandi.';
            msg.classList.remove('hidden');
        }
    } catch (err) {
        msg.className = 'text-xs font-semibold p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
        msg.textContent = 'Terjadi kesalahan jaringan.';
        msg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.textContent = 'Perbarui Kata Sandi';
    }
});
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
