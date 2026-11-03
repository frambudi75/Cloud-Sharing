# Changelog

Semua perubahan pada spesifikasi dan implementasi proyek file-sharing akan dicatat di dokumen ini.

## [v1.5.3] - 2026-11-03
### Ditambahkan & Ditingkatkan
- **Sistem Pagination Interaktif pada Seluruh Log Aktivitas (Audit Trail)**:
  - **Log Aktivitas Pengguna (`Drive -> Log Aktivitas`)**:
    - Penambahan navigasi halaman bernomor cerdas (pills 1, 2, 3 ... N dengan ellipsis), tombol *Prev* dan *Next* aktif/nonaktif.
    - Penampil ringkasan data (*range indicator*): `Menampilkan X - Y dari Z aktivitas`.
    - Pilihan jumlah baris per halaman dinamis (`25`, `50`, `100` entri per halaman via `log_limit`).
    - Mempertahankan filter kategori tindakan (`action_type`) dan kata kunci pencarian (`search`) di setiap navigasi halaman.
  - **Audit Trail Global Seluruh Pengguna (`Pengaturan Server -> Section 6`)**:
    - Penambahan dropdown filter tindakan instan (*Semua, Unggah, Unduh Langsung, Unduh Publik, Berbagi, Hapus, Pulihkan, Login*).
    - Penambahan pagination dinamis AJAX tanpa reload halaman lengkap dengan tombol halaman, tombol Prev/Next, dan pilihan baris (`10`, `20`, `50`).
    - Penyesuaian endpoint `get_global_logs` dengan dukungan parameter `page`, `limit`, dan `action_type`.
  - **Audit Pagination Menyeluruh Sistem**:
    - Evaluasi seluruh modul tabel: Tab Drive Saya, Keranjang Sampah, dan Tautan Publik tetap menggunakan live client-side filtering untuk menjaga kelancaran seleksi massal (*bulk action*) dan operasi *drag-and-drop*.

## [v1.5.2] - 2026-11-03
### Diperbaiki
- **Perbaikan Kosongkan Sampah (*Empty Trash*)**:
  - Memperbaiki kueri `deleteFile()` pada `StorageService` yang tidak memuat kolom `original_name`, menyebabkan *uncaught exception* saat mencatat log aktivitas permanen.
  - Memperbaiki penanganan `emptyTrash()` di `DriveController` dengan mengisolasi penghapusan folder di bawah `SET FOREIGN_KEY_CHECKS = 0` guna mencegah kegagalan *foreign key self-reference* pada subfolder di dalam sampah.
  - Memperbaiki Service Worker (`sw.js`) agar selalu mengembalikan objek `Response` valid dan tidak melemparkan `TypeError: Failed to convert value to 'Response'` saat offline fallback.

## [v1.5.1] - 2026-11-03
### Ditambahkan & Ditingkatkan
- **Direct-Offset Chunk Streaming (Zero-Merge Overhead)**:
  - Mengubah metode penggabungan potongan berkas besar: tiap potongan 8 MB langsung ditulis ke byte offset target pada berkas sementara menggunakan `fseek()`.
  - Mengeliminasi proses *reassembly / merge loop* 115 detik menjadi 0 detik, menuntaskan masalah HTTP 504 Gateway Timeout pada reverse proxy VPS.
  - Bypass kalkulasi hash SHA-256 sinkron untuk file > 50 MB guna mencegah blocking CPU/IO.
- **Proteksi Anti-Refresh Tidak Sengaja (*beforeunload Guard*)**:
  - Penambahan pendeteksi navigasi browser `beforeunload` saat proses pengunggahan aktif.
- **Auto-Detection Pintar Layar Setup Database**:
  - Deteksi otomatis lingkungan Docker vs XAMPP pada `src/Views/system/db_setup.php`.
  - Peringatan cerdas untuk penanganan password volume MariaDB yang terlanjur terinisialisasi.
- **Anti-Brute Force Password Share Publik**:
  - Penerapan rate limiting berbasis IP (maksimal 5 kali percobaan salah per 15 menit) pada pembukaan berkas yang diproteksi password.

## [v1.5.0] - 2026-11-03
### Ditambahkan & Ditingkatkan
- **Upgrade Runtime ke PHP 8.5**:
  - Peningkatan basis Docker image ke `php:8.5-fpm-alpine` dengan Zend OPcache built-in dan JIT Compiler (`tracing`, 64MB).
  - Pembersihan fungsi deprecated `curl_close()` pada `WebhookService` untuk kompatibilitas penuh PHP 8.5+.
  - Konfigurasi `display_errors = Off` dan `log_errors = On` di produksi.
  - Tampilan versi PHP dinamis di badge header dashboard.
