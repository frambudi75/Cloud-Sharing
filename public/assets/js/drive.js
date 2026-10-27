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

    // Close modal & context menu on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeContextMenu();
            document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('hidden'));
            if (document.querySelectorAll('.file-select-checkbox:checked').length > 0) {
                clearBulkSelection();
            }
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

        if (!confirm(`Hapus ${fileIds.length} berkas yang dipilih secara permanen? Tindakan ini tidak dapat dibatalkan.`)) {
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
        if (!confirm(`Hapus folder "${folderName}" beserta seluruh isinya? Tindakan ini tidak dapat dibatalkan.`)) {
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
                window.location.reload();
            } else {
                alert(result.error || 'Gagal menghapus folder.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan saat menghapus folder.');
        }
    };

    // =========================================================================
    // 7. Single File Deletion
    // =========================================================================
    window.deleteFile = async function (fileId, fileName) {
        if (!confirm(`Hapus berkas "${fileName}" secara permanen?`)) {
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
                window.location.reload();
            } else {
                alert(result.error || 'Gagal menghapus berkas.');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat menghapus berkas.');
        }
    };

    // =========================================================================
    // 8. Drag-and-Drop File Upload with Pulse Animation
    // =========================================================================
    const dropZone = document.getElementById('dropZone');
    const fileUploadInput = document.getElementById('fileUploadInput');
    const uploadProgressContainer = document.getElementById('uploadProgressContainer');
    const uploadProgressBar = document.getElementById('uploadProgressBar');
    const uploadPercentage = document.getElementById('uploadPercentage');
    const uploadFileName = document.getElementById('uploadFileName');

    if (dropZone && fileUploadInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover-active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover-active');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        });

        fileUploadInput.addEventListener('change', () => {
            handleFiles(fileUploadInput.files);
        });
    }

    function handleFiles(files) {
        if (!files || files.length === 0) return;
        uploadFile(files[0]);
    }

    function uploadFile(file) {
        if (!uploadProgressContainer) return;

        uploadProgressContainer.classList.remove('hidden');
        uploadFileName.textContent = file.name;
        uploadProgressBar.style.width = '0%';
        uploadPercentage.textContent = '0%';

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('file', file);
        if (currentFolderId) {
            formData.append('folder_id', currentFolderId);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '?action=upload', true);

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                uploadProgressBar.style.width = percentComplete + '%';
                uploadPercentage.textContent = percentComplete + '%';
            }
        };

        xhr.onload = () => {
            uploadProgressContainer.classList.add('hidden');
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        window.location.reload();
                    } else {
                        alert(res.error || 'Gagal mengunggah berkas.');
                    }
                } catch (e) {
                    alert('Respons server tidak valid saat upload.');
                }
            } else {
                alert(`Gagal mengunggah berkas (HTTP ${xhr.status}).`);
            }
        };

        xhr.onerror = () => {
            uploadProgressContainer.classList.add('hidden');
            alert('Kesalahan koneksi saat mengunggah berkas.');
        };

        xhr.send(formData);
    }

    // =========================================================================
    // 9. Live Search Filter across Grid Cards and Table Rows
    // =========================================================================
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();

            // Filter List View rows
            const rows = document.querySelectorAll('.file-row');
            rows.forEach(row => {
                const name = (row.getAttribute('data-filename') || '').toLowerCase();
                row.style.display = name.includes(query) ? '' : 'none';
            });

            // Filter Grid View cards
            const cards = document.querySelectorAll('.file-card');
            cards.forEach(card => {
                const name = (card.getAttribute('data-filename') || '').toLowerCase();
                card.style.display = name.includes(query) ? '' : 'none';
            });
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

        if (existingToken) {
            const origin = window.location.origin + window.location.pathname;
            urlInput.value = `${origin}?action=share&token=${existingToken}`;
            resultBox.classList.remove('hidden');
            if (submitBtn) submitBtn.textContent = 'Perbarui Tautan Publik';
        } else {
            resultBox.classList.add('hidden');
            urlInput.value = '';
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
                                <button type="button" onclick="revokeDirectUserShare(${fileId}, ${item.shared_with_user_id})" 
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
        if (!confirm('Cabut akses pengguna terhadap berkas ini?')) return;

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
    // 11. In-Browser File Preview Modal
    // =========================================================================
    window.previewFile = function (fileId, fileName, mimeType) {
        const modal = document.getElementById('previewModal');
        const title = document.getElementById('previewModalTitle');
        const body = document.getElementById('previewModalBody');

        if (!modal || !title || !body) return;

        title.textContent = `Pratinjau: ${fileName}`;
        body.innerHTML = '';

        const previewUrl = `?action=download_direct&file_id=${fileId}&preview=1`;

        if (mimeType.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = previewUrl;
            img.alt = fileName;
            img.className = 'max-w-full max-h-[70vh] rounded-lg shadow-sm object-contain';
            body.appendChild(img);
        } else if (mimeType === 'application/pdf') {
            const iframe = document.createElement('iframe');
            iframe.src = previewUrl;
            iframe.className = 'w-full h-[70vh] rounded-lg border-0';
            body.appendChild(iframe);
        } else if (mimeType.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = previewUrl;
            video.controls = true;
            video.className = 'max-w-full max-h-[70vh] rounded-lg';
            body.appendChild(video);
        } else if (mimeType.startsWith('audio/')) {
            const audio = document.createElement('audio');
            audio.src = previewUrl;
            audio.controls = true;
            audio.className = 'w-full max-w-md';
            body.appendChild(audio);
        } else {
            const iframe = document.createElement('iframe');
            iframe.src = previewUrl;
            iframe.className = 'w-full h-[70vh] bg-white rounded-lg p-4 font-mono text-xs';
            body.appendChild(iframe);
        }

        openModal('previewModal');
    };

    // =========================================================================
    // 12. Toast Feedback & Clipboard Utilities (Mockup Aligned)
    // =========================================================================
    window.showToast = function (message) {
        let toast = document.getElementById('appToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'appToast';
            toast.className = 'fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-xl flex items-center space-x-2 transition-all duration-300 pointer-events-auto';
            toast.innerHTML = `
                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span id="appToastMsg"></span>
                <button type="button" onclick="this.parentElement.style.opacity='0'" class="ml-2 text-slate-400 hover:text-white">&times;</button>
            `;
            document.body.appendChild(toast);
        }
        const msgEl = toast.querySelector('#appToastMsg') || toast;
        msgEl.textContent = message;
        toast.style.display = 'flex';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        clearTimeout(window.__toastTimeout);
        window.__toastTimeout = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(() => { if (toast.style.opacity === '0') toast.style.display = 'none'; }, 300);
        }, 3000);
    };

    window.copyToClipboard = async function (text, message = 'Tautan berhasil disalin!') {
        if (!text) {
            window.showToast('Tautan tidak tersedia.');
            return;
        }
        try {
            await navigator.clipboard.writeText(text);
            window.showToast(message);
        } catch (err) {
            // Fallback for non-HTTPS/legacy
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                window.showToast(message);
            } catch (e) {
                alert('Gagal menyalin tautan: ' + text);
            }
            document.body.removeChild(textArea);
        }
    };

    window.copyShareUrl = function (token, btnElement) {
        if (!token) {
            window.showToast('Belum ada tautan publik untuk berkas ini.');
            return;
        }
        const origin = window.location.origin + window.location.pathname;
        const fullShareUrl = `${origin}?action=share&token=${token}`;
        window.copyToClipboard(fullShareUrl, 'Tautan publik berhasil disalin!');
    };
});
