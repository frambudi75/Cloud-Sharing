# Product Requirement Document (PRD) - Self-Hosted File Sharing System

## 1. Ringkasan Eksekutif & Tujuan
Sistem File Sharing berbasis web yang ringan, cepat, dan aman, berjalan di lingkungan **Linux (Docker)** dengan **PHP Native**, antarmuka modern menggunakan **Tailwind CSS**, dan basis data **MariaDB / MySQL**.

Sistem ini mengadopsi **Model Hybrid (Model C)**:
- **Pengguna Terdaftar (Admin/User):** Memiliki ruang penyimpanan (*drive*) pribadi untuk mengunggah, mengorganisasi folder, dan menghasilkan tautan berbagi (*shareable links*).
- **Tamu / Penerima (Guest):** Dapat mengakses dan mengunduh berkas melalui tautan publik, tautan ber-password, atau tautan berbatas waktu (*expired/burn-after-download*) tanpa perlu mendaftar akun.

---

## 2. Target Pengguna & Hak Akses (User Roles)

| Role | Deskripsi Hak Akses |
| :--- | :--- |
| **Admin** | Manajemen pengguna (tambah/hapus akun, atur kuota storage), pantau statistik sistem, kelola seluruh berkas/folder miliknya, dan konfigurasi sistem (storage limit, cleanup scheduler). |
| **User (Member)** | Memiliki ruang drive pribadi, upload/download berkas, buat folder, kelola tautan sharing, atur password & tanggal kedaluwarsa link. |
| **Guest (Tamu / Penerima Link)** | Hanya dapat melihat halaman download/preview berkas yang dibagikan kepadanya, memasukkan password jika diproteksi, dan mengunduh berkas sesuai batas yang ditentukan. |

---

## 3. Kebutuhan Fungsional (Functional Requirements)

### 3.1. Autentikasi & Akun
- Login & Logout dengan proteksi session aman (`HttpOnly`, `SameSite=Strict`).
- Hash password menggunakan standar industri (`password_hash` dengan algoritma Bcrypt/Argon2id).
- Manajemen profil pengguna & reset password (oleh Admin).
- Kuota penyimpanan (*storage quota limit*) per pengguna.

### 3.2. Manajemen Berkas & Folder (Drive Pribadi)
- **Struktur Hirarki Folder:** Buat folder, sub-folder, ganti nama (*rename*), dan hapus (dengan konfirmasi).
- **Upload Berkas:**
  - Drag-and-drop file uploader dengan *progress bar* real-time.
  - Dukungan *chunked upload* untuk mendukung file berukuran besar (>500MB) tanpa terkendala timeout server.
  - Multi-file upload sekaligus.
- **Pencarian & Filter:** Cari berkas/folder berdasarkan nama atau tipe ekstensi.
- **Pemberian Tag / Metadata:** Tanggal unggah, ukuran file asli, dan ekstensi file.

### 3.3. Mesin Berbagi Berkas (Sharing Engine)
- **Tautan Berbagi Unik:** Setiap link share menggunakan token acak aman (misal 32 karakter kriptografis/UUID) agar tidak dapat ditebak (*anti-enumeration*).
- **Opsi Konfigurasi Tautan:**
  - **Akses:** Publik (langsung bisa diunduh) atau Terproteksi Password (hash tersimpan di database).
  - **Masa Berlaku (Expiration):** Dapat diatur (1 jam, 24 jam, 7 hari, 30 hari, atau tanpa batas).
  - **Batas Unduhan (Download Limit):** Batas maksimum berapa kali berkas boleh diunduh (misal: *Burn after 1 download*).
  - **Izin Preview:** Izinkan atau larang preview sebelum download.
- **Revoke / Cabut Link:** Pemilik berkas dapat menonaktifkan tautan sharing sewaktu-waktu.

### 3.4. Portal Unduhan Tamu (Guest Download Portal)
- Halaman antarmuka khusus yang bersih dan elegan (Tailwind CSS) ketika tautan dibuka.
- Menampilkan nama berkas, ukuran, tanggal unggah, dan sisa batas waktu/unduhan.
- Form input password yang halus jika berkas diproteksi.
- Tampilan preview langsung di browser untuk berkas bertipe:
  - **Gambar:** JPG, PNG, WEBP, GIF, SVG.
  - **Dokumen:** PDF viewer.
  - **Media:** Audio (MP3, WAV) & Video (MP4, WebM).
  - **Teks/Kode:** TXT, Markdown, JSON, Log.

