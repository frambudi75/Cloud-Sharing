# Cloud Sharing

Sistem File Sharing berbasis web modern, ringan, dan cepat, dibangun menggunakan **PHP Native (PHP 8.4+)**, **Tailwind CSS**, **MariaDB/MySQL**, dan **Redis Cache**.

Didesain dual-compatible: dapat langsung dijalankan di lingkungan lokal **XAMPP Windows** maupun dideploy melalui **Docker di Linux**.

---

## Fitur Utama

- **Model Hybrid (Private Drive & Guest Sharing):**
  - Drive pribadi untuk pengguna terdaftar dengan hierarki folder dan drag-and-drop multi-file uploader.
  - Portal unduhan tamu berbasis token unik dengan opsi proteksi kata sandi, batas waktu kedaluwarsa, dan batas jumlah unduhan.
- **In-Browser File Preview:** Pratinjau langsung di browser untuk berkas Gambar, Dokumen PDF, Video (MP4/WebM), Audio (MP3/WAV), dan Teks/Kode.
- **Performa Tinggi & Zero-Copy File Serving:**
  - Dukungan `X-Accel-Redirect` (Nginx) untuk streaming file besar tanpa membebani RAM PHP.
  - Dukungan HTTP Range Partial Content (RFC 7233) untuk XAMPP Apache (video/audio seeking dan download resume).
  - Lapisan caching **Redis 7** untuk response instan (<2ms) pada tautan publik.
  - Skema database relasional dengan 8 indeks optimasi tinggi.
- **User Management & Storage Quota:**
  - Administrator dapat menambah, mengedit, menonaktifkan, mereset password, dan menghapus akun pengguna.
  - Pengaturan kuota penyimpanan (*storage quota*) spesifik per user dengan indikator visual progress bar.
- **Keamanan:**
  - Penyimpanan fisik terabstraksi dengan hash UUID (mencegah *path traversal* dan *file collision*).
  - Validasi MIME type berbasis magic bytes (`finfo`).
  - Proteksi form dengan CSRF token dan sanitasi XSS.
  - Pencegahan eksekusi skrip PHP di folder storage.

---

## Panduan Menjalankan di XAMPP Windows

1. Tempatkan proyek di folder `c:\xampp\htdocs\file-sharing\`.
2. Nyalakan modul **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database atau langsung buka menu **Import**, lalu pilih file:
   ```
   database.sql
   ```
4. Salin file `.env.example` menjadi `.env` (secara default sudah disesuaikan untuk XAMPP):
   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=filesharing_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Buka di browser:
   ```
   http://localhost/file-sharing/
   ```
   * Akun Admin Default: **admin**
   * Kata Sandi: **admin123**

---

## Panduan Menjalankan di Docker (Linux / Windows)

1. Pastikan Docker dan Docker Compose telah terpasang.
2. Salin environment file:
   ```bash
   cp .env.example .env
   ```
3. Jalankan container:
   ```bash
   docker compose up -d --build
   ```
4. Akses melalui browser di `http://localhost:8080`. Database MariaDB dan Redis akan otomatis diinisialisasi melalui `docker/mariadb/init.sql`.

---

## Struktur Folder

```
file-sharing/
├── docker/                  # Konfigurasi container Nginx, PHP 8.4-FPM, MariaDB
├── docs/                    # Dokumentasi lengkap: PRD, Architecture, Schema, Rules
├── public/                  # Document root web server
│   └── assets/              # Script JavaScript & CSS
├── src/                     # Core backend logic (Controllers, Services, Views)
│   ├── Config/              # Database PDO & Env loader
│   ├── Controllers/         # Auth, Drive, Guest, User Management
│   ├── Services/            # Storage, Share, Download, Cache, Auth
│   └── Views/               # Antarmuka Tailwind CSS
├── storage/                 # Private file uploads (di luar document root)
├── database.sql             # Skema DDL dan seed data
└── docker-compose.yml       # Stack Nginx, PHP 8.4, Redis, MariaDB
```
