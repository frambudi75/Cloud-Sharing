<?php
$pageTitle = 'Kelola Pengguna - File Sharing Cloud';
require_once dirname(__DIR__) . '/layouts/header.php';
use App\Services\StorageService;
?>

<div class="min-h-full flex flex-col bg-slate-50" id="userManagementApp" data-csrf="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="?action=drive" class="flex items-center space-x-2.5 group">
                    <img src="assets/img/favicon.svg" alt="Cloud Sharing Logo" 
                         class="h-9 w-9 rounded-lg shadow-sm group-hover:scale-105 transition-all duration-200">
                    <span class="font-bold text-slate-900 text-lg tracking-tight">Cloud Sharing</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="font-semibold text-slate-700 text-sm">Kelola Pengguna</span>
            </div>

            <!-- Header Navigation -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Dark Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" onclick="toggleDarkMode()" 
                        class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors" 
                        title="Ganti Mode Terang / Gelap">
                    <svg id="themeIconSun" class="w-4 h-4 hidden text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <svg id="themeIconMoon" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </button>

                <a href="?action=server" 
                   class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                    Pengaturan Server
                </a>

                <a href="?action=drive" 
                   class="inline-flex items-center h-9 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Buka Drive Saya
                </a>
                <a href="?action=logout" class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl text-slate-700 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 transition-colors">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Manajemen Pengguna & Kuota Penyimpanan</h1>
                <p class="text-xs text-slate-500 mt-1">Kelola akun, hak akses role, status, dan batasan kapasitas storage masing-masing pengguna.</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Search input -->
                <div class="relative w-full sm:w-64">
                    <input type="text" id="userSearchInput" placeholder="Cari nama atau email..." 
                           class="w-full h-10 pl-9 pr-3 text-sm rounded-xl border border-slate-200 bg-white placeholder:text-slate-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-500/20 transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <!-- Add User Button -->
                <button type="button" onclick="openCreateUserModal()" 
                        class="inline-flex items-center h-10 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-sm font-semibold shadow-xs transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Tambah Pengguna
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="mt-6 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="usersTable">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Penggunaan Kuota Storage</th>
                            <th class="py-3 px-4">Terdaftar</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
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
                                        <div class="h-9 w-9 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs uppercase flex-shrink-0">
                                            <?= htmlspecialchars(substr($u['username'], 0, 2)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 truncate">
                                                <?= htmlspecialchars($u['username']) ?>
                                                <?php if ($isCurrentAdmin): ?>
                                                    <span class="ml-1 text-[11px] font-normal text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded">Akun Anda</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-slate-500 truncate"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                            Administrator
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                            Dinonaktifkan
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="w-48">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-slate-700"><?= StorageService::formatBytes($usedBytes) ?></span>
                                            <span class="text-slate-500">dari <?= StorageService::formatBytes($quotaBytes) ?></span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="bg-brand-600 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Edit User & Quota -->
                                        <button type="button" 
                                                onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['role'] ?>', '<?= $u['status'] ?>', <?= $quotaGb ?>)" 
                                                class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors" title="Ubah data & kuota">
                                            Edit
                                        </button>

                                        <!-- Reset Password -->
                                        <button type="button" 
                                                onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" 
                                                class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors" title="Reset kata sandi">
                                            Reset Password
                                        </button>

                                        <!-- Delete User -->
                                        <?php if (!$isCurrentAdmin): ?>
                                            <button type="button" 
                                                    onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" 
                                                    class="h-8 px-2.5 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold transition-colors" title="Hapus pengguna">
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

    <!-- Modal 1: Tambah Pengguna Baru -->
    <div id="createUserModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Tambah Pengguna Baru</h3>
            <p class="text-xs text-slate-500 mb-4">Buat akun pengguna baru dan tetapkan kuota penyimpanannya.</p>
            
            <form id="createUserForm" onsubmit="handleCreateUser(event)" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Pengguna (Username)</label>
                    <input type="text" id="newUsername" required placeholder="misal: brian" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Email</label>
                    <input type="email" id="newEmail" required placeholder="brian@example.com" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kata Sandi Awal</label>
                    <input type="password" id="newPassword" required placeholder="Minimal 6 karakter" minlength="6"
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Role Akun</label>
                        <select id="newRole" class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="user">User Biasa</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kuota Storage (GB)</label>
                        <input type="number" id="newQuotaGb" required min="0.5" step="0.5" value="5" 
                               class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('createUserModal')" 
                            class="h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Edit Pengguna & Kuota -->
    <div id="editUserModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Edit Pengguna & Kuota</h3>
            <p class="text-xs text-slate-500 mb-4" id="editModalSubtitle"></p>
            
            <form id="editUserForm" onsubmit="handleUpdateUser(event)" class="space-y-3.5">
                <input type="hidden" id="editUserId">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Email</label>
                    <input type="email" id="editEmail" required 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Role</label>
                        <select id="editRole" class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="user">User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Status Akun</label>
                        <select id="editStatus" class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-800 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                            <option value="active">Aktif</option>
                            <option value="suspended">Dinonaktifkan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kuota Penyimpanan (GB)</label>
                    <input type="number" id="editQuotaGb" required min="0.1" step="0.5" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    <p class="text-[11px] text-slate-500 mt-1">Batas maksimum berkas yang dapat diunggah akun ini.</p>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('editUserModal')" 
                            class="h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Reset Kata Sandi -->
    <div id="resetPasswordModal" class="hidden fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Reset Kata Sandi</h3>
            <p class="text-xs text-slate-500 mb-4" id="resetModalSubtitle"></p>
            
            <form id="resetPasswordForm" onsubmit="handleResetPassword(event)" class="space-y-3.5">
                <input type="hidden" id="resetUserId">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kata Sandi Baru</label>
                    <input type="password" id="resetNewPassword" required minlength="6" placeholder="Minimal 6 karakter" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-300 text-slate-900 text-sm focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                </div>
                <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('resetPasswordModal')" 
                            class="h-10 px-4 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="h-10 px-4 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Script Manajemen Pengguna -->
<script>
const csrfToken = document.getElementById('userManagementApp').getAttribute('data-csrf');

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
            window.location.reload();
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
            window.location.reload();
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
            alert('Kata sandi berhasil diubah.');
            closeModal('resetPasswordModal');
        } else {
            alert(json.error || 'Gagal mengubah kata sandi.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}

async function deleteUser(id, username) {
    if (!confirm(`Hapus pengguna "${username}" secara permanen? Semua berkas dan foldernya di disk akan terhapus.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('user_id', id);

    try {
        const res = await fetch('?action=delete_user', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.error || 'Gagal menghapus pengguna.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
