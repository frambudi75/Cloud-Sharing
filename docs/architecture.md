# System Architecture - File Sharing System

## 1. Arsitektur Komponen & Container Docker

Sistem didesain modular berbasis container terisolasi untuk kemudahan deployment dan skalabilitas di lingkungan Linux.

```
       [ Client Browser / Guest / User ]
                       │
                       ▼ Port 80/443
               ┌───────────────┐
               │  Nginx Server │ ── (Static Assets: Tailwind CSS, JS)
               └───────┬───────┘
                       │ FastCGI (Port 9000)
                       ▼
             ┌───────────────────┐
             │   PHP-FPM 8.4/8.5 │ ◄── Direct Volume: /storage/uploads
             └─────┬───────┬─────┘     (Zero-Copy X-Accel-Redirect Nginx)
                   │       │
    Port 6379 ┌────┘       └────┐ Port 3306
              ▼                 ▼
     ┌─────────────────┐ ┌───────────────────┐
     │   Redis 7 Cache │ │ MariaDB / MySQL   │
     │ (Tokens/Session)│ │ (Indexed Storage) │
     └─────────────────┘ └───────────────────┘
```

---

## 2. Lapisan Caching Redis (High Performance)

- **Redis Cache Engine:** Digunakan untuk *in-memory caching* token share link, verifikasi password tamu, dan rate limiting brute-force.
- **Microsecond Response:** Data metadata berkas publik dibaca langsung dari memori Redis tanpa perlu membebani disk/MySQL query setiap kali link dibuka ribuan kali secara bersamaan.
- **Graceful Fallback:** [CacheService.php](file:///c:/xampp/htdocs/file-sharing/src/Services/CacheService.php) dirancang otomatis mendeteksi ketersediaan Redis: jika Redis tidak aktif (seperti saat pengujian lokal XAMPP tanpa Redis), aplikasi tetap berjalan normal tanpa error.

---

## 3. Fitur Kunci: Zero-Copy File Serving (`X-Accel-Redirect`)

Ketika user atau tamu meminta file (baik preview maupun download):
1. **Client** mengirim request ke endpoint PHP: `?action=download_share&token=xyz`
2. **PHP-FPM (8.4+)** melakukan autentikasi:
   - Validasi session (jika berkas privat) ATAU validasi token & password & expiry (lewat Redis/DB).
   - Menghitung batasan kuota / `download_count`.
3. Setelah valid, alih-alih membaca file via `readfile()` yang memakan RAM PHP, PHP cukup mengirim response header:
   ```http
   Content-Type: application/pdf
   Content-Disposition: attachment; filename="document.pdf"
   X-Accel-Redirect: /internal-storage/uploads/2026/10/a41fd3.bin
   ```
4. **Nginx** menangkap header internal ini, lalu Nginx yang membaca dan mengalirkan file langsung ke browser dengan kecepatan disk asli tanpa membebani PHP worker.
5. **Fallback XAMPP:** Jika berjalan di Apache XAMPP, sistem otomatis menggunakan HTTP Range chunked streaming (RFC 7233) dengan memory buffer hemat (64 KB chunk).

---

## 4. Struktur Direktori Proyek

```
file-sharing/
├── docker/
│   ├── nginx/
│   │   └── default.conf         # Konfigurasi Nginx & X-Accel-Redirect
│   ├── php/
│   │   ├── Dockerfile           # PHP 8.4 / 8.5 Alpine + pdo_mysql, redis, gd, zip
│   │   └── php.ini
│   └── mariadb/
│       └── init.sql             # Auto-bootstrap DDL & Seeding
├── docker-compose.yml           # Orkestrator (web, app, redis, db)
├── docs/                        # Dokumentasi spesifikasi & schema
│   ├── architecture.md
│   ├── changelog.md
│   ├── prd.md
│   ├── readme.md
│   ├── rules.md
│   └── schema.md
├── public/                      # Web Document Root
│   ├── assets/
│   │   ├── css/
│   │   └── js/                  # drive.js
│   └── index.php                # Front controller Docker
├── src/                         # Core Application Logic
│   ├── init.php                 # Autoloader & env loader
│   ├── Config/                  # Env, Database
│   ├── Controllers/             # AuthController, DriveController, GuestController, UserController
│   ├── Services/                # AuthService, StorageService, ShareService, DownloadService, CacheService
│   └── Views/                   # Templates PHP + Tailwind CSS
└── storage/                     # Private Storage
    └── uploads/                 # Berkas fisik user (UUID hashed)
```