---

## 4. Kebutuhan Non-Fungsional & Keamanan (Security & Architecture)

### 4.1. Keamanan Penyimpanan & File Handling
1. **Abstraksi Nama Berkas di Disk:**
   - Berkas fisik yang disimpan di storage server **tidak menggunakan nama asli**, melainkan menggunakan UUID/Hash (contoh: `storage/uploads/a4/1f/a41fd3...bin`).
   - Mencegah bahaya overwrite berkas, collision nama berkas, dan *Directory Traversal*.
2. **Anti Remote Code Execution (RCE):**
   - Direktori `uploads` di-mount di luar `document_root` atau dikonfigurasi Nginx agar secara ketat memblokir eksekusi skrip PHP atau file executable lainnya.
3. **Validasi MIME Type Mendalam:**
   - Pemeriksaan konten berkas asli via *magic bytes* (`finfo_file` / `mime_content_type`), bukan hanya mempercayai ekstensi yang dikirim client.
4. **Proteksi Brute-Force:**
   - Rate limiting pada input password link download (misal: batasi maksimal 5 percobaan per menit per IP).
5. **Proteksi Form & Akses:**
   - Validasi CSRF token pada setiap form POST/AJAX.
   - Sanitasi input dan proteksi XSS pada nama berkas yang dirender ke HTML.

### 4.2. Performa & Efisiensi Download (Nginx X-Accel-Redirect)
- **Zero-Copy File Serving:** Saat tamu mengunduh berkas besar, PHP hanya memvalidasi hak akses/password. Setelah valid, PHP mengirim header `X-Accel-Redirect` ke Nginx untuk mengalirkan file langsung dari disk.
- Memori PHP-FPM tetap sangat kecil (<16MB) meskipun melayani download berkas berukuran gigabyte.

---

## 5. Arsitektur Teknologi & Lingkungan Docker

| Komponen | Spesifikasi / Pilihan |
| :--- | :--- |
| **OS Host** | Linux (Ubuntu/Debian/Arch/CentOS/Rocky) |
| **Containerization** | Docker Engine & Docker Compose |
| **Web Server** | **Nginx** (Alpine Linux) sebagai Reverse Proxy & Static/X-Accel Server |
| **Backend Runtime** | **PHP 8.4+ FPM** (Native, clean modular MVC/service-based architecture, tanpa framework berat) |
| **Database** | **MariaDB 10.11+ / MySQL 8.0+** (Persistent Volume di Docker) |
| **Frontend** | **Tailwind CSS** + Modern Vanilla JavaScript (Fetch API, Drag & Drop API, ES Modules) |
| **Background Jobs** | Docker Cron Container untuk auto-purge berkas kedaluwarsa |

---

## 6. Struktur Skema Database Utama (Ringkasan)
1. `users` : Data akun, role (admin/user), password hash, quota limit, disk used.
2. `folders` : Struktur direktori bertingkat (hierarki dengan `parent_id`).
3. `files` : Metadata berkas (nama asli, storage path hash, size, mime type, folder_id, user_id).
4. `share_links` : Token unik, file_id, password_hash, expires_at, max_downloads, download_count, is_active.
5. `download_logs` : Catatan audit unduhan (IP address, user agent, waktu unduh).

---

## 7. Rencana Tahapan Implementasi (Milestones)

- **Milestone 1:** Setup Docker Compose (Nginx, PHP-FPM, MariaDB), inisialisasi skema DB, dan konfigurasi lingkungan.
- **Milestone 2:** Modul Autentikasi (Login/Logout/Session) dan Layout Dashboard responsif dengan Tailwind CSS.
- **Milestone 3:** File Manager Core (Struktur folder, upload file dengan validasi keamanan & hashing storage).
- **Milestone 4:** Sharing Engine (Generate share link, password protection, expiry date, download counter).
- **Milestone 5:** Guest Portal & Nginx `X-Accel-Redirect` (Download portal, preview berkas in-browser, streaming efisien).
- **Milestone 6:** Fitur Tambahan & Testing (Chunked upload untuk file besar, background cleanup cron, hardening keamanan).
