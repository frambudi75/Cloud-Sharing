# Dokumentasi Proyek File Sharing

Selamat datang di repositori dokumentasi sistem **File Sharing (PHP Native + Tailwind CSS + MariaDB + Docker)**.

## Daftar Dokumen Spesifikasi:
1. **[Product Requirement Document (PRD)](file:///c:/xampp/htdocs/file-sharing/docs/prd.md):**  
   Menjelaskan visi produk, user roles, spesifikasi fitur lengkap (Drive pribadi, sharing link ber-password/expired, preview in-browser), dan tahapan milestone.
2. **[Architecture Blueprint](file:///c:/xampp/htdocs/file-sharing/docs/architecture.md):**  
   Menjelaskan arsitektur container Docker (Nginx, PHP-FPM, MariaDB), mekanisme *Zero-Copy File Serving* dengan `X-Accel-Redirect`, struktur folder proyek, dan pertahanan keamanan storage.
3. **[Database Schema (DDL)](file:///c:/xampp/htdocs/file-sharing/docs/schema.md):**  
   Diagram relasi entitas (ERD) dan skema DDL SQL lengkap untuk MariaDB / MySQL (`users`, `folders`, `files`, `share_links`, `download_logs`).
4. **[Rules & Coding Standards](file:///c:/xampp/htdocs/file-sharing/docs/rules.md):**  
   Standar penulisan kode PHP modern, sanitasi keamanan, penamaan variabel, dan tata kelola Tailwind CSS.
5. **[Changelog](file:///c:/xampp/htdocs/file-sharing/docs/changelog.md):**  
   Catatan riwayat perubahan dan progres fitur.