- **Full Persistent Resumable Upload (TUS-Style Deterministic Chunks)**:
  - Penambahan endpoint `?action=check_upload_chunks` untuk memeriksa potongan berkas yang sudah ada di server sebelum pengunggahan dimulai.
  - Implementasi *deterministic file fingerprinting* di browser (`name`, `size`, `lastModified`, `folderId`) sehingga `upload_id` tetap konsisten meski tab di-refresh atau koneksi VPN/WiFi terputus.
  - Sistem otomatis melanjutkan (*resume*) pengunggahan dari chunk terakhir yang tersimpan di disk server tanpa perlu mengulang dari 0%.

## [Unreleased] - 2026-10-31
### Ditambahkan & Ditingkatkan
- **Alat Pemeliharaan & Optimasi Server (System Maintenance Tools)**:
  - Tombol **Bersihkan Chunk Unggahan** (`?action=clean_chunks`): Membersihkan seluruh sisa potongan file sementara (`storage/chunks`) dan membebaskan ruang disk fisik.
  - Tombol **Bersihkan Cache Redis & Sesi** (`?action=purge_cache`): Menghapus seluruh namespace cache `fileshare:*` dan mensinkronkan ulang data in-memory.
  - Tombol **Optimasi & Defrag Basis Data** (`?action=optimize_db`): Menjalankan `OPTIMIZE TABLE` pada 8 tabel inti MariaDB secara atomik.
  - Tombol **Uji Kecepatan Disk Storage** (`?action=benchmark_disk`): Melakukan uji tulis & baca acak 10 MB ke storage dan menghitung kecepatan nyata (MB/s).
- **Pengaturan Global Aplikasi (System Global Settings)**:
  - Panel konfigurasi baru untuk kuota default pengguna baru (GB) dan masa berlaku default tautan berbagi publik.
  - Sakelar **Mode Pemeliharaan (Maintenance Mode)**: Mengunci akses pengguna biasa saat server sedang dalam perbaikan rutin dengan pesan pengumuman kustom dan layar pemeliharaan modern (`src/Views/system/maintenance.php`).
- **Log Viewer & Riwayat Webhook (Webhook Delivery Logs)**:
  - Tabel riwayat pengiriman notifikasi webhook terakhir lengkap dengan status HTTP response, latensi milidetik, masking URL aman, dan indikator status.
- **Audit Trail Global Administrator**:
  - Tabel kronologi aktivitas seluruh pengguna sistem lintas akun dengan fitur pencarian instan nama berkas/IP/user dan filter jenis tindakan.
- **Pemantauan Beban CPU, RAM, & Uptime Server Real-Time**:
  - Kartu pemantauan beban CPU (*Load Average 1m, 5m, 15m*), penggunaan RAM fisik server, dan uptime host di halaman [Pengaturan Server](file:///c:/xampp/htdocs/file-sharing/src/Views/admin/server.php).

## [2026-10-29]
### Ditambahkan & Ditingkatkan
- **Log Aktivitas & Riwayat Unduhan Lengkap (Activity & Audit Trail) (`?action=drive&tab=activity_logs`)**:
  - Tabel dan skema baru `activity_logs` dengan migrasi *self-healing* otomatis di `Database::ensureSchema()` dan `Database::repairSchema()`.
  - Service `ActivityLogService` untuk pencatatan otomatis seluruh peristiwa: unggah berkas (langsung & resumable chunk), unduh langsung, unduh tautan publik tamu (lengkap dengan IP dan User-Agent), buat catatan/kode teks kilat, edit teks, buat/hapus folder, duplikat berkas, ganti nama berkas/folder, pemindahan berkas (drag-and-drop), berbagi ke teman, pencabutan tautan, pembuangan ke sampah, dan pemulihan berkas.
  - Tampilan dashboard audit trail terpadu di Drive (`?action=drive&tab=activity_logs`) dengan 4 kartu statistik KPI (*Total Aktivitas*, *Unduhan Hari Ini*, *Unggahan Hari Ini*, *Berkas Terpopuler*), filter chip interaktif per jenis aksi, pencarian berkas/IP, badge warna visual, deteksi perangkat/browser otomatis, format waktu lampau humanis, dan paginasi.
  - Pintasan cepat di sidebar navigasi Drive, dropdown profil pengguna, dan Command Palette (`Ctrl + K`).

## [2026-10-28]
### Ditambahkan & Ditingkatkan
- **Halaman Tentang Aplikasi & Pengembang (`?action=about`) + Kartu Info Developer di Pengaturan Server**:
  - Halaman informasi aplikasi lengkap (`src/Views/system/about.php`) berisi spesifikasi arsitektur, daftar 8 modul fitur utama, pintasan keyboard, serta profil pengembang **Habib Frambudi** (`@frambudi75` — `https://github.com/frambudi75` & `https://github.com/frambudi75/Cloud-Sharing`).
  - Kartu informasi aplikasi & pengembang juga ditambahkan langsung di halaman **Pengaturan Server** (`?action=server`), sidebar Drive, menu dropdown profil, Command Palette (`Ctrl + K`), serta footer halaman Login dan Unduh Tamu.
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
