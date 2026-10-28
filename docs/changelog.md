# Changelog

Semua perubahan pada spesifikasi dan implementasi proyek file-sharing akan dicatat di dokumen ini.

## [Unreleased] - 2026-10-28
### Ditambahkan & Ditingkatkan
- **Menu Pusat Kendali Tautan Publik (`?action=drive&tab=my_shares`)**:
  - Menampilkan seluruh tautan publik aktif milik pengguna beserta status proteksi kata sandi, kuota unduhan (`X / Y`), masa berlaku, tombol QR Code, Salin Link, dan pencabutan akses (`?action=revoke_public_share`).
- **Slide-Over File Inspector Drawer**:
  - Panel inspektur di sisi kanan layar untuk melihat thumbnail, metadata lengkap berkas, status berbagi, QR Code, dan aksi cepat tanpa meninggalkan halaman utama.
- **Command Palette Cepat (`Ctrl + K`)**:
  - Modal pencarian cepat lintas berkas dan pintasan aksi (*Unggah Berkas*, *Buat Folder*, *Tautan Publik*, *Ganti Mode Gelap/Terang*, *Profil*).
- **QR Code Instan pada Modal Berbagi & Halaman Unduh Tamu**:
  - Pembuatan QR Code otomatis untuk memudahkan transfer dan pengunduhan berkas dari PC ke ponsel.
- **Dropdown Menu Avatar & Mobile Bottom Navigation Bar**:
  - Penyederhanaan header atas dengan menu akun terpadu serta bilah navigasi bawah khusus perangkat mobile (`< lg`).
- **Perbaikan Ganti Kata Sandi Profil**:
  - Menjamin respons JSON murni tanpa redirect HTML `302`, trimming input otomatis, dan penyeragaman CSRF pada `ProfileController::updatePassword()`.

## [2026-10-27]
### Ditambahkan
- **Fitur User Management Lengkap & Kuota Storage per User** di [UserController.php](file:///c:/xampp/htdocs/file-sharing/src/Controllers/UserController.php) dan [users.php](file:///c:/xampp/htdocs/file-sharing/src/Views/admin/users.php):
  - Tambah pengguna baru dengan role (`admin` atau `user`) dan penetapan kuota storage (dalam GB).
  - Edit pengguna (email, role, status aktif/suspended, dan kuota penyimpanan).
  - Reset kata sandi pengguna langsung oleh Administrator.
  - Hapus pengguna dengan penghapusan otomatis berkas fisik di disk storage.
  - Bar visual penggunaan kuota masing-masing user (`X MB / Y GB`).
- **File Manager Drive Core**:
  - Drag-and-drop multi-file uploader dengan progress bar persentase real-time.
  - Pembuatan folder dan navigasi breadcrumb bertingkat.
  - In-browser file previewer (Gambar, Dokumen PDF, Video, Audio, Teks/Code).
  - Sharing engine dengan opsi kata sandi, tanggal kedaluwarsa, dan batas unduhan.
- **Portal Tamu (Guest Download)** dengan validasi kata sandi dan streaming berkas efisien.
- **Product Requirement Document (PRD)** di [prd.md](file:///c:/xampp/htdocs/file-sharing/docs/prd.md) dengan spesifikasi Model Hybrid.
- **Arsitektur Sistem** di [architecture.md](file:///c:/xampp/htdocs/file-sharing/docs/architecture.md) dengan Nginx, PHP-FPM, MariaDB di Docker serta akselerasi download via `X-Accel-Redirect`.
- **Skema Database Relasional** di [schema.md](file:///c:/xampp/htdocs/file-sharing/docs/schema.md) untuk MariaDB/MySQL.
