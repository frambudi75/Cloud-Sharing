# Design Direction (DESIGN.md)

Reading this as: Web-based File Sharing Cloud Utility for teams and external guests, in a Slate & Crisp Indigo modern system, dial ENERGY 2 / RHYTHM 2 / MOTION 1.

---

## 1. Dials & Identity
* **Dial**: `ENERGY 2 / RHYTHM 2 / MOTION 1`
  * **ENERGY 2 (Balanced)**: Terlihat profesional, tepercaya, dan berorientasi pada produktivitas utilitas cloud. Bukan flat tanpa ekspresi, tapi tidak berlebihan dengan tren glow/mesh yang mengganggu.
  * **RHYTHM 2 (Structured with Intentional Breaks)**: Tata letak hierarki file/folder terstruktur dengan card statistik storage dan area drag-and-drop yang tegas.
  * **MOTION 1 (Subtle & Functional)**: Transisi halus pada hover, state upload progress bar yang akurat, modal pop-in yang cepat dan responsif. Tanpa animasi dekoratif mengambang yang memperlambat alur kerja.

---

## 2. Color Palette & Purpose
* **Neutrals (Slate & Pure White/Charcoal)**:
  * Background Light: `#f8fafc` (Slate 50) dengan kartu `#ffffff` dan border `#e2e8f0` (Slate 200).
  * Dark Surface (opsional/kontras): `#0f172a` (Slate 900) dan `#1e293b` (Slate 800).
  * Teks Utama: `#0f172a` (Slate 900) - Rasio kontras 15:1 (melebihi standar WCAG AAA).
  * Teks Sekunder: `#475569` (Slate 600) - Rasio kontras 5.5:1 (melebihi WCAG AA 4.5:1).
* **Primary Accent (Crisp Indigo)**:
  * Brand & Active CTA: `#4f46e5` (Indigo 600) / Hover `#4338ca` (Indigo 700).
  * Focus Ring: `#6366f1` (Indigo 500) dengan outline offset 2px.
  * *Purpose*: Indigo dipilih sebagai simbol reliabilitas, keamanan transfer data, dan kejelasan navigasi tindakan utama.
* **Semantic Accents**:
  * Success / Active Link: `#059669` (Emerald 600).
  * Warning / Expiry Near: `#d97706` (Amber 600).
  * Danger / Delete: `#dc2626` (Red 600).

---

## 3. Typography
* **Font Family**: Modern Sans-Serif (`Inter`, `-apple-system`, `BlinkMacSystemFont`, `Segoe UI`, sans-serif).
* **Hierarchy**:
  * Page Title: `text-xl font-bold tracking-tight text-slate-900`
  * Section Header: `text-base font-semibold text-slate-800`
  * Body Text: `text-sm font-normal text-slate-700`
  * Caption & Metadata: `text-xs font-medium text-slate-500`
* *Purpose*: Ukuran dan hierarki tipografi diatur padat dan mudah dipindai untuk manajemen puluhan berkas tanpa membuang ruang layar.

---

## 4. UI Components & Craftsmanship Rules
1. **Interactive Elements**:
   * Setiap tombol dan link memiliki aksi nyata (upload, download, buat folder, hapus, share modal, copy link).
   * Tombol modal dan drawer dapat ditutup dengan tombol `Escape` dan klik di luar area modal.
   * Ukuran tombol dan area klik minimal 44x44px pada layar mobile/sentuh.
2. **File Previewer**:
   * Menampilkan berkas asli sesuai MIME type (Gambar, Dokumen PDF, Video, Audio, Teks).
3. **States Wajib**:
   * **Empty State**: Ilustrasi icon folder kosong yang informatif dengan tombol ajakan upload.
   * **Loading State**: Progress bar real-time selama upload chunk.
   * **Error State**: Pesan validasi yang jelas jika password salah, file melebihi kuota, atau link kedaluwarsa.
4. **Copywriting Hygiene**:
   * Tidak menggunakan tanda em-dash (`—`), buzzword AI yang klise, data palsu, atau testimonial fiktif.
