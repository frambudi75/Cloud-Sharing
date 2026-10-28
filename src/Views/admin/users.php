<?php
$pageTitle = 'Kelola Pengguna - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;

$totalUsersCount = count($users);
$adminCount = 0;
$activeCount = 0;
$suspendedCount = 0;
$totalAllocatedQuota = 0;
$totalUsedStorage = 0;

foreach ($users as $usr) {
    if (($usr['role'] ?? '') === 'admin') {
        $adminCount++;
    }
    if (($usr['status'] ?? '') === 'active') {
        $activeCount++;
    } else {
        $suspendedCount++;
    }
    $totalAllocatedQuota += (int)($usr['storage_quota'] ?? 0);
    $totalUsedStorage += (int)($usr['storage_used'] ?? 0);
}
$overallUsagePercent = $totalAllocatedQuota > 0 ? min(100, round(($totalUsedStorage / $totalAllocatedQuota) * 100, 1)) : 0;
?>

<div class="min-h-full relative flex flex-col bg-slate-50 dark:bg-slate-950 overflow-x-hidden" id="userManagementApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Subtle Ambient Background Grid -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#cbd5e1_1px,transparent_1px),linear-gradient(to_bottom,#cbd5e1_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:3rem_3rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_30%,#000_60%,transparent_100%)] opacity-35 pointer-events-none"></div>

    <!-- Top Navigation Bar -->
    <header class="relative z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-lg shadow-sm group-hover:scale-105 transition-all duration-200">
                    <span class="font-bold text-slate-900 dark:text-white text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300 text-sm">Kelola Pengguna</span>
            </div>

            <!-- Header Navigation -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <a href="?action=server" 
                   class="inline-flex items-center h-9 px-3 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                    <span class="hidden sm:inline">Pengaturan Server</span>
                </a>

                <a href="?action=drive" 
                   class="inline-flex items-center h-9 px-3 sm:px-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 sm:mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="hidden sm:inline">Buka Drive Saya</span>
                </a>

                <a href="?action=logout" class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-300 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 border border-slate-200 dark:border-slate-800 hover:border-red-200 transition-colors">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Manajemen Pengguna & Kuota Penyimpanan</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola akun, hak akses role, status aktif, dan batasan kapasitas storage masing-masing pengguna.</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Search input -->
                <div class="relative w-full sm:w-64">
                    <input type="text" id="userSearchInput" placeholder="Cari nama atau email..." 
                           class="w-full h-10 pl-9 pr-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <!-- Add User Button -->
                <button type="button" onclick="openCreateUserModal()" 
                        class="inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Tambah Pengguna
                </button>
            </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Akun -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Akun</span>
                    <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1 font-mono"><?= $totalUsersCount ?></div>
                    <span class="text-[11px] text-slate-500"><?= $adminCount ?> Admin • <?= max(0, $totalUsersCount - $adminCount) ?> User</span>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
            </div>

            <!-- Card 2: Status Akun -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Status Pengguna</span>
                    <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 font-mono"><?= $activeCount ?> Aktif</div>
                    <span class="text-[11px] text-slate-500"><?= $suspendedCount ?> Dinonaktifkan</span>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Card 3: Total Alokasi Kuota -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Alokasi Kuota</span>
                    <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1 font-mono"><?= StorageService::formatBytes($totalAllocatedQuota) ?></div>
                    <span class="text-[11px] text-slate-500">Batas gabungan seluruh akun</span>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path></svg>
                </div>
            </div>

            <!-- Card 4: Total Ruang Terpakai -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pemakaian</span>
                    <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1 font-mono"><?= StorageService::formatBytes($totalUsedStorage) ?></div>
                    <span class="text-[11px] text-slate-500"><?= $overallUsagePercent ?>% dari total kuota alokasi</span>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="usersTable">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4">Role</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Penggunaan Kuota Storage</th>
                            <th class="py-3.5 px-4">Terdaftar</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <?php foreach ($users as $u): ?>
                            <?php
                            $quotaBytes = (int)$u['storage_quota'];
                            $usedBytes = (int)$u['storage_used'];
                            $percentage = $quotaBytes > 0 ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;
                            $quotaGb = round($quotaBytes / (1024 * 1024 * 1024), 2);
                            $isCurrentAdmin = (int)$u['id'] === (int)$currentUser['id'];
                            ?>
                            <tr class="user-row hover:bg-slate-50/60 transition-colors" data-user="<?= htmlspecialchars(strtolower($u['username'] . ' ' . $u['email'])) ?>">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-9 w-9 rounded-full bg-brand-50 dark:bg-brand-950/60 text-brand-700 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/50 font-bold flex items-center justify-center text-xs uppercase flex-shrink-0">
                                            <?= htmlspecialchars(substr($u['username'], 0, 2)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 dark:text-white truncate">
                                                <?= htmlspecialchars($u['username']) ?>
                                                <?php if ($isCurrentAdmin): ?>
                                                    <span class="ml-1 text-[10px] font-bold text-brand-600 dark:text-brand-300 bg-brand-50 dark:bg-brand-950/80 px-1.5 py-0.5 rounded border border-brand-200/60 dark:border-brand-800/50">Akun Anda</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-950/60 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800/60">
                                            Administrator
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/60">
                                            Dinonaktifkan
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="w-48">
                                        <div class="flex items-center justify-between text-xs mb-1 font-mono">
                                            <span class="font-semibold text-slate-700 dark:text-slate-200"><?= StorageService::formatBytes($usedBytes) ?></span>
                                            <span class="text-slate-400">dari <?= StorageService::formatBytes($quotaBytes) ?></span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                            <div class="<?= $percentage > 90 ? 'bg-rose-500' : 'bg-brand-600' ?> h-2 rounded-full transition-all" style="width: <?= max(2, $percentage) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Edit User & Quota -->
                                        <button type="button" 
                                                onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['role'] ?>', '<?= $u['status'] ?>', <?= $quotaGb ?>)" 
                                                class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-colors" title="Ubah data & kuota">
                                            Edit
                                        </button>

                                        <!-- Reset Password -->
                                        <button type="button" 
                                                onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" 
                                                class="h-8 px-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-colors" title="Reset kata sandi">
                                            Reset Sandi
                                        </button>

                                        <!-- Delete User -->
                                        <?php if (!$isCurrentAdmin): ?>
                                            <button type="button" 
                                                    onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" 
                                                    class="h-8 px-2.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 text-xs font-semibold transition-colors" title="Hapus pengguna">
                                                Hapus
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="adminToast" class="fixed bottom-6 right-6 z-50 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-white rounded-2xl px-4 py-3 shadow-2xl flex items-center space-x-3 transition-all duration-300 transform translate-y-12 opacity-0 pointer-events-none">
        <div class="h-6 w-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <span class="text-xs font-bold" id="adminToastMsg">Perubahan disimpan</span>
    </div>

    <!-- Modal 1: Tambah Pengguna Baru -->
    <div id="createUserModal" class="hidden fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Tambah Pengguna Baru</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Buat akun pengguna baru dan tetapkan kuota penyimpanannya.</p>
            
            <form id="createUserForm" onsubmit="handleCreateUser(event)" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Nama Pengguna (Username)</label>
                    <input type="text" id="newUsername" required placeholder="misal: brian" 
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email</label>
                    <input type="email" id="newEmail" required placeholder="brian@example.com" 
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Awal</label>
                    <input type="password" id="newPassword" required placeholder="Minimal 6 karakter" minlength="6"
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Role Akun</label>
                        <select id="newRole" class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="user">User Biasa</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Kuota Storage (GB)</label>
                        <input type="number" id="newQuotaGb" required min="0.5" step="0.5" value="5" 
                               class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeModal('createUserModal')" 
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-colors">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Edit Pengguna & Kuota -->
    <div id="editUserModal" class="hidden fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Edit Pengguna & Kuota</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4" id="editModalSubtitle"></p>
            
            <form id="editUserForm" onsubmit="handleUpdateUser(event)" class="space-y-3.5">
                <input type="hidden" id="editUserId">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email</label>
                    <input type="email" id="editEmail" required 
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Role</label>
                        <select id="editRole" class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="user">User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Status Akun</label>
                        <select id="editStatus" class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="active">Aktif</option>
                            <option value="suspended">Dinonaktifkan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Kuota Penyimpanan (GB)</label>
                    <input type="number" id="editQuotaGb" required min="0.1" step="0.5" 
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    <p class="text-[11px] text-slate-500 mt-1">Batas maksimum berkas yang dapat diunggah akun ini.</p>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeModal('editUserModal')" 
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Reset Kata Sandi -->
    <div id="resetPasswordModal" class="hidden fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Reset Kata Sandi</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4" id="resetModalSubtitle"></p>
            
            <form id="resetPasswordForm" onsubmit="handleResetPassword(event)" class="space-y-3.5">
                <input type="hidden" id="resetUserId">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Baru</label>
                    <input type="password" id="resetNewPassword" required minlength="6" placeholder="Minimal 6 karakter" 
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeModal('resetPasswordModal')" 
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-colors">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Custom Confirmation Modal -->
    <div id="adminConfirmModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-start space-x-3.5">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 border border-rose-200/80 dark:border-rose-900/50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="adminConfirmTitle" class="text-sm font-bold text-slate-900 dark:text-white">Konfirmasi Hapus Pengguna</h3>
                    <p id="adminConfirmMsg" class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Apakah Anda yakin?</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" id="adminConfirmCancel" class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    Batal
                </button>
                <button type="button" id="adminConfirmOk" class="h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors">
                    Hapus Permanen
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Script Manajemen Pengguna -->
<script>
const csrfToken = document.getElementById('userManagementApp').getAttribute('data-csrf');

function showAdminToast(message) {
    const toast = document.getElementById('adminToast');
    const msg = document.getElementById('adminToastMsg');
    if (!toast || !msg) return;
    msg.textContent = message;
    toast.classList.remove('translate-y-12', 'opacity-0', 'pointer-events-none');
    toast.classList.add('translate-y-0', 'opacity-100');
    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-12', 'opacity-0', 'pointer-events-none');
    }, 2800);
}

