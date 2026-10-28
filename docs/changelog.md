# Changelog

Semua perubahan pada spesifikasi dan implementasi proyek file-sharing akan dicatat di dokumen ini.

## [Unreleased] - 2026-10-28
### Ditambahkan & Ditingkatkan
- **Buat & Edit Berkas Teks / Catatan / Kode Langsung di Drive (`?action=create_text_file` & `?action=save_text_file`)**:
  - Tombol **Catatan / Teks Baru** di Drive untuk membuat berkas `.txt`, `.md`, `.json`, `.csv` langsung di folder saat ini, serta tombol **✏️ Edit Teks** di dalam modal pratinjau untuk mengubah dan menyimpan berkas teks secara langsung tanpa unduh/unggah ulang.
- **Fitur Duplikat Berkas Cepat (`?action=duplicate_file`)**:
  - Opsi **Duplikat Berkas** pada menu kartu dan klik kanan untuk membuat salinan cadangan berkas (`Nama (Salinan).ext`) dalam 1 klik dengan perhitungan kuota otomatis.
- **Embedded Live Preview & Unduh PNG QR di Halaman Tamu (`?action=share`)**:
  - Menampilkan pratinjau langsung (Gambar, Video player, Audio player, dan cuplikan Teks/Kode) tepat di dalam kartu halaman unduh tamu serta tombol **Unduh Gambar QR (PNG)** baik di halaman tamu maupun di modal berbagi Drive.
- **Dark Mode Penuh & 4 Kartu Ringkasan KPI di Halaman Admin (`?action=users` & `?action=server`)**:
  - Dukungan mode gelap menyeluruh pada tabel, kartu, dan modal manajemen pengguna & server, dilengkapi 4 kartu KPI (*Total Akun*, *Status Pengguna*, *Total Alokasi Kuota*, *Total Pemakaian*) serta modal konfirmasi kustom saat menghapus pengguna.
- **Analisis Penyimpanan & 25 Berkas Terbesar (`?action=drive&tab=storage_analyzer`)**:
  - Dashboard rincian penggunaan kuota per kategori berkas, peringatan ruang sampah yang bisa dibebaskan, serta tabel 25 berkas terbesar lintas folder untuk pembersihan cepat.
- **Visual Kartu Berkas & Folder Lebih Hidup**:
  - Kartu folder menampilkan jumlah berkas & subfolder di dalamnya (`X berkas • Y folder`) dengan badge angka.
  - Kartu berkas menampilkan ilustrasi ikon berwarna sesuai tipe berkas (Video, Audio, Arsip, PDF, Code, Dokumen), efek *skeleton shimmer* saat memuat thumbnail gambar, serta pil indikator **`🔗 Publik`** / **`🔒 Publik`** / **`👥 X`** langsung di kartu.
- **Modal Konfirmasi Modern Custom (`#confirmActionModal`)**:
  - Menggantikan kotak dialog `window.confirm()` bawaan browser dengan modal konfirmasi kustom yang serasi dengan mode terang dan gelap.
- **Navigasi Galeri Pratinjau (`←` / `→`)**:
  - Tombol Sebelumnya & Berikutnya serta dukungan tombol panah keyboard kiri/kanan pada modal pratinjau berkas (`#fileDetailModal`).
- **Penyempurnaan Scrollbar Dark Mode**:
  - Penyesuaian warna *scrollbar track* dan *thumb* otomatis saat mode gelap aktif di `src/Views/layouts/header.php`.
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
