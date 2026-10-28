/**
 * File Sharing Cloud - Drive Client JavaScript
 * Standards: Modern Vanilla ES6+, High Accessibility, Keyboard Navigable, Zero Dead Controls
 */

document.addEventListener('DOMContentLoaded', () => {
    const driveApp = document.getElementById('driveApp');
    if (!driveApp) return;

    const csrfToken = driveApp.getAttribute('data-csrf') || '';
    const currentFolderId = driveApp.getAttribute('data-folder-id') || '';

    // =========================================================================
    // 1. Modal Helper Functions
    // =========================================================================
    window.openModal = function (modalId) {
        closeContextMenu();
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            const firstInput = modal.querySelector('input:not([type=hidden]), select, button:not([title=Batal])');
            if (firstInput) firstInput.focus();
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    // Custom Modern Confirmation Modal (Promise-based replacement for window.confirm)
    window.askConfirm = function ({
        title = 'Konfirmasi Tindakan',
        message = 'Apakah Anda yakin ingin melanjutkan?',
        confirmText = 'Ya, Lanjutkan',
        variant = 'danger'
    } = {}) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmActionModal');
            if (!modal) {
                resolve(window.confirm(message));
                return;
            }

            const titleEl = document.getElementById('confirmModalTitle');
            const msgEl = document.getElementById('confirmModalMessage');
            const okBtn = document.getElementById('confirmModalOkBtn');
            const cancelBtn = document.getElementById('confirmModalCancelBtn');
            const iconBox = document.getElementById('confirmModalIconBox');

            if (titleEl) titleEl.textContent = title;
            if (msgEl) msgEl.textContent = message;
            if (okBtn) {
                okBtn.textContent = confirmText;
                okBtn.className = variant === 'warning'
                    ? 'h-9 px-4 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition-colors'
                    : 'h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors';
            }
            if (iconBox) {
                iconBox.className = variant === 'warning'
                    ? 'h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center shrink-0'
                    : 'h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200/80 flex items-center justify-center shrink-0';
            }

            const cleanup = (result) => {
                modal.classList.add('hidden');
                if (okBtn) okBtn.onclick = null;
                if (cancelBtn) cancelBtn.onclick = null;
                resolve(result);
            };

            if (okBtn) okBtn.onclick = () => cleanup(true);
            if (cancelBtn) cancelBtn.onclick = () => cleanup(false);

            modal.classList.remove('hidden');
            if (okBtn) okBtn.focus();
        });
    };

    // Close modal & context menu on Escape key + Gallery Arrow Navigation
    document.addEventListener('keydown', (e) => {
        const detailModal = document.getElementById('fileDetailModal');
        if (detailModal && !detailModal.classList.contains('hidden')) {
            if (e.key === 'ArrowLeft' && typeof window.navigatePreviewGallery === 'function') {
                e.preventDefault();
                window.navigatePreviewGallery(-1);
                return;
            }
            if (e.key === 'ArrowRight' && typeof window.navigatePreviewGallery === 'function') {
                e.preventDefault();
                window.navigatePreviewGallery(1);
                return;
            }
        }

        if (e.key === 'Escape') {
            closeContextMenu();
            if (typeof window.closeInspectorDrawer === 'function') window.closeInspectorDrawer();
            const userMenu = document.getElementById('userDropdownMenu');
            if (userMenu) userMenu.classList.add('hidden');
            const cancelBtn = document.getElementById('confirmModalCancelBtn');
            const confirmModal = document.getElementById('confirmActionModal');
            if (confirmModal && !confirmModal.classList.contains('hidden') && cancelBtn) {
                cancelBtn.click();
                return;
            }
            document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('hidden'));
            if (document.querySelectorAll('.file-select-checkbox:checked').length > 0) {
                clearBulkSelection();
            }
        }

        // Global Shortcut: Ctrl + K or Cmd + K to open Command Palette
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (typeof window.openCommandPalette === 'function') {
                window.openCommandPalette();
            }
            return;
        }

        // Global Shortcut: / to focus search
        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Global Shortcut: N to create new folder
        if ((e.key === 'n' || e.key === 'N') && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            const btnNewFolderModal = document.getElementById('btnNewFolderModal');
            if (btnNewFolderModal) {
                e.preventDefault();
                document.getElementById('newFolderName').value = '';
                openModal('newFolderModal');
            }
        }
    });

    // Close modal on backdrop click
    document.querySelectorAll('[id$="Modal"]').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // =========================================================================
    // 1b. Dark Mode Theme Toggle
    // =========================================================================
    function syncThemeIcons(isDark) {
        const sunIcon = document.getElementById('themeIconSun');
        const moonIcon = document.getElementById('themeIconMoon');
        if (sunIcon && moonIcon) {
            if (isDark) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }
    }

    window.toggleDarkMode = function () {
        const isDark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('drive_theme', isDark ? 'dark' : 'light');
        } catch (e) {}
        syncThemeIcons(isDark);
    };

    // Initial sync of theme icons
    syncThemeIcons(document.documentElement.classList.contains('dark'));

    // =========================================================================
    // 1c. Instant Live Search & Filter Chips
    // =========================================================================
    let currentFilterCategory = 'all';

    window.setFilterCategory = function (category, buttonEl) {
        currentFilterCategory = category;
        document.querySelectorAll('.filter-chip').forEach(chip => {
            if (chip === buttonEl) {
                chip.className = 'filter-chip active px-3 py-1.5 rounded-xl font-bold bg-brand-600 text-white shadow-xs transition-all';
            } else {
                chip.className = 'filter-chip px-3 py-1.5 rounded-xl font-semibold bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-all';
            }
        });
        applyLiveFilter();
    };

    function matchesFilterCategory(fileCat, filterCat) {
        if (filterCat === 'all') return true;
        if (filterCat === 'media') return ['video', 'audio', 'media'].includes(fileCat);
        if (filterCat === 'document') return ['document', 'pdf', 'code'].includes(fileCat);
        return fileCat === filterCat;
    }

    function applyLiveFilter() {
        const searchInput = document.getElementById('searchInput');
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

        // Filter File Cards in Grid View
        const fileCards = document.querySelectorAll('.file-card');
        fileCards.forEach(card => {
            if (card.classList.contains('folder-drop-zone')) return;
            const fileName = (card.getAttribute('data-filename') || '').toLowerCase();
            const rawName = (card.getAttribute('data-rawname') || '').toLowerCase();
            const fileCat = card.getAttribute('data-category') || 'document';

            const matchesQuery = !query || fileName.includes(query) || rawName.includes(query);
            const matchesCategory = matchesFilterCategory(fileCat, currentFilterCategory);

            if (matchesQuery && matchesCategory) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        // Filter Folder Cards in Grid View
        const folderCards = document.querySelectorAll('#filesGridView .folder-drop-zone');
        folderCards.forEach(folder => {
            const folderName = (folder.getAttribute('data-folder-name') || '').toLowerCase();
            const matchesQuery = !query || folderName.includes(query);
            const matchesCategory = (currentFilterCategory === 'all');

            if (matchesQuery && matchesCategory) {
                folder.classList.remove('hidden');
            } else {
                folder.classList.add('hidden');
            }
        });

        // Filter Table Rows in List View
        const fileRows = document.querySelectorAll('#filesTable tbody .file-row');
        fileRows.forEach(row => {
            const fileName = (row.getAttribute('data-filename') || '').toLowerCase();
            const rawName = (row.getAttribute('data-rawname') || '').toLowerCase();
            const fileCat = row.getAttribute('data-category') || 'document';

            const matchesQuery = !query || fileName.includes(query) || rawName.includes(query);
            const matchesCategory = matchesFilterCategory(fileCat, currentFilterCategory);

            if (matchesQuery && matchesCategory) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    const searchInputEl = document.getElementById('searchInput');
    if (searchInputEl) {
        searchInputEl.addEventListener('input', applyLiveFilter);
    }

    // =========================================================================
    // 2. View Mode Switcher (Grid vs List View)
    // =========================================================================
    window.setViewMode = function (mode) {
        const gridView = document.getElementById('filesGridView');
        const listView = document.getElementById('filesListView');
        const btnGrid = document.getElementById('btnViewGrid');
        const btnList = document.getElementById('btnViewList');

        if (!gridView || !listView) return;

        if (mode === 'list') {
            gridView.classList.add('hidden');
            listView.classList.remove('hidden');

            if (btnList && btnGrid) {
                btnList.className = 'h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all bg-white text-brand-600 shadow-xs';
                btnGrid.className = 'h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all text-slate-500 hover:text-slate-800';
            }
            try { localStorage.setItem('drive_view_mode', 'list'); } catch (e) {}
        } else {
            listView.classList.add('hidden');
            gridView.classList.remove('hidden');

            if (btnList && btnGrid) {
                btnGrid.className = 'h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all bg-white text-brand-600 shadow-xs';
                btnList.className = 'h-8 px-2.5 rounded-md text-xs font-semibold flex items-center transition-all text-slate-500 hover:text-slate-800';
            }
            try { localStorage.setItem('drive_view_mode', 'grid'); } catch (e) {}
        }
    };

    // Restore saved view mode preference
    try {
        const savedMode = localStorage.getItem('drive_view_mode');
        if (savedMode === 'list') {
            setViewMode('list');
        }
    } catch (e) {}

    // =========================================================================
    // 3. Client-Side Sorting Engine (Name, Size, Date)
    // =========================================================================
    window.sortFiles = function (criteria) {
        const gridContainer = document.getElementById('filesGridView');
        const listTbody = document.querySelector('#filesTable tbody');

        const sortItems = (elements, isTable = false) => {
            return Array.from(elements).sort((a, b) => {
                const nameA = a.getAttribute('data-rawname') || '';
                const nameB = b.getAttribute('data-rawname') || '';
                const sizeA = parseInt(a.getAttribute('data-filesize') || '0', 10);
                const sizeB = parseInt(b.getAttribute('data-filesize') || '0', 10);
                const dateA = parseInt(a.getAttribute('data-created') || '0', 10);
                const dateB = parseInt(b.getAttribute('data-created') || '0', 10);

                switch (criteria) {
                    case 'name_asc':
                        return nameA.localeCompare(nameB, undefined, { numeric: true, sensitivity: 'base' });
                    case 'name_desc':
                        return nameB.localeCompare(nameA, undefined, { numeric: true, sensitivity: 'base' });
                    case 'size_desc':
                        return sizeB - sizeA;
                    case 'size_asc':
                        return sizeA - sizeB;
                    case 'created_asc':
                        return dateA - dateB;
                    case 'created_desc':
                    default:
                        return dateB - dateA;
                }
            });
        };

        if (gridContainer) {
            const cards = gridContainer.querySelectorAll('.file-card');
            if (cards.length > 0) {
                const sortedCards = sortItems(cards);
                sortedCards.forEach(card => gridContainer.appendChild(card));
            }
        }

        if (listTbody) {
            const rows = listTbody.querySelectorAll('.file-row');
            if (rows.length > 0) {
                const sortedRows = sortItems(rows, true);
                sortedRows.forEach(row => listTbody.appendChild(row));
            }
        }

        showToast('Urutan berkas diperbarui.');
    };

    // =========================================================================
    // 4. Custom Right-Click Context Menu
    // =========================================================================
    const contextMenu = document.getElementById('fileContextMenu');

    window.handleFileContextMenu = function (e, fileId, rawName, mime, shareToken) {
        e.preventDefault();
        if (!contextMenu) return;

        const x = Math.min(e.clientX, window.innerWidth - 210);
        const y = Math.min(e.clientY, window.innerHeight - 220);

        contextMenu.style.left = `${x}px`;
        contextMenu.style.top = `${y}px`;
        contextMenu.classList.remove('hidden');

        // Wire context menu action buttons
        const btnPreview = document.getElementById('ctxBtnPreview');
        const btnShare = document.getElementById('ctxBtnShare');
        const btnCopyLink = document.getElementById('ctxBtnCopyLink');
        const btnDownload = document.getElementById('ctxBtnDownload');
        const btnDelete = document.getElementById('ctxBtnDelete');
        const btnRename = document.getElementById('ctxBtnRename');

        if (btnPreview) {
            btnPreview.onclick = () => {
                closeContextMenu();
                previewFile(fileId, rawName, mime);
            };
        }

        if (btnShare) {
            btnShare.onclick = () => {
                closeContextMenu();
                openShareModal(fileId, rawName, shareToken);
            };
        }

        if (btnCopyLink) {
            btnCopyLink.onclick = () => {
                closeContextMenu();
                if (shareToken) {
                    copyShareUrl(shareToken);
                } else {
                    openShareModal(fileId, rawName, '');
                }
            };
        }

        if (btnDownload) {
            btnDownload.href = `?action=download_direct&file_id=${fileId}`;
            btnDownload.onclick = () => closeContextMenu();
        }

        if (btnDelete) {
            btnDelete.onclick = () => {
                closeContextMenu();
                deleteFile(fileId, rawName);
            };
        }

        if (btnRename) {
            btnRename.onclick = () => {
                closeContextMenu();
                openRenameModal('file', fileId, rawName);
            };
        }

        const btnDuplicate = document.getElementById('ctxBtnDuplicate');
        if (btnDuplicate) {
            btnDuplicate.onclick = () => {
                closeContextMenu();
                duplicateFile(fileId, rawName);
            };
        }
    };

    function closeContextMenu() {
        if (contextMenu) {
            contextMenu.classList.add('hidden');
        }
    }

    document.addEventListener('click', (e) => {
        if (contextMenu && !contextMenu.contains(e.target)) {
            closeContextMenu();
        }
        if (!e.target.closest('.card-action-menu')) {
            document.querySelectorAll('.card-action-menu').forEach(menu => menu.classList.add('hidden'));
        }
    });

    window.addEventListener('scroll', () => {
        closeContextMenu();
        document.querySelectorAll('.card-action-menu').forEach(menu => menu.classList.add('hidden'));
    }, true);

    // In-Card Action Dropdown (Mockup Three-Dot Menu)
    window.toggleCardMenu = function (e, menuId) {
        e.stopPropagation();
        closeContextMenu();
        const targetMenu = document.getElementById(menuId);
        const allMenus = document.querySelectorAll('.card-action-menu');

        allMenus.forEach(menu => {
            if (menu !== targetMenu) {
                menu.classList.add('hidden');
            }
        });

        if (targetMenu) {
            targetMenu.classList.toggle('hidden');
        }
    };

    // =========================================================================
    // 5. Bulk Selection & Multi-Delete Actions
    // =========================================================================
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkSelectedCount = document.getElementById('bulkSelectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    window.onFileCheckboxChange = function (checkbox) {
        const fileId = checkbox.getAttribute('data-id');
        const isChecked = checkbox.checked;

        // Synchronize corresponding checkboxes in both grid and list view
        document.querySelectorAll(`.file-select-checkbox[data-id="${fileId}"]`).forEach(cb => {
            cb.checked = isChecked;
            const parentCard = cb.closest('.file-card');
            if (parentCard) parentCard.classList.toggle('is-selected', isChecked);
            const parentRow = cb.closest('.file-row');
            if (parentRow) parentRow.classList.toggle('is-selected', isChecked);
        });

        updateBulkActionBar();
    };

    window.toggleSelectAll = function (checked) {
        document.querySelectorAll('.file-select-checkbox').forEach(cb => {
            cb.checked = checked;
            const parentCard = cb.closest('.file-card');
            if (parentCard) parentCard.classList.toggle('is-selected', checked);
            const parentRow = cb.closest('.file-row');
            if (parentRow) parentRow.classList.toggle('is-selected', checked);
        });

        updateBulkActionBar();
    };

    window.clearBulkSelection = function () {
        document.querySelectorAll('.file-select-checkbox').forEach(cb => {
            cb.checked = false;
            const parentCard = cb.closest('.file-card');
            if (parentCard) parentCard.classList.remove('is-selected');
            const parentRow = cb.closest('.file-row');
            if (parentRow) parentRow.classList.remove('is-selected');
        });

        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        updateBulkActionBar();
    };

    function updateBulkActionBar() {
        const checkedBoxes = Array.from(document.querySelectorAll('.file-select-checkbox:checked'));
        const uniqueIds = new Set(checkedBoxes.map(cb => cb.getAttribute('data-id')));
        const count = uniqueIds.size;

        if (bulkSelectedCount) bulkSelectedCount.textContent = count;

        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
                bulkActionBar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
            } else {
                bulkActionBar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
                bulkActionBar.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
            }
        }
    }

    window.bulkDeleteSelected = async function () {
        const checkedBoxes = Array.from(document.querySelectorAll('.file-select-checkbox:checked'));
        const fileIds = Array.from(new Set(checkedBoxes.map(cb => parseInt(cb.getAttribute('data-id'), 10))));

        if (fileIds.length === 0) return;

        const ok = await askConfirm({
            title: 'Pindahkan Berkas ke Sampah',
            message: `Pindahkan ${fileIds.length} berkas yang dipilih ke Keranjang Sampah? Anda masih dapat memulihkannya nanti.`,
            confirmText: 'Pindahkan ke Sampah',
            variant: 'warning'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_ids', JSON.stringify(fileIds));

        try {
            const response = await fetch('?action=bulk_delete_files', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.error || 'Gagal menghapus berkas terpilih.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menghapus berkas.');
        }
    };

    // =========================================================================
    // 6. New Folder Management
    // =========================================================================
    const btnNewFolderModal = document.getElementById('btnNewFolderModal');
    if (btnNewFolderModal) {
        btnNewFolderModal.addEventListener('click', () => {
            const folderInput = document.getElementById('newFolderName');
            if (folderInput) folderInput.value = '';
            openModal('newFolderModal');
        });
    }

    window.handleCreateFolder = async function (e) {
        e.preventDefault();
        const nameInput = document.getElementById('newFolderName');
        const folderName = nameInput ? nameInput.value.trim() : '';
        if (!folderName) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('name', folderName);
        if (currentFolderId) {
            formData.append('parent_id', currentFolderId);
        }

        try {
            const response = await fetch('?action=create_folder', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.error || 'Gagal membuat folder.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat membuat folder.');
        }
    };

    window.deleteFolder = async function (folderId, folderName) {
        const ok = await askConfirm({
            title: 'Pindahkan Folder ke Sampah',
            message: `Pindahkan folder "${folderName}" beserta isinya ke Keranjang Sampah?`,
            confirmText: 'Pindahkan ke Sampah',
            variant: 'warning'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('folder_id', folderId);

        try {
            const response = await fetch('?action=delete_folder', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Folder "${folderName}" dipindahkan ke Sampah.`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal memindahkan folder ke sampah.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menghapus folder.');
        }
    };

    // =========================================================================
    // 7. Single File Deletion (Soft Delete to Trash)
    // =========================================================================
    window.deleteFile = async function (fileId, fileName) {
        const ok = await askConfirm({
            title: 'Pindahkan Berkas ke Sampah',
            message: `Pindahkan berkas "${fileName}" ke Keranjang Sampah?`,
            confirmText: 'Pindahkan ke Sampah',
            variant: 'warning'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);

        try {
            const response = await fetch('?action=delete_file', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Berkas "${fileName}" dipindahkan ke Sampah.`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal memindahkan berkas ke sampah.');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat menghapus berkas.');
        }
    };

    // =========================================================================
    // 7a. Recycle Bin / Trash Actions (Restore & Permanent Delete)
    // =========================================================================
    window.restoreFile = async function (fileId, fileName) {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);

        try {
            const response = await fetch('?action=restore_file', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Berkas "${fileName}" berhasil dipulihkan!`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal memulihkan berkas.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat memulihkan berkas.');
        }
    };

    window.permanentDeleteFile = async function (fileId, fileName) {
        const ok = await askConfirm({
            title: 'Hapus Berkas Secara Permanen',
            message: `Hapus berkas "${fileName}" secara PERMANEN? Tindakan ini tidak dapat dibatalkan dan ruang kuota akan dikembalikan.`,
            confirmText: 'Hapus Permanen',
            variant: 'danger'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);

        try {
            const response = await fetch('?action=permanent_delete_file', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Berkas "${fileName}" berhasil dimusnahkan.`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal menghapus berkas permanen.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menghapus berkas permanen.');
        }
    };

    window.restoreFolder = async function (folderId, folderName) {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('folder_id', folderId);

        try {
            const response = await fetch('?action=restore_folder', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Folder "${folderName}" berhasil dipulihkan!`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal memulihkan folder.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat memulihkan folder.');
        }
    };

    window.permanentDeleteFolder = async function (folderId, folderName) {
        const ok = await askConfirm({
            title: 'Hapus Folder Secara Permanen',
            message: `Hapus folder "${folderName}" beserta seluruh isinya secara PERMANEN? Tindakan ini tidak dapat dibatalkan.`,
            confirmText: 'Hapus Permanen',
            variant: 'danger'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('folder_id', folderId);

        try {
            const response = await fetch('?action=permanent_delete_folder', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Folder "${folderName}" berhasil dimusnahkan.`);
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal menghapus folder permanen.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menghapus folder permanen.');
        }
    };

    window.emptyTrashConfirm = async function () {
        const ok = await askConfirm({
            title: 'Kosongkan Keranjang Sampah',
            message: 'Kosongkan semua berkas dan folder di dalam Keranjang Sampah secara PERMANEN? Seluruh ruang penyimpanan akan dibebaskan.',
            confirmText: 'Ya, Kosongkan Sampah',
            variant: 'danger'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('?action=empty_trash', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast('Keranjang sampah berhasil dikosongkan!');
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal mengosongkan sampah.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat mengosongkan sampah.');
        }
    };

    // =========================================================================
    // 7b. Rename File & Folder
    // =========================================================================
    window.openRenameModal = function (type, id, currentName) {
        closeContextMenu();
        const typeInput = document.getElementById('renameType');
        const idInput = document.getElementById('renameTargetId');
        const nameInput = document.getElementById('renameInput');
        const modalTitle = document.getElementById('renameModalTitle');
        const inputLabel = document.getElementById('renameInputLabel');
        const helpText = document.getElementById('renameInputHelp');

        if (typeInput) typeInput.value = type;
        if (idInput) idInput.value = id;
        if (nameInput) nameInput.value = currentName;

        if (type === 'folder') {
            if (modalTitle) modalTitle.innerHTML = `
                <svg class="w-4 h-4 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Ganti Nama Folder
            `;
            if (inputLabel) inputLabel.textContent = 'Nama Folder Baru';
            if (helpText) helpText.textContent = 'Masukkan nama baru untuk folder ini.';
        } else {
            if (modalTitle) modalTitle.innerHTML = `
                <svg class="w-4 h-4 mr-2 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Ganti Nama Berkas
            `;
            if (inputLabel) inputLabel.textContent = 'Nama Berkas Baru';
            if (helpText) helpText.textContent = 'Ekstensi berkas otomatis dipertahankan jika tidak dicantumkan.';
        }

        openModal('renameModal');

        if (nameInput) {
            nameInput.focus();
            if (type === 'file') {
                const lastDot = currentName.lastIndexOf('.');
                if (lastDot > 0) {
                    nameInput.setSelectionRange(0, lastDot);
                } else {
                    nameInput.select();
                }
            } else {
                nameInput.select();
            }
        }
    };

    window.handleRenameSubmit = async function (e) {
        e.preventDefault();
        const type = document.getElementById('renameType').value;
        const targetId = document.getElementById('renameTargetId').value;
        const nameInput = document.getElementById('renameInput');
        const newName = nameInput ? nameInput.value.trim() : '';
        const submitBtn = document.getElementById('btnSubmitRename');

        if (!newName) {
            alert('Nama tidak boleh kosong.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('name', newName);

        const action = type === 'folder' ? '?action=rename_folder' : '?action=rename_file';
        if (type === 'folder') {
            formData.append('folder_id', targetId);
        } else {
            formData.append('file_id', targetId);
        }

        try {
            const response = await fetch(action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(type === 'folder' ? 'Nama folder berhasil diubah!' : 'Nama berkas berhasil diubah!');
                closeModal('renameModal');
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert(result.error || 'Gagal mengubah nama.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Simpan Perubahan';
                }
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat mengubah nama.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Simpan Perubahan';
            }
        }
    };

    // =========================================================================
    // 7c. Duplicate File & Create / Edit Text File Directly
    // =========================================================================
    window.duplicateFile = async function (fileId, fileName) {
        closeContextMenu();
        document.querySelectorAll('.card-action-menu').forEach(m => m.classList.add('hidden'));

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);

        try {
            showToast(`Membuat salinan "${fileName}"...`);
            const response = await fetch('?action=duplicate_file', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Salinan "${result.file_name || fileName}" berhasil dibuat!`);
                setTimeout(() => window.location.reload(), 350);
            } else {
                alert(result.error || 'Gagal menduplikat berkas.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menduplikat berkas.');
        }
    };

    window.updateTextEditorStats = function () {
        const textarea = document.getElementById('textEditorContent');
        const stats = document.getElementById('textEditorStats');
        if (!textarea || !stats) return;
        const val = textarea.value || '';
        const lines = val === '' ? 0 : val.split('\n').length;
        stats.textContent = `${lines} baris • ${val.length} karakter`;
    };

    window.setTextFileExt = function (ext) {
        const input = document.getElementById('textEditorFilename');
        if (!input) return;
        let name = input.value.trim();
        if (!name) {
            input.value = 'catatan' + ext;
        } else {
            const dot = name.lastIndexOf('.');
            if (dot > 0) {
                name = name.substring(0, dot);
            }
            input.value = name + ext;
        }
        input.focus();
    };

    window.openNewTextFileModal = function () {
        const idInput = document.getElementById('textEditorFileId');
        const titleEl = document.getElementById('textEditorModalTitle');
        const nameRow = document.getElementById('textEditorNameRow');
        const nameInput = document.getElementById('textEditorFilename');
        const contentInput = document.getElementById('textEditorContent');
        const submitBtn = document.getElementById('btnSubmitTextEditor');

        if (idInput) idInput.value = '0';
        if (titleEl) titleEl.textContent = 'Buat Catatan / Berkas Teks Baru';
        if (nameRow) nameRow.classList.remove('hidden');
        if (nameInput) {
            nameInput.value = '';
            nameInput.required = true;
        }
        if (contentInput) contentInput.value = '';
        if (submitBtn) submitBtn.textContent = 'Simpan Berkas Baru';

        window.updateTextEditorStats();
        openModal('textEditorModal');
        setTimeout(() => nameInput?.focus(), 30);
    };

    window.openEditTextFileModal = function (fileId, fileName, initialContent) {
        closeModal('fileDetailModal');
        const idInput = document.getElementById('textEditorFileId');
        const titleEl = document.getElementById('textEditorModalTitle');
        const nameRow = document.getElementById('textEditorNameRow');
        const nameInput = document.getElementById('textEditorFilename');
        const contentInput = document.getElementById('textEditorContent');
        const submitBtn = document.getElementById('btnSubmitTextEditor');

        if (idInput) idInput.value = String(fileId);
        if (titleEl) titleEl.textContent = `Edit Berkas: ${fileName}`;
        if (nameRow) nameRow.classList.add('hidden');
        if (nameInput) {
            nameInput.value = fileName;
            nameInput.required = false;
        }
        if (contentInput) contentInput.value = initialContent || '';
        if (submitBtn) submitBtn.textContent = 'Simpan Perubahan';

        window.updateTextEditorStats();
        openModal('textEditorModal');
        setTimeout(() => contentInput?.focus(), 30);
    };

    window.handleSaveTextFile = async function (e) {
        e.preventDefault();
        const fileId = parseInt(document.getElementById('textEditorFileId')?.value || '0', 10);
        const filename = (document.getElementById('textEditorFilename')?.value || '').trim();
        const content = document.getElementById('textEditorContent')?.value || '';
        const submitBtn = document.getElementById('btnSubmitTextEditor');

        if (fileId <= 0 && !filename) {
            alert('Masukkan nama berkas terlebih dahulu.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('content', content);

        const endpoint = fileId > 0 ? '?action=save_text_file' : '?action=create_text_file';
        if (fileId > 0) {
            formData.append('file_id', fileId);
        } else {
            formData.append('filename', filename);
            if (currentFolderId) {
                formData.append('folder_id', currentFolderId);
            }
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                closeModal('textEditorModal');
                showToast(fileId > 0 ? 'Isi berkas berhasil diperbarui!' : `Berkas "${result.file_name || filename}" berhasil dibuat!`);
                setTimeout(() => window.location.reload(), 350);
            } else {
                alert(result.error || 'Gagal menyimpan berkas teks.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menyimpan berkas teks.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = fileId > 0 ? 'Simpan Perubahan' : 'Simpan Berkas';
            }
        }
    };

    window.downloadQrFromImg = async function (imgId) {
        const img = document.getElementById(imgId);
        if (!img || !img.src) return;
        const hiResUrl = img.src.replace(/size=\d+x\d+/, 'size=400x400');
        try {
            const res = await fetch(hiResUrl);
            const blob = await res.blob();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'qr-code-tautan.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showToast('Gambar QR Code berhasil diunduh!');
        } catch (e) {
            window.open(hiResUrl, '_blank');
        }
    };

    // =========================================================================
    // 8. Drag-and-Drop File Upload & Multi-File Support
    // =========================================================================
    const dropZone = document.getElementById('dropZone');
    const fileUploadInput = document.getElementById('fileUploadInput');
    const globalDropOverlay = document.getElementById('globalDropOverlay');
    let dragCounter = 0;

    if (fileUploadInput) {
        fileUploadInput.addEventListener('change', () => {
            if (fileUploadInput.files && fileUploadInput.files.length > 0) {
                handleFiles(fileUploadInput.files);
            }
            fileUploadInput.value = '';
        });
    }

    if (dropZone) {
        dropZone.style.cursor = 'pointer';
        dropZone.addEventListener('click', () => {
            if (fileUploadInput) fileUploadInput.click();
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover-active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover-active');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover-active');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files);
            }
        });
    }

    // =========================================================================
    // 8.1 Drag and Drop File to Folder (Pindahkan Berkas Tunggal maupun Banyak)
    // =========================================================================
    let isInternalFileDrag = false;
    let draggedFileIds = [];
    let draggedFileEl = null;

    function initFileToFolderDragAndDrop() {
        const draggableFiles = document.querySelectorAll('.draggable-file');
        const folderDropZones = document.querySelectorAll('.folder-drop-zone');

        draggableFiles.forEach(fileEl => {
            fileEl.addEventListener('dragstart', (e) => {
                isInternalFileDrag = true;
                draggedFileEl = fileEl;
                const singleId = fileEl.getAttribute('data-id');

                // Cek apakah ada checkbox yang sedang dicentang
                const checkedBoxes = Array.from(document.querySelectorAll('.file-select-checkbox:checked'));
                const checkedIds = Array.from(new Set(checkedBoxes.map(cb => cb.getAttribute('data-id'))));

                // Jika file yang di-drag termasuk dalam file yang dicentang, pindahkan semua file yang dicentang!
                if (checkedIds.length > 0 && checkedIds.includes(singleId)) {
                    draggedFileIds = checkedIds;
                } else if (checkedIds.length > 0 && !checkedIds.includes(singleId)) {
                    // Jika file yang di-drag belum dicentang, sertakan file ini juga
                    draggedFileIds = [singleId];
                } else {
                    draggedFileIds = [singleId];
                }

                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', JSON.stringify(draggedFileIds));
                }

                // Beri efek visual pada semua file yang ikut ditarik
                draggedFileIds.forEach(id => {
                    document.querySelectorAll(`.draggable-file[data-id="${id}"]`).forEach(el => {
                        el.classList.add('opacity-40', 'scale-[0.98]', 'border-brand-400');
                    });
                });

                if (draggedFileIds.length > 1) {
                    showToast(`Menarik ${draggedFileIds.length} berkas sekaligus...`);
                }
            });

            fileEl.addEventListener('dragend', () => {
                isInternalFileDrag = false;
                draggedFileEl = null;

                // Kembalikan efek visual
                document.querySelectorAll('.draggable-file').forEach(el => {
                    el.classList.remove('opacity-40', 'scale-[0.98]', 'border-brand-400');
                });
                draggedFileIds = [];

                folderDropZones.forEach(f => {
                    f.classList.remove('ring-2', 'ring-brand-500', 'bg-brand-50/70', 'scale-[1.02]');
                });
            });
        });

        folderDropZones.forEach(folderEl => {
            folderEl.addEventListener('dragover', (e) => {
                if (!isInternalFileDrag) return;
                e.preventDefault();
                e.stopPropagation();
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'move';
                }
                folderEl.classList.add('ring-2', 'ring-brand-500', 'bg-brand-50/70', 'scale-[1.02]');
            });

            folderEl.addEventListener('dragleave', (e) => {
                if (!isInternalFileDrag) return;
                e.preventDefault();
                folderEl.classList.remove('ring-2', 'ring-brand-500', 'bg-brand-50/70', 'scale-[1.02]');
            });

            folderEl.addEventListener('drop', async (e) => {
                if (!isInternalFileDrag) return;
                e.preventDefault();
                e.stopPropagation();

                folderEl.classList.remove('ring-2', 'ring-brand-500', 'bg-brand-50/70', 'scale-[1.02]');

                let targetFileIds = [];
                try {
                    const rawData = e.dataTransfer ? e.dataTransfer.getData('text/plain') : '';
                    if (rawData.startsWith('[')) {
                        targetFileIds = JSON.parse(rawData);
                    } else if (rawData) {
                        targetFileIds = [rawData];
                    }
                } catch (err) {
                    targetFileIds = draggedFileIds;
                }

                if (!targetFileIds || targetFileIds.length === 0) {
                    targetFileIds = draggedFileIds;
                }

                const folderId = folderEl.getAttribute('data-folder-id');
                const folderName = folderEl.getAttribute('data-folder-name') || 'Folder';

                if (!targetFileIds || targetFileIds.length === 0) return;

                const countText = targetFileIds.length > 1 ? `${targetFileIds.length} berkas` : 'berkas';
                showToast(`Memindahkan ${countText} ke ${folderName}...`);

                try {
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('file_ids', JSON.stringify(targetFileIds));
                    if (folderId && folderId !== 'root') {
                        formData.append('folder_id', folderId);
                    }

                    const response = await fetch('?action=move_file', {
                        method: 'POST',
                        body: formData
                    });

                    const res = await response.json();
                    if (res.success) {
                        showToast(`${targetFileIds.length} berkas berhasil dipindahkan ke ${folderName}!`);
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert(res.error || 'Gagal memindahkan berkas.');
                    }
                } catch (err) {
                    alert('Terjadi kesalahan saat memindahkan berkas.');
                }
            });
        });
    }

    // Panggil inisialisasi Drag & Drop File ke Folder
    initFileToFolderDragAndDrop();

    // =========================================================================
    // 8.2 Global Window-Wide Drag and Drop for Uploading from Desktop
    // =========================================================================
    window.addEventListener('dragenter', (e) => {
        if (isInternalFileDrag || !e.dataTransfer || !e.dataTransfer.types.includes('Files')) {
            return;
        }
        e.preventDefault();
        dragCounter++;
        if (globalDropOverlay) globalDropOverlay.classList.remove('hidden');
        if (dropZone) dropZone.classList.add('dragover-active');
    });

    window.addEventListener('dragover', (e) => {
        if (isInternalFileDrag || !e.dataTransfer || !e.dataTransfer.types.includes('Files')) {
            return;
        }
        e.preventDefault();
        if (globalDropOverlay && globalDropOverlay.classList.contains('hidden')) {
            globalDropOverlay.classList.remove('hidden');
        }
    });

    window.addEventListener('dragleave', (e) => {
        if (isInternalFileDrag) return;
        dragCounter--;
        if (dragCounter <= 0) {
            dragCounter = 0;
            if (globalDropOverlay) globalDropOverlay.classList.add('hidden');
            if (dropZone) dropZone.classList.remove('dragover-active');
        }
    });

    window.addEventListener('drop', (e) => {
        if (isInternalFileDrag) return;
        e.preventDefault();
        dragCounter = 0;
        if (globalDropOverlay) globalDropOverlay.classList.add('hidden');
        if (dropZone) dropZone.classList.remove('dragover-active');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });

    async function handleFiles(fileList) {
        if (!fileList || fileList.length === 0) return;
        const files = Array.from(fileList);
        let successCount = 0;
        for (let i = 0; i < files.length; i++) {
            const ok = await uploadSingleFile(files[i], i + 1, files.length);
            if (ok) successCount++;
        }
        if (successCount > 0) {
            showToast(`${successCount} berkas berhasil diunggah.`);
            setTimeout(() => {
                window.location.reload();
            }, 600);
        }
    }

    let currentUploadXhr = null;
    let isUploadCancelled = false;

    function getOrCreateUploadDock() {
        let dock = document.getElementById('uploadDock');
        if (!dock) {
            dock = document.createElement('div');
            dock.id = 'uploadDock';
            dock.className = 'fixed bottom-5 right-5 z-50 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden';
            dock.innerHTML = `
                <div class="h-11 px-4 bg-slate-900 text-white flex items-center justify-between text-xs font-semibold">
                    <span id="uploadDockTitle">Mengunggah berkas...</span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="uploadDockCancelBtn" class="px-2 py-0.5 rounded text-[11px] bg-rose-500/20 text-rose-300 hover:bg-rose-500 hover:text-white transition-colors">Batal</button>
                        <button type="button" onclick="this.closest('#uploadDock').remove()" class="text-slate-400 hover:text-white text-base leading-none">&times;</button>
                    </div>
                </div>
                <div class="p-4 space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span id="uploadDockFileName" class="text-slate-800 font-medium truncate max-w-[220px]">Nama Berkas</span>
                        <span id="uploadDockPercent" class="text-brand-600 font-mono font-bold">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div id="uploadDockBar" class="h-2 rounded-full bg-brand-600 transition-all duration-150" style="width: 0%"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(dock);

            const cancelBtn = dock.querySelector('#uploadDockCancelBtn');
            if (cancelBtn) {
                cancelBtn.onclick = () => {
                    isUploadCancelled = true;
                    if (currentUploadXhr) {
                        try { currentUploadXhr.abort(); } catch (e) {}
                    }
                    dock.remove();
                    showToast('Unggahan dibatalkan.');
                };
            }
        }
        return dock;
    }

    function uploadSingleFile(file, currentIndex, totalFiles) {
        return new Promise(async (resolve) => {
            isUploadCancelled = false;
            const dock = getOrCreateUploadDock();
            dock.classList.remove('hidden');

            const titleEl = dock.querySelector('#uploadDockTitle');
            const fileEl = dock.querySelector('#uploadDockFileName');
            const percentEl = dock.querySelector('#uploadDockPercent');
            const barEl = dock.querySelector('#uploadDockBar');

            if (titleEl) titleEl.textContent = `Mengunggah (${currentIndex}/${totalFiles})...`;
            if (fileEl) fileEl.textContent = file.name;
            if (percentEl) percentEl.textContent = '0%';
            if (barEl) barEl.style.width = '0%';

            // Cloudflare Tunnel Free membatasi request body maksimum 100MB (HTTP 413).
            // Potong berkas menjadi bagian kecil 8MB jika ukuran berkas > 8MB.
            const CHUNK_SIZE = 8 * 1024 * 1024; // 8MB per chunk

            if (file.size <= CHUNK_SIZE) {
                // Berkas kecil (<= 8MB): unggah langsung via ?action=upload
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('file', file);
                if (currentFolderId) {
                    formData.append('folder_id', currentFolderId);
                }

                const xhr = new XMLHttpRequest();
                currentUploadXhr = xhr;
                xhr.open('POST', '?action=upload', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        if (barEl) barEl.style.width = percentComplete + '%';
                        if (percentEl) percentEl.textContent = percentComplete + '%';
                    }
                };

                xhr.onload = () => {
                    if (isUploadCancelled) {
                        resolve(false);
                        return;
                    }
                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (!res.success) {
                                alert(res.error || `Gagal mengunggah ${file.name}`);
                                resolve(false);
                                return;
                            }
                            resolve(true);
                        } catch (e) {
                            console.error('Non-JSON response:', xhr.responseText);
                            const snippet = xhr.responseText ? xhr.responseText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 150) : '';
                            alert('Respons server: ' + (snippet || 'Format respons tidak dikenali.'));
                            resolve(false);
                        }
                    } else {
                        alert(`Gagal mengunggah berkas ${file.name} (HTTP ${xhr.status}).`);
                        resolve(false);
                    }
                };

                xhr.onerror = () => {
                    if (!isUploadCancelled) {
                        alert(`Kesalahan koneksi saat mengunggah berkas ${file.name}.`);
                    }
                    resolve(false);
                };

                xhr.onabort = () => {
                    resolve(false);
                };

                xhr.send(formData);
            } else {
                // Berkas besar (> 8MB, misal aplikasi/installer > 100MB): Chunked Upload via ?action=upload_chunk dengan Auto-Retry
                const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
                let uploadedBytes = 0;

                try {
                    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                        if (isUploadCancelled) {
                            resolve(false);
                            return;
                        }

                        const start = chunkIndex * CHUNK_SIZE;
                        const end = Math.min(start + CHUNK_SIZE, file.size);
                        const chunkBlob = file.slice(start, end);

                        const formData = new FormData();
                        formData.append('csrf_token', csrfToken);
                        formData.append('upload_id', uploadId);
                        formData.append('chunk_index', chunkIndex);
                        formData.append('total_chunks', totalChunks);
                        formData.append('filename', file.name);
                        formData.append('total_size', file.size);
                        if (currentFolderId) {
                            formData.append('folder_id', currentFolderId);
                        }
                        formData.append('file', chunkBlob, file.name);

                        // Upload bagian ini dengan Auto-Retry (hingga 3 kali percobaan jika ada gangguan jaringan)
                        let chunkSuccess = false;
                        let attempt = 0;
                        const maxRetries = 3;

                        while (attempt < maxRetries && !chunkSuccess) {
                            if (isUploadCancelled) {
                                resolve(false);
                                return;
                            }

                            try {
                                await new Promise((chunkResolve, chunkReject) => {
                                    const chunkXhr = new XMLHttpRequest();
                                    currentUploadXhr = chunkXhr;
                                    chunkXhr.open('POST', '?action=upload_chunk', true);
                                    chunkXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                    chunkXhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

                                    chunkXhr.upload.onprogress = (e) => {
                                        if (e.lengthComputable) {
                                            const currentLoaded = uploadedBytes + e.loaded;
                                            const percentComplete = Math.min(99, Math.round((currentLoaded / file.size) * 100));
                                            if (barEl) barEl.style.width = percentComplete + '%';
                                            if (percentEl) percentEl.textContent = `${percentComplete}% (${chunkIndex + 1}/${totalChunks})`;
                                        }
                                    };

                                    chunkXhr.onload = () => {
                                        if (isUploadCancelled) {
                                            chunkReject(new Error('Unggahan dibatalkan.'));
                                            return;
                                        }
                                        if (chunkXhr.status === 200) {
                                            try {
                                                const res = JSON.parse(chunkXhr.responseText);
                                                if (!res.success) {
                                                    chunkReject(new Error(res.error || `Gagal mengunggah bagian ke-${chunkIndex + 1}`));
                                                    return;
                                                }
                                                chunkResolve(res);
                                            } catch (e) {
                                                chunkReject(new Error('Format respons server tidak dikenali saat mengunggah bagian berkas.'));
                                            }
                                        } else {
                                            chunkReject(new Error(`Server error HTTP ${chunkXhr.status} pada bagian ke-${chunkIndex + 1}`));
                                        }
                                    };

                                    chunkXhr.onerror = () => {
                                        chunkReject(new Error(`Koneksi terputus saat mengunggah bagian ke-${chunkIndex + 1}.`));
                                    };

                                    chunkXhr.onabort = () => {
                                        chunkReject(new Error('Unggahan dibatalkan.'));
                                    };

                                    chunkXhr.send(formData);
                                });

                                chunkSuccess = true;
                            } catch (err) {
                                if (isUploadCancelled) {
                                    resolve(false);
                                    return;
                                }

                                attempt++;
                                if (attempt >= maxRetries) {
                                    throw err;
                                }

                                if (percentEl) {
                                    percentEl.textContent = `Koneksi tersendat, mencoba ulang bagian ${chunkIndex + 1} (${attempt}/${maxRetries})...`;
                                }
                                await new Promise(r => setTimeout(r, 1200 * attempt));
                            }
                        }

                        uploadedBytes += (end - start);
                    }

                    if (barEl) barEl.style.width = '100%';
                    if (percentEl) percentEl.textContent = '100%';
                    resolve(true);
                } catch (err) {
                    if (!isUploadCancelled) {
                        alert(err.message || `Gagal mengunggah berkas ${file.name}`);
                    }
                    resolve(false);
                }
            }
        });
    }

    // =========================================================================
    // 10. Sharing Engine (Public Links & User-to-User Shares)
    // =========================================================================
    window.switchShareTab = function (tab) {
        const tabBtnPublic = document.getElementById('tabBtnPublicShare');
        const tabBtnUser = document.getElementById('tabBtnUserShare');
        const panelPublic = document.getElementById('sharePanelPublic');
        const panelUser = document.getElementById('sharePanelUser');

        if (!panelPublic || !panelUser) return;

        if (tab === 'user') {
            panelPublic.classList.add('hidden');
            panelUser.classList.remove('hidden');

            if (tabBtnPublic && tabBtnUser) {
                tabBtnUser.className = 'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-brand-600 shadow-xs';
                tabBtnPublic.className = 'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900';
            }
        } else {
            panelUser.classList.add('hidden');
            panelPublic.classList.remove('hidden');

            if (tabBtnPublic && tabBtnUser) {
                tabBtnPublic.className = 'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-brand-600 shadow-xs';
                tabBtnUser.className = 'flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-600 hover:text-slate-900';
            }
        }
    };

    window.openShareModal = function (fileId, fileName, existingToken) {
        const fileIdInput = document.getElementById('shareFileId');
        const modalFileName = document.getElementById('shareModalFileName');
        const passwordInput = document.getElementById('sharePassword');
        const maxDownloadsInput = document.getElementById('shareMaxDownloads');
        const expirySelect = document.getElementById('shareExpiry');
        const resultBox = document.getElementById('shareResultBox');
        const urlInput = document.getElementById('shareGeneratedUrl');
        const submitBtn = document.getElementById('btnSubmitShare');

        if (fileIdInput) fileIdInput.value = fileId;
        if (modalFileName) modalFileName.textContent = fileName;
        if (passwordInput) passwordInput.value = '';
        if (maxDownloadsInput) maxDownloadsInput.value = '';
        if (expirySelect) expirySelect.value = 'never';

        switchShareTab('public');

        const qrImg = document.getElementById('shareModalQrImg');
        const btnRevokeModal = document.getElementById('btnRevokeModalShare');

        if (existingToken) {
            const origin = window.location.origin + window.location.pathname;
            const fullShareUrl = `${origin}?action=share&token=${existingToken}`;
            urlInput.value = fullShareUrl;
            if (qrImg) {
                qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=4&data=${encodeURIComponent(fullShareUrl)}`;
            }
            if (btnRevokeModal) {
                btnRevokeModal.classList.remove('hidden');
                btnRevokeModal.onclick = () => window.revokePublicShareLink(0, fileId, fileName);
            }
            resultBox.classList.remove('hidden');
            if (submitBtn) submitBtn.textContent = 'Perbarui Tautan Publik';
        } else {
            resultBox.classList.add('hidden');
            urlInput.value = '';
            if (qrImg) qrImg.src = '';
            if (submitBtn) submitBtn.textContent = 'Buat Tautan Publik';
        }

        loadFileUserShares(fileId);
        openModal('shareModal');
    };

    // Public Link Form
    window.handleCreateShare = async function (e) {
        e.preventDefault();
        const fileId = document.getElementById('shareFileId').value;
        const password = document.getElementById('sharePassword').value;
        const expiry = document.getElementById('shareExpiry').value;
        const maxDownloads = document.getElementById('shareMaxDownloads').value;
        const allowPreview = document.getElementById('shareAllowPreview')?.checked ? 1 : 0;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);
        formData.append('password', password);
        formData.append('expiry', expiry);
        formData.append('max_downloads', maxDownloads);
        formData.append('allow_preview', allowPreview);

        try {
            const response = await fetch('?action=create_share', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const origin = window.location.origin + window.location.pathname;
                const fullShareUrl = `${origin}?action=share&token=${result.share_token}`;
                document.getElementById('shareGeneratedUrl').value = fullShareUrl;
                const qrImg = document.getElementById('shareModalQrImg');
                if (qrImg) {
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=4&data=${encodeURIComponent(fullShareUrl)}`;
                }
                const btnRevokeModal = document.getElementById('btnRevokeModalShare');
                if (btnRevokeModal) {
                    btnRevokeModal.classList.remove('hidden');
                    const fName = document.getElementById('shareModalFileName')?.textContent || 'berkas';
                    btnRevokeModal.onclick = () => window.revokePublicShareLink(result.share_id || 0, fileId, fName);
                }
                document.getElementById('shareResultBox').classList.remove('hidden');
                copyToClipboard(fullShareUrl, 'Tautan publik berhasil dibuat dan disalin!');
            } else {
                alert(result.error || 'Gagal membuat tautan.');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat memproses tautan.');
        }
    };

    // Direct User Share Handler
    window.handleDirectUserShare = async function (e) {
        e.preventDefault();
        const fileId = document.getElementById('shareFileId').value;
        const selectUser = document.getElementById('directShareUserId');
        const withUserId = selectUser ? selectUser.value : '';
        const permissionRadio = document.querySelector('input[name="directPermission"]:checked');
        const permission = permissionRadio ? permissionRadio.value : 'download';

        if (!withUserId) {
            alert('Silakan pilih pengguna tujuan.');
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);
        formData.append('with_user_id', withUserId);
        formData.append('permission', permission);

        try {
            const response = await fetch('?action=share_with_user', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast('Berkas berhasil dibagikan ke pengguna!');
                selectUser.value = '';
                loadFileUserShares(fileId);
            } else {
                alert(result.error || 'Gagal membagikan ke pengguna.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat membagikan.');
        }
    };

    // Load User Shares for Current File
    window.loadFileUserShares = async function (fileId) {
        const container = document.getElementById('fileUserSharesList');
        if (!container) return;

        container.innerHTML = '<p class="text-xs text-slate-400">Memuat daftar...</p>';

        try {
            const response = await fetch(`?action=get_file_user_shares&file_id=${fileId}`);
            const result = await response.json();

            if (result.success && Array.isArray(result.shares)) {
                if (result.shares.length === 0) {
                    container.innerHTML = '<p class="text-xs text-slate-400 italic">Belum ada pengguna terdaftar yang diberi akses berkas ini.</p>';
                    return;
                }

                let html = '';
                result.shares.forEach(item => {
                    const permBadge = item.permission === 'view'
                        ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Hanya Lihat</span>'
                        : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Boleh Unduh</span>';

                    html += `
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                            <div class="flex items-center space-x-2 min-w-0">
                                <div class="h-6 w-6 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-[10px] uppercase flex-shrink-0">
                                    ${escapeHtml(item.username.charAt(0))}
                                </div>
                                <div class="truncate">
                                    <span class="font-bold text-slate-800">${escapeHtml(item.username)}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">(${escapeHtml(item.email)})</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                ${permBadge}
                                <button type="button" onclick="revokeDirectUserShare(${fileId}, ${item.user_id || item.shared_with_user_id})" 
                                        class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors" title="Cabut Akses">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-xs text-red-500">Gagal memuat daftar penerima.</p>';
            }
        } catch (err) {
            container.innerHTML = '<p class="text-xs text-slate-400 italic">Belum dibagikan ke pengguna mana pun.</p>';
        }
    };

    window.revokeDirectUserShare = async function (fileId, userId) {
        const ok = await askConfirm({
            title: 'Cabut Akses Pengguna',
            message: 'Cabut akses pengguna ini terhadap berkas Anda?',
            confirmText: 'Cabut Akses',
            variant: 'warning'
        });
        if (!ok) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file_id', fileId);
        formData.append('with_user_id', userId);

        try {
            const response = await fetch('?action=revoke_user_share', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast('Akses pengguna berhasil dicabut.');
                loadFileUserShares(fileId);
            } else {
                alert(result.error || 'Gagal mencabut akses.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan.');
        }
    };

    // Clipboard Copy with Dynamic Button Feedback
    window.copyGeneratedShareUrl = function (btnElement) {
        const input = document.getElementById('shareGeneratedUrl');
        if (input && input.value) {
            copyToClipboard(input.value, 'Tautan berhasil disalin ke clipboard!', btnElement);
        }
    };

    window.copyShareUrl = function (token, btnElement) {
        const origin = window.location.origin + window.location.pathname;
        const fullUrl = `${origin}?action=share&token=${token}`;
        copyToClipboard(fullUrl, 'Tautan berbagi berhasil disalin!', btnElement);
    };

    function copyToClipboard(text, successMessage, btnElement) {
        const executeSuccess = () => {
            showToast(successMessage);
            if (btnElement && btnElement instanceof HTMLElement) {
                const originalHtml = btnElement.innerHTML;
                const originalClass = btnElement.className;

                btnElement.innerHTML = `
                    <svg class="w-3.5 h-3.5 mr-1 text-emerald-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Tersalin!
                `;
                btnElement.classList.add('bg-emerald-100', 'text-emerald-800', 'border-emerald-300');

                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.className = originalClass;
                }, 2000);
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(executeSuccess).catch(() => {
                fallbackCopy(text);
                executeSuccess();
            });
        } else {
            fallbackCopy(text);
            executeSuccess();
        }
    }

    function fallbackCopy(text) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    }

    function escapeHtml(string) {
        const div = document.createElement('div');
        div.textContent = string;
        return div.innerHTML;
    }

    // =========================================================================
    // 11. File Detail & Preview Modal (With Next/Prev Gallery Navigation)
    // =========================================================================
    let currentGalleryList = [];
    let currentGalleryIndex = -1;

    function buildVisibleGalleryList() {
        const list = [];
        const seen = new Set();
        document.querySelectorAll('.file-card[data-filejson]:not(.hidden), .file-row[data-filejson]:not(.hidden)').forEach(el => {
            const id = el.getAttribute('data-id');
            if (!id || seen.has(id)) return;
            seen.add(id);
            try {
                const parsed = JSON.parse(el.getAttribute('data-filejson'));
                if (parsed && parsed.id) list.push(parsed);
            } catch (e) {}
        });
        return list;
    }

    window.navigatePreviewGallery = function (step) {
        if (!currentGalleryList || currentGalleryList.length <= 1) return;
        const nextIdx = (currentGalleryIndex + step + currentGalleryList.length) % currentGalleryList.length;
        const nextItem = currentGalleryList[nextIdx];
        if (nextItem) {
            window.openFileDetail(nextItem);
        }
    };

    window.openFileDetail = function (fileData) {
        closeContextMenu();
        const modal = document.getElementById('fileDetailModal');
        if (!modal) {
            window.location.href = `?action=download_direct&file_id=${fileData.id}&preview=1`;
            return;
        }

        // Sync gallery navigation state
        currentGalleryList = buildVisibleGalleryList();
        currentGalleryIndex = currentGalleryList.findIndex(item => Number(item.id) === Number(fileData.id));
        const btnPrev = document.getElementById('detailBtnPrev');
        const btnNext = document.getElementById('detailBtnNext');
        const counterEl = document.getElementById('detailGalleryCounter');

        if (currentGalleryList.length > 1 && currentGalleryIndex !== -1) {
            if (btnPrev) {
                btnPrev.classList.remove('hidden');
                btnPrev.classList.add('flex');
            }
            if (btnNext) {
                btnNext.classList.remove('hidden');
                btnNext.classList.add('flex');
            }
            if (counterEl) {
                counterEl.textContent = `${currentGalleryIndex + 1} / ${currentGalleryList.length}`;
                counterEl.classList.remove('hidden');
            }
        } else {
            if (btnPrev) {
                btnPrev.classList.add('hidden');
                btnPrev.classList.remove('flex');
            }
            if (btnNext) {
                btnNext.classList.add('hidden');
                btnNext.classList.remove('flex');
            }
            if (counterEl) counterEl.classList.add('hidden');
        }

        const nameEl = document.getElementById('detailFileName');
        const badgeEl = document.getElementById('detailFileBadge');
        const sizeEl = document.getElementById('detailFileSize');
        const mimeEl = document.getElementById('detailFileMime');
        const dateEl = document.getElementById('detailFileDate');
        const shareStatusEl = document.getElementById('detailFileShareStatus');
        const previewContainer = document.getElementById('detailPreviewContainer');

        if (nameEl) nameEl.textContent = fileData.name;
        if (badgeEl) {
            badgeEl.textContent = fileData.badge || 'FILE';
            badgeEl.className = `inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold ${fileData.bg || 'bg-slate-100 text-slate-700'}`;
        }
        if (sizeEl) sizeEl.textContent = fileData.formatted_size;
        if (mimeEl) mimeEl.textContent = fileData.mime;
        if (dateEl) dateEl.textContent = fileData.created_at;
        if (shareStatusEl) {
            if (fileData.is_shared) {
                shareStatusEl.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Publik</span>`;
            } else if (fileData.user_shares_count > 0) {
                shareStatusEl.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">${fileData.user_shares_count} Pengguna</span>`;
            } else {
                shareStatusEl.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-500">Pribadi</span>`;
            }
        }

        if (previewContainer) {
            previewContainer.innerHTML = '';
            const previewUrl = `?action=download_direct&file_id=${fileData.id}&preview=1`;

            const btnOpenNewTab = document.getElementById('detailBtnOpenNewTab');
            if (btnOpenNewTab) {
                btnOpenNewTab.href = previewUrl;
            }

            const fileNameLower = (fileData.name || '').toLowerCase();
            const textExtensions = ['.txt', '.md', '.markdown', '.json', '.js', '.ts', '.css', '.html', '.htm', '.xml', '.sql', '.sh', '.py', '.php', '.yml', '.yaml', '.log', '.csv', '.env', '.ini'];
            const isTextCode = textExtensions.some(ext => fileNameLower.endsWith(ext)) || (fileData.mime && (fileData.mime.startsWith('text/') || fileData.mime === 'application/json' || fileData.mime === 'application/javascript'));

            if (fileData.mime && fileData.mime.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = previewUrl;
                img.alt = fileData.name;
                img.className = 'max-h-[50vh] max-w-full rounded-xl object-contain shadow-xs mx-auto';
                previewContainer.appendChild(img);
            } else if (fileData.mime === 'application/pdf' || fileNameLower.endsWith('.pdf')) {
                const iframe = document.createElement('iframe');
                iframe.src = previewUrl;
                iframe.className = 'w-full h-[55vh] rounded-xl border border-slate-200 dark:border-slate-700 bg-white';
                previewContainer.appendChild(iframe);
            } else if (fileData.mime && fileData.mime.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = previewUrl;
                video.controls = true;
                video.className = 'max-h-[50vh] max-w-full rounded-xl shadow-xs mx-auto bg-black';
                previewContainer.appendChild(video);
            } else if (fileData.mime && fileData.mime.startsWith('audio/')) {
                const audioBox = document.createElement('div');
                audioBox.className = 'p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center w-full max-w-md mx-auto';
                audioBox.innerHTML = `
                    <div class="h-14 w-14 rounded-xl bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path></svg>
                    </div>
                    <audio controls class="w-full"><source src="${previewUrl}"></audio>
                `;
                previewContainer.appendChild(audioBox);
            } else if (isTextCode) {
                const codeBox = document.createElement('div');
                codeBox.className = 'w-full flex flex-col space-y-2';
                codeBox.innerHTML = `
                    <div class="flex items-center justify-between text-xs font-mono text-slate-500 pb-1.5 border-b border-slate-200 dark:border-slate-700">
                        <span>Pratinjau Kode / Teks</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] text-slate-400">Maks. 200KB</span>
                            <button type="button" id="btnInlineEditText" class="px-2.5 py-0.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 font-sans font-bold text-[11px] transition-colors">
                                ✏️ Edit Teks
                            </button>
                        </div>
                    </div>
                    <pre class="w-full max-h-[50vh] overflow-auto p-4 rounded-xl bg-slate-900 text-slate-100 font-mono text-xs leading-relaxed select-text whitespace-pre border border-slate-800"><code id="codePreviewContent">Memuat isi berkas...</code></pre>
                `;
                previewContainer.appendChild(codeBox);

                fetch(previewUrl)
                    .then(r => r.text())
                    .then(text => {
                        const codeEl = document.getElementById('codePreviewContent');
                        if (codeEl) {
                            codeEl.textContent = text.slice(0, 200000);
                        }
                        const editBtn = document.getElementById('btnInlineEditText');
                        if (editBtn) {
                            editBtn.onclick = () => window.openEditTextFileModal(fileData.id, fileData.name, text);
                        }
                    })
                    .catch(() => {
                        const codeEl = document.getElementById('codePreviewContent');
                        if (codeEl) codeEl.textContent = 'Gagal memuat pratinjau teks.';
                    });
            } else {
                const emptyBox = document.createElement('div');
                emptyBox.className = 'p-8 text-center text-slate-400';
                emptyBox.innerHTML = `
                    <div class="h-16 w-16 mx-auto mb-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
                        <span class="text-base font-bold">${fileData.badge || 'FILE'}</span>
                    </div>
                    <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(fileData.name)}</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">Pratinjau langsung tidak tersedia untuk format berkas ini. Anda dapat mengunduhnya secara langsung.</p>
                `;
                previewContainer.appendChild(emptyBox);
            }
        }

        const btnDownload = document.getElementById('detailBtnDownload');
        const btnShare = document.getElementById('detailBtnShare');
        const btnDelete = document.getElementById('detailBtnDelete');
        const btnRename = document.getElementById('detailBtnRename');

        if (btnDownload) {
            btnDownload.href = `?action=download_direct&file_id=${fileData.id}`;
        }
        if (btnShare) {
            btnShare.onclick = () => {
                closeModal('fileDetailModal');
                openShareModal(fileData.id, fileData.name, fileData.share_token || '');
            };
        }
        if (btnDelete) {
            btnDelete.onclick = () => {
                closeModal('fileDetailModal');
                deleteFile(fileData.id, fileData.name);
            };
        }
        if (btnRename) {
            btnRename.onclick = () => {
                closeModal('fileDetailModal');
                openRenameModal('file', fileData.id, fileData.name);
            };
        }

        openModal('fileDetailModal');
    };

    window.previewFile = function (fileId, fileName, mimeType) {
        window.openFileDetail({
            id: fileId,
            name: fileName,
            mime: mimeType,
            formatted_size: '-',
            size: 0,
            badge: 'FILE',
            bg: 'bg-slate-100 text-slate-700',
            created_at: '-',
            is_shared: false,
            share_token: '',
            user_shares_count: 0
        });
    };

    // =========================================================================
    // 12. Toast Feedback & Clipboard Utilities (Mockup Aligned)
    // =========================================================================
    window.hideToast = function () {
        const toast = document.getElementById('appToast');
        if (!toast) return;
        toast.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
        toast.classList.add('translate-y-12', 'opacity-0', 'pointer-events-none');
    };

    window.showToast = function (message) {
        const toast = document.getElementById('appToast');
        if (!toast) return;
        const msgEl = toast.querySelector('#appToastMsg') || toast;
        msgEl.textContent = message;
        toast.classList.remove('translate-y-12', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
        clearTimeout(window.__toastTimeout);
        window.__toastTimeout = setTimeout(() => {
            window.hideToast();
        }, 3000);
    };

    // Global Shortcut: U to trigger file upload
    document.addEventListener('keydown', function (e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            return;
        }
        if (e.key === 'u' || e.key === 'U') {
            e.preventDefault();
            const uploadInput = document.getElementById('fileUploadInput');
            if (uploadInput) uploadInput.click();
        }
    });

    // =========================================================================
    // 13. User Avatar Dropdown, QR Modal, Revoke Public Share, Inspector & Command Palette
    // =========================================================================
    window.toggleUserDropdown = function (e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('userDropdownMenu');
        if (menu) menu.classList.toggle('hidden');
    };

    document.addEventListener('click', (e) => {
        const wrapper = document.getElementById('userDropdownWrapper');
        const menu = document.getElementById('userDropdownMenu');
        if (menu && wrapper && !wrapper.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    window.openQrModal = function (token, fileName) {
        const origin = window.location.origin + window.location.pathname;
        const fullUrl = `${origin}?action=share&token=${token}`;
        const qrImg = document.getElementById('qrModalImg');
        const qrName = document.getElementById('qrModalFileName');
        const qrCopyBtn = document.getElementById('qrModalCopyBtn');

        if (qrImg) {
            qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=6&data=${encodeURIComponent(fullUrl)}`;
        }
        if (qrName) qrName.textContent = fileName || 'Tautan Berkas';
        if (qrCopyBtn) {
            qrCopyBtn.onclick = function () {
                copyToClipboard(fullUrl, 'Tautan berhasil disalin!', this);
            };
        }
        openModal('qrCodeModal');
    };

    window.revokePublicShareLink = async function (shareId, fileId, fileName) {
        const ok = await askConfirm({
            title: 'Cabut Tautan Publik',
            message: `Nonaktifkan dan cabut tautan publik untuk "${fileName}"? Siapa pun yang memiliki tautan lama tidak akan bisa mengunduh lagi.`,
            confirmText: 'Cabut Tautan',
            variant: 'danger'
        });
        if (!ok) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        if (shareId > 0) formData.append('share_id', shareId);
        if (fileId > 0) formData.append('file_id', fileId);

        try {
            const response = await fetch('?action=revoke_public_share', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast('Tautan publik berhasil dicabut.');
                closeModal('shareModal');
                setTimeout(() => window.location.reload(), 350);
            } else {
                alert(result.error || 'Gagal mencabut tautan publik.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat mencabut tautan.');
        }
    };

    // Slide-Over File Inspector Drawer
    window.openInspectorDrawer = function (fileData) {
        closeContextMenu();
        document.querySelectorAll('.card-action-menu').forEach(m => m.classList.add('hidden'));

        const drawer = document.getElementById('fileInspectorDrawer');
        const backdrop = document.getElementById('fileInspectorBackdrop');
        if (!drawer) return;

        const badgeEl = document.getElementById('inspectorBadge');
        const titleEl = document.getElementById('inspectorTitle');
        const previewBox = document.getElementById('inspectorPreviewBox');
        const metaName = document.getElementById('inspectorMetaName');
        const metaSize = document.getElementById('inspectorMetaSize');
        const metaMime = document.getElementById('inspectorMetaMime');
        const metaDate = document.getElementById('inspectorMetaDate');
        const metaShare = document.getElementById('inspectorMetaShare');
        const shareCard = document.getElementById('inspectorShareCard');
        const shareInput = document.getElementById('inspectorShareUrlInput');

        if (badgeEl) {
            badgeEl.textContent = fileData.badge || 'FILE';
            badgeEl.className = `inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold ${fileData.bg || 'bg-brand-50 text-brand-700'}`;
        }
        if (titleEl) titleEl.textContent = fileData.name;
        if (metaName) metaName.textContent = fileData.name;
        if (metaSize) metaSize.textContent = fileData.formatted_size;
        if (metaMime) metaMime.textContent = fileData.mime;
        if (metaDate) metaDate.textContent = fileData.created_at;

        if (previewBox) {
            if (fileData.mime && fileData.mime.startsWith('image/')) {
                previewBox.innerHTML = `<img src="?action=download_direct&file_id=${fileData.id}&thumb=1" alt="${escapeHtml(fileData.name)}" class="max-h-full max-w-full rounded-xl object-contain shadow-2xs">`;
            } else {
                previewBox.innerHTML = `
                    <div class="text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold ${fileData.bg || 'bg-slate-200 text-slate-700'} mb-2">${fileData.badge || 'FILE'}</span>
                        <p class="text-[11px] text-slate-400 truncate max-w-[200px]">${escapeHtml(fileData.name)}</p>
                    </div>
                `;
            }
        }

        if (metaShare) {
            if (fileData.is_shared && fileData.share_token) {
                metaShare.innerHTML = `<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Publik Aktif</span>`;
            } else if (fileData.user_shares_count > 0) {
                metaShare.innerHTML = `<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">${fileData.user_shares_count} Pengguna</span>`;
            } else {
                metaShare.innerHTML = `<span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500">Pribadi</span>`;
            }
        }

        if (shareCard) {
            if (fileData.is_shared && fileData.share_token) {
                const origin = window.location.origin + window.location.pathname;
                const fullShareUrl = `${origin}?action=share&token=${fileData.share_token}`;
                if (shareInput) shareInput.value = fullShareUrl;
                const btnCopy = document.getElementById('inspectorBtnCopyShare');
                if (btnCopy) {
                    btnCopy.onclick = function () {
                        copyToClipboard(fullShareUrl, 'Tautan disalin!', this);
                    };
                }
                const btnQr = document.getElementById('inspectorBtnQr');
                if (btnQr) {
                    btnQr.onclick = () => window.openQrModal(fileData.share_token, fileData.name);
                }
                shareCard.classList.remove('hidden');
            } else {
                shareCard.classList.add('hidden');
            }
        }

        const btnPreview = document.getElementById('inspectorBtnPreview');
        const btnDownload = document.getElementById('inspectorBtnDownload');
        const btnShare = document.getElementById('inspectorBtnShare');
        const btnRename = document.getElementById('inspectorBtnRename');
        const btnDelete = document.getElementById('inspectorBtnDelete');

        if (btnPreview) {
            btnPreview.onclick = () => {
                window.closeInspectorDrawer();
                window.openFileDetail(fileData);
            };
        }
        if (btnDownload) {
            btnDownload.href = `?action=download_direct&file_id=${fileData.id}`;
        }
        if (btnShare) {
            btnShare.onclick = () => {
                window.closeInspectorDrawer();
                window.openShareModal(fileData.id, fileData.name, fileData.share_token || '');
            };
        }
        if (btnRename) {
            btnRename.onclick = () => {
                window.closeInspectorDrawer();
                window.openRenameModal('file', fileData.id, fileData.name);
            };
        }
        if (btnDelete) {
            btnDelete.onclick = () => {
                window.closeInspectorDrawer();
                window.deleteFile(fileData.id, fileData.name);
            };
        }

        if (backdrop) backdrop.classList.remove('hidden');
        drawer.classList.remove('translate-x-full');
    };

    window.closeInspectorDrawer = function () {
        const drawer = document.getElementById('fileInspectorDrawer');
        const backdrop = document.getElementById('fileInspectorBackdrop');
        if (drawer) drawer.classList.add('translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
    };

    // Command Palette (Ctrl + K)
    window.openCommandPalette = function () {
        const modal = document.getElementById('commandPaletteModal');
        const input = document.getElementById('commandPaletteInput');
        if (!modal || !input) return;

        modal.classList.remove('hidden');
        input.value = '';
        renderCommandPaletteItems('');
        setTimeout(() => input.focus(), 20);
    };

    function renderCommandPaletteItems(query) {
        const container = document.getElementById('commandPaletteResults');
        if (!container) return;

        const q = (query || '').toLowerCase().trim();
        const items = [];

        // 1. Collect files on current view
        const seenIds = new Set();
        document.querySelectorAll('.file-card[data-id], .file-row[data-id]').forEach(el => {
            const id = el.getAttribute('data-id');
            if (!id || seenIds.has(id)) return;
            seenIds.add(id);
            const rawName = el.getAttribute('data-rawname') || '';
            const mime = el.getAttribute('data-mimetype') || '';
            if (!q || rawName.toLowerCase().includes(q)) {
                items.push({
                    type: 'file',
                    label: rawName,
                    sub: 'Berkas • Klik untuk Pratinjau',
                    action: () => {
                        closeModal('commandPaletteModal');
                        previewFile(parseInt(id, 10), rawName, mime);
                    }
                });
            }
        });

        // 2. Quick Commands
        const commands = [
            { label: 'Unggah Berkas Baru', sub: 'Pintasan: U', action: () => { closeModal('commandPaletteModal'); document.getElementById('fileUploadInput')?.click(); } },
            { label: 'Buat Catatan / Berkas Teks Baru', sub: 'Tulis berkas .txt, .md, atau .json langsung', action: () => { closeModal('commandPaletteModal'); openNewTextFileModal(); } },
            { label: 'Buat Folder Baru', sub: 'Pintasan: N', action: () => { closeModal('commandPaletteModal'); openModal('newFolderModal'); } },
            { label: 'Analisis Penyimpanan & Berkas Terbesar', sub: 'Cek pemakaian kuota & 25 berkas terbesar', action: () => { window.location.href = '?action=drive&tab=storage_analyzer'; } },
            { label: 'Buka Drive Saya', sub: 'Navigasi Utama', action: () => { window.location.href = '?action=drive&tab=my_drive'; } },
            { label: 'Buka Tautan Publik Saya', sub: 'Kelola Link Aktif & QR Code', action: () => { window.location.href = '?action=drive&tab=my_shares'; } },
            { label: 'Buka Dibagikan ke Saya', sub: 'Berkas dari Pengguna Lain', action: () => { window.location.href = '?action=drive&tab=shared_with_me'; } },
            { label: 'Buka Keranjang Sampah', sub: 'Pulihkan atau Kosongkan Sampah', action: () => { window.location.href = '?action=drive&tab=trash'; } },
            { label: 'Ganti Tema Gelap / Terang', sub: 'Mode Tampilan', action: () => { closeModal('commandPaletteModal'); toggleDarkMode(); } },
            { label: 'Buka Profil & Keamanan Sandi', sub: 'Pengaturan Akun', action: () => { window.location.href = '?action=profile'; } }
        ];

        commands.forEach(cmd => {
            if (!q || cmd.label.toLowerCase().includes(q) || cmd.sub.toLowerCase().includes(q)) {
                items.push({ type: 'cmd', ...cmd });
            }
        });

        if (items.length === 0) {
            container.innerHTML = `<div class="p-6 text-center text-slate-400">Tidak ditemukan berkas atau perintah untuk "${escapeHtml(query)}"</div>`;
            return;
        }

        container.innerHTML = '';
        items.slice(0, 12).forEach((item, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-brand-50/70 flex items-center justify-between transition-colors group';
            const iconHtml = item.type === 'file'
                ? `<span class="h-7 w-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold mr-3">📄</span>`
                : `<span class="h-7 w-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold mr-3">⚡</span>`;
            btn.innerHTML = `
                <div class="flex items-center min-w-0">
                    ${iconHtml}
                    <div class="truncate">
                        <span class="font-bold text-slate-800 group-hover:text-brand-600 block truncate">${escapeHtml(item.label)}</span>
                        <span class="text-[10px] text-slate-400">${escapeHtml(item.sub)}</span>
                    </div>
                </div>
                <span class="text-[10px] font-mono text-slate-400 group-hover:text-brand-600 ml-2">↵</span>
            `;
            btn.onclick = item.action;
            if (idx === 0) btn.dataset.firstItem = '1';
            container.appendChild(btn);
        });
    }

    const cmdInput = document.getElementById('commandPaletteInput');
    if (cmdInput) {
        cmdInput.addEventListener('input', () => renderCommandPaletteItems(cmdInput.value));
        cmdInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstBtn = document.querySelector('#commandPaletteResults button');
                if (firstBtn) firstBtn.click();
            }
        });
    }
});
