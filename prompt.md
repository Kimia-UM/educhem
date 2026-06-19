# Rencana Persiapan Sebelum Push ke GitHub

Rencana ini bertujuan untuk memastikan repositori dalam kondisi bersih, aman (tidak ada kredensial bocor), dan siap digunakan oleh rekan pengembang lain setelah melakukan `git clone`.

---

## Langkah-langkah Persiapan

### 1. Sinkronisasi `.env.example`

#### [MODIFY] [.env.example](file:///d:/Skripsi/pope/.env.example)

Kita perlu menambahkan variabel lingkungan terkait kecerdasan buatan (AI SDK) yang digunakan oleh aplikasi, agar rekan pengembang mengetahui bahwa variabel tersebut harus dikonfigurasi.

Tambahkan baris berikut di bagian akhir `.env.example` tanpa menyertakan API Key asli:
```env
# KONFIGURASI AI SDK
AI_DEFAULT_PROVIDER=gemini
GEMINI_API_KEY=
AI_GEMINI_MODEL=Gemini-2.5-Flash
```

---

### 2. Pemeriksaan File Migrasi & Database

Pastikan file migrasi baru dan perubahan seeder yang telah dibuat ikut ter-stage untuk di-commit:
* **Migration**: [2026_06_19_024230_add_is_evaluation_finished_to_class_members_table.php](file:///d:/Skripsi/pope/database/migrations/2026_06_19_024230_add_is_evaluation_finished_to_class_members_table.php) (Menyimpan status evaluasi siswa).
* **Seeders**: Pastikan `RoleAndAdminSeeder.php` dan `TestAccountsSeeder.php` dapat dijalankan dengan aman.

---

### 3. Keamanan Kredensial

* **File `.env`**: Sudah terverifikasi berada di dalam file `.gitignore` sehingga aman dari komit tidak sengaja.
* **Kredensial API**: Pastikan tidak ada API Key (seperti `GEMINI_API_KEY`) yang tertulis secara langsung (*hardcoded*) di dalam file Vue atau PHP. (Semua pemanggilan API sejauh ini menggunakan file `.env`).

---

### 4. Git Commit Setup

Kita akan melakukan komit untuk semua file yang berubah dan file baru. Commit akan dikelompokkan dengan pesan terstruktur:

**Suggested Commit Message:**
```text
feat: kustomisasi tema visual LC5E, perbaikan dashboard admin & navigasi sidebar

- Mengubah model pembelajaran dari POE ke LC5E secara menyeluruh
- Menghubungkan dashboard superadmin dengan data real database (user, kelas, dll.)
- Menghapus card "Aktivitas Siswa" di Dashboard Guru
- Memperbaiki warna glow navigasi aktif sidebar sesuai dengan tema aktif
- Menambahkan kolom status evaluasi siswa pada kelas
```

---

### 5. Update README.md

#### [MODIFY] [README.md](file:///d:/Skripsi/pope/README.md)

File README.md perlu diperbarui untuk menggambarkan sistem terbaru dengan akurat. Bagian-bagian yang perlu ditambahkan atau diperbarui:

**Bagian yang Harus Diperbarui:**

1. **Gambaran Umum (Overview)**
   - Jelaskan bahwa aplikasi menggunakan model pembelajaran LC5E (dari POE)
   - Sebutkan tujuan utama: platform pembelajaran interaktif untuk guru dan siswa
   - Highlight fitur utama (dashboard, manajemen kelas, latihan soal, integrasi AI, dll.)

2. **Fitur Utama (Key Features)**
   - Dashboard untuk Superadmin, Guru, dan Siswa
   - Manajemen kelas dan topik pembelajaran
   - Sistem latihan soal dengan evaluasi otomatis menggunakan AI (Gemini)
   - Integrasi chat AI untuk pembelajaran interaktif
   - Tracking progress siswa dan video watch history
   - Sistem role-based access control (RBAC)

3. **Tech Stack**
   - **Backend**: Laravel 11 (PHP)
   - **Frontend**: Vue 3, TypeScript, Tailwind CSS
   - **Database**: MySQL/PostgreSQL
   - **AI Integration**: Google Gemini SDK
   - **Build Tools**: Vite, PNPM

4. **Persyaratan Sistem (System Requirements)**
   - PHP 8.2 atau lebih tinggi
   - Node.js 18+ dan PNPM
   - MySQL 8.0+ atau PostgreSQL 12+
   - API Key Google Gemini

5. **Struktur Folder (Project Structure)**
   - Penjelasan singkat tentang struktur direktori utama (app, resources, routes, database, dll.)

6. **Catatan untuk Kontributor**
   - Kontribusi hanya diterima dari anggota tim resmi
   - Ikuti conventional commits untuk pesan commit
   - Jalankan tests sebelum push: `php artisan test`
   - Ensure code quality dengan linter: `npm run lint`

---

## Panduan Inisialisasi Untuk Rekan Pengembang (Setup Guide)

Setelah rekan Anda melakukan `git clone`, mereka harus menjalankan langkah-langkah berikut untuk setup:

1. **Salin Environment Variables**:
   ```bash
   cp .env.example .env
   ```
   *Isi `GEMINI_API_KEY` di file `.env` baru tersebut dengan API Key Gemini mereka.*

2. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Generate App Key**:
   ```bash
   php artisan key:generate
   ```

4. **Migrasi Database & Seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Jalankan Development Server**:
   ```bash
   # Terminal 1
   php artisan serve
   
   # Terminal 2
   npm run dev
   ```

---

## Rencana Verifikasi (Oleh User)

Verifikasi dilakukan secara manual oleh rekan Anda setelah melakukan clone:
1. Pastikan proses migrasi database dan pengisian seeder (`TestAccountsSeeder` & `RoleAndAdminSeeder`) berjalan sukses tanpa error.
2. Login sebagai guru (`guru@test.com`) dan siswa (`siswa1@test.com`) untuk memastikan fitur dashboard berjalan normal.
3. Pastikan tema navigasi sidebar dapat menyala secara dinamis dan tidak error.