function askAdminConfirm(title, message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('adminConfirmModal');
        document.getElementById('adminConfirmTitle').textContent = title;
        document.getElementById('adminConfirmMsg').textContent = message;
        const okBtn = document.getElementById('adminConfirmOk');
        const cancelBtn = document.getElementById('adminConfirmCancel');

        const cleanup = (val) => {
            modal.classList.add('hidden');
            okBtn.onclick = null;
            cancelBtn.onclick = null;
            resolve(val);
        };
        okBtn.onclick = () => cleanup(true);
        cancelBtn.onclick = () => cleanup(false);
        modal.classList.remove('hidden');
    });
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('hidden');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('hidden'));
    }
});

// Search filter
document.getElementById('userSearchInput').addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.user-row').forEach(row => {
        const text = row.getAttribute('data-user') || '';
        row.style.display = text.includes(q) ? '' : 'none';
    });
});

function openCreateUserModal() {
    document.getElementById('createUserForm').reset();
    openModal('createUserModal');
}

async function handleCreateUser(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('username', document.getElementById('newUsername').value.trim());
    formData.append('email', document.getElementById('newEmail').value.trim());
    formData.append('password', document.getElementById('newPassword').value);
    formData.append('role', document.getElementById('newRole').value);
    formData.append('quota_gb', document.getElementById('newQuotaGb').value);

    try {
        const res = await fetch('?action=create_user', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showAdminToast('Pengguna baru berhasil ditambahkan!');
            setTimeout(() => window.location.reload(), 400);
        } else {
            alert(json.error || 'Gagal menambahkan pengguna.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}

function openEditUserModal(id, username, email, role, status, quotaGb) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editModalSubtitle').textContent = `Mengubah data akun untuk "${username}"`;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editStatus').value = status;
    document.getElementById('editQuotaGb').value = quotaGb;
    openModal('editUserModal');
}

async function handleUpdateUser(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('user_id', document.getElementById('editUserId').value);
    formData.append('email', document.getElementById('editEmail').value.trim());
    formData.append('role', document.getElementById('editRole').value);
    formData.append('status', document.getElementById('editStatus').value);
    formData.append('quota_gb', document.getElementById('editQuotaGb').value);

    try {
        const res = await fetch('?action=update_user', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showAdminToast('Data pengguna berhasil diperbarui!');
            setTimeout(() => window.location.reload(), 400);
        } else {
            alert(json.error || 'Gagal memperbarui pengguna.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}

function openResetPasswordModal(id, username) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetModalSubtitle').textContent = `Masukkan kata sandi baru untuk "${username}"`;
    document.getElementById('resetNewPassword').value = '';
    openModal('resetPasswordModal');
}

async function handleResetPassword(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('user_id', document.getElementById('resetUserId').value);
    formData.append('new_password', document.getElementById('resetNewPassword').value);

    try {
        const res = await fetch('?action=reset_user_password', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            closeModal('resetPasswordModal');
            showAdminToast('Kata sandi pengguna berhasil diperbarui!');
        } else {
            alert(json.error || 'Gagal mengubah kata sandi.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}

async function deleteUser(id, username) {
    const ok = await askAdminConfirm(
        'Hapus Pengguna Permanen',
        `Hapus pengguna "${username}" secara permanen? Semua berkas dan foldernya di penyimpanan server akan ikut terhapus.`
    );
    if (!ok) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('user_id', id);

    try {
        const res = await fetch('?action=delete_user', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            showAdminToast(`Pengguna "${username}" berhasil dihapus.`);
            setTimeout(() => window.location.reload(), 400);
        } else {
            alert(json.error || 'Gagal menghapus pengguna.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
