# Development Rules & Coding Standards

Dokumen ini berisi standar kode dan aturan pengembangan untuk proyek File Sharing (PHP Native + Tailwind CSS + MariaDB + Docker).

---

## 1. Aturan PHP Backend
1. **PHP Versi & Strict Mode:**
   - Gunakan fitur modern PHP 8.2+.
   - Selalu deklarasikan `declare(strict_types=1);` pada baris pertama setiap file PHP.
2. **Keamanan Database (PDO):**
   - Wajib menggunakan `PDO` dengan *Prepared Statements* untuk semua query dinamis. Dilarang merangkai query langsung (*string concatenation / interpolation*) untuk mencegah SQL Injection.
   - Atur PDO mode ke `PDO::ERRMODE_EXCEPTION` dan `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.
3. **Penyimpanan Berkas Fisik:**
   - Dilarang menyimpan file di disk menggunakan nama berkas asli dari user. Wajib di-generate menggunakan `bin2hex(random_bytes(16))` atau UUIDv4.
   - Simpan berkas di luar folder `public/` (misal: `/storage/uploads/`).
4. **Download Stream:**
   - Untuk serving file kepada client, wajib mengutamakan header `X-Accel-Redirect` (Nginx) agar tidak membebani memori PHP.

---

## 2. Aturan Frontend & Desain UI
1. **Tailwind CSS & Styling:**
   - Gunakan palet warna yang harmonis dan profesional (Slate/Zinc/Indigo).
   - Terapkan micro-interaction pada tombol (hover, active, transition).
   - Buat tampilan yang responsif (mobile, tablet, desktop) dengan tap target yang ramah sentuhan (min 44px).
2. **JavaScript:**
   - Gunakan modern Vanilla JavaScript (Fetch API, Async/Await).
   - Terapkan feedback visual saat proses upload (progress bar, loading indicator).

---

## 3. Docker & Deployment
1. Jangan menyimpan kredensial atau secret di dalam git (gunakan `.env` dan sediakan `.env.example`).
2. Semua direktori upload dan database wajib di-*mount* ke Docker persistent volume agar data tidak hilang saat container di-*rebuild*.
