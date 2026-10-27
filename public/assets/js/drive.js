/**
 * File Sharing Cloud - Drive Client JavaScript
 * Standards: Modern Vanilla ES6+, High Accessibility, Keyboard Navigable, Zero Dead Controls
 */

document.addEventListener('DOMContentLoaded', () => {
    const driveApp = document.getElementById('driveApp');
    if (!driveApp) return;

    const csrfToken = driveApp.getAttribute('data-csrf');
    const currentFolderId = driveApp.getAttribute('data-folder-id');

    // =========================================================================
    // 1. Modal Helper Functions
    // =========================================================================
    window.openModal = function (modalId) {
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

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('hidden'));
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
    // 2. New Folder Management
    // =========================================================================
    const btnNewFolderModal = document.getElementById('btnNewFolderModal');
    if (btnNewFolderModal) {
        btnNewFolderModal.addEventListener('click', () => {
            document.getElementById('newFolderName').value = '';
            openModal('newFolderModal');
        });
    }

    window.handleCreateFolder = async function (e) {
        e.preventDefault();
        const nameInput = document.getElementById('newFolderName');
        const folderName = nameInput.value.trim();
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
            alert('Terjadi kesalahan saat menghapus folder.');
        }
    };

    // =========================================================================
    // 3. File Upload Engine (Multi-file & Drag and Drop with Real Progress)
    // =========================================================================
    const fileUploadInput = document.getElementById('fileUploadInput');
    const uploadProgressContainer = document.getElementById('uploadProgressContainer');
    const uploadProgressBar = document.getElementById('uploadProgressBar');
    const uploadPercentText = document.getElementById('uploadPercentText');
    const uploadStatusText = document.getElementById('uploadStatusText');
    const dropzoneOverlay = document.getElementById('dropzoneOverlay');

    if (fileUploadInput) {
        fileUploadInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                uploadFiles(Array.from(e.target.files));
            }
        });
    }

    // Drag and Drop Global Listeners
    let dragCounter = 0;
    window.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dragCounter++;
        if (dropzoneOverlay) dropzoneOverlay.classList.remove('hidden');
    });

    window.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dragCounter--;
        if (dragCounter <= 0 && dropzoneOverlay) {
            dropzoneOverlay.classList.add('hidden');
        }
    });

    window.addEventListener('dragover', (e) => {
        e.preventDefault();
    });

    window.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        if (dropzoneOverlay) dropzoneOverlay.classList.add('hidden');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            uploadFiles(Array.from(e.dataTransfer.files));
        }
    });

    async function uploadFiles(files) {
        if (!files.length) return;

        uploadProgressContainer.classList.remove('hidden');
        let completed = 0;
        const totalFiles = files.length;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            uploadStatusText.textContent = `Mengunggah (${i + 1}/${totalFiles}): ${file.name}`;

            await new Promise((resolve) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('file', file);
                if (currentFolderId) {
                    formData.append('folder_id', currentFolderId);
                }

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const filePercent = Math.round((e.loaded / e.total) * 100);
                        uploadProgressBar.style.width = `${filePercent}%`;
                        uploadPercentText.textContent = `${filePercent}%`;
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (!res.success) {
                                alert(`Gagal mengunggah ${file.name}: ${res.error}`);
                            }
                        } catch (e) {
                            console.error('Server response:', xhr.responseText);
                            alert(`Gagal mengunggah ${file.name}. Silakan cek console browser.`);
                        }
                    } else {
                        alert(`Gagal mengunggah ${file.name} (Kode status ${xhr.status})`);
                    }
                    resolve();
                });

                xhr.addEventListener('error', () => {
                    alert(`Kesalahan koneksi saat mengunggah ${file.name}`);
                    resolve();
                });

                xhr.open('POST', '?action=upload');
                xhr.send(formData);
            });
            completed++;
        }

        uploadStatusText.textContent = 'Semua berkas selesai diunggah. Memuat ulang...';
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // =========================================================================
    // 4. File Management (Delete, Search Filter)
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

    // Live search filter on table
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.file-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-filename') || '';
                if (name.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // =========================================================================
    // 5. Sharing Engine Modal & Clipboard Helper
    // =========================================================================
    window.openShareModal = function (fileId, fileName, existingToken) {
        document.getElementById('shareFileId').value = fileId;
        document.getElementById('shareModalFileName').textContent = fileName;
        document.getElementById('sharePassword').value = '';
        document.getElementById('shareMaxDownloads').value = '';
        document.getElementById('shareExpiry').value = 'never';

        const resultBox = document.getElementById('shareResultBox');
        const urlInput = document.getElementById('shareGeneratedUrl');
        const submitBtn = document.getElementById('btnSubmitShare');

        if (existingToken) {
            const origin = window.location.origin + window.location.pathname;
            urlInput.value = `${origin}?action=share&token=${existingToken}`;
            resultBox.classList.remove('hidden');
            submitBtn.textContent = 'Perbarui Tautan';
        } else {
            resultBox.classList.add('hidden');
            urlInput.value = '';
            submitBtn.textContent = 'Buat Tautan';
        }

        openModal('shareModal');
    };

    window.handleCreateShare = async function (e) {
        e.preventDefault();
        const fileId = document.getElementById('shareFileId').value;
        const password = document.getElementById('sharePassword').value;
        const expiry = document.getElementById('shareExpiry').value;
        const maxDownloads = document.getElementById('shareMaxDownloads').value;
        const allowPreview = document.getElementById('shareAllowPreview').checked ? 1 : 0;

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
                copyToClipboard(fullShareUrl, 'Tautan berbagi berhasil dibuat dan disalin ke clipboard!');
            } else {
                alert(result.error || 'Gagal membuat tautan berbagi.');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat membuat tautan.');
        }
    };

    window.copyGeneratedShareUrl = function () {
        const input = document.getElementById('shareGeneratedUrl');
        if (input && input.value) {
            copyToClipboard(input.value, 'Tautan berhasil disalin ke clipboard!');
        }
    };

    window.copyShareUrl = function (token) {
        const origin = window.location.origin + window.location.pathname;
        const fullUrl = `${origin}?action=share&token=${token}`;
        copyToClipboard(fullUrl, 'Tautan berbagi berhasil disalin!');
    };

    function copyToClipboard(text, successMessage) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMessage);
            }).catch(() => {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast('Tautan berhasil disalin!');
    }

    // =========================================================================
    // 6. In-Browser File Preview Modal
    // =========================================================================
    window.previewFile = function (fileId, fileName, mimeType) {
        const modal = document.getElementById('previewModal');
        const title = document.getElementById('previewModalTitle');
        const body = document.getElementById('previewModalBody');

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
            // Text or code preview
            const iframe = document.createElement('iframe');
            iframe.src = previewUrl;
            iframe.className = 'w-full h-[70vh] bg-white rounded-lg p-4 font-mono text-xs';
            body.appendChild(iframe);
        }

        openModal('previewModal');
    };

    // =========================================================================
    // 7. Minimal Toast Feedback Component
    // =========================================================================
    function showToast(message) {
        let toast = document.getElementById('appToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'appToast';
            toast.className = 'fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-lg flex items-center space-x-2 transition-opacity duration-300 pointer-events-none';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.style.opacity = '1';
        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    }
});
