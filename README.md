# EduChem LMS 🧪

EduChem adalah sebuah Platform Learning Management System (LMS) interaktif yang dirancang khusus untuk pembelajaran Kimia menggunakan metode pedagogi **POE (Predict-Observe-Explain)**. Sistem ini dilengkapi dengan *Dynamic Content Builder* untuk Guru dan antarmuka lembar kerja interaktif untuk Siswa.

## 🚀 Teknologi yang Digunakan
Sistem ini dibangun di atas arsitektur *Monolith Modern* (Server-Driven SPA):
- **Backend:** Laravel 11
- **Frontend:** Vue.js 3 (Composition API) + Inertia.js
- **Styling:** Tailwind CSS
- **UI Components:** shadcn-vue (Radix Vue)
- **Rich Text Editor:** VueQuill (Mendukung Subscript/Superscript untuk rumus Kimia)
- **Role Management:** Spatie Laravel Permission

## 📋 Prasyarat Sistem
Pastikan perangkat lunak berikut sudah terinstal di komputer Anda sebelum menjalankan aplikasi:
- **PHP** >= 8.2
- **Composer** (Package Manager untuk PHP)
- **Node.js** (Disarankan v18 LTS ke atas) & **NPM**
- **MySQL** atau MariaDB

---

## 🛠️ Panduan Instalasi (Langkah-demi-Langkah)

Ikuti langkah di bawah ini untuk menjalankan *project* ini di *local machine* (komputer) Anda.

**1. Clone Repositori**
Buka terminal dan jalankan perintah berikut:
```bash
git clone [https://github.com/USERNAME_GITHUB_ANDA/lms-fmipa.git](https://github.com/USERNAME_GITHUB_ANDA/lms-fmipa.git)
cd lms-fmipa
```
2. Install Dependensi Backend (PHP)
```Bash
composer install
```
3. Install Dependensi Frontend (JavaScript/Vue)
```Bash
npm install
```
4. Konfigurasi Environment & Database
Salin file .env.example dan ubah namanya menjadi .env:
```Bash
cp .env.example .env
```
Buka file .env menggunakan code editor Anda, lalu sesuaikan bagian kredensial database. Pastikan Anda sudah membuat database kosong (misal bernama educhem_db) di MySQL/phpMyAdmin:
```Code snippet
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=educhem_db
DB_USERNAME=root
DB_PASSWORD=
```
5. Generate Application Key & Tautkan Folder Storage
```Bash
php artisan key:generate
php artisan storage:link
```
6. Jalankan Migrasi Database & Seeder
(Catatan: Perintah ini akan menghapus tabel lama jika ada, membuat tabel baru, dan mengisi data awal / dummy data).
```Bash
php artisan migrate:fresh --seed
```
💻 Cara Menjalankan Aplikasi (Development)
Untuk menjalankan aplikasi dengan fitur Hot-Module Replacement (HMR), Anda perlu membuka dua tab terminal secara bersamaan.
Terminal 1 (Menjalankan Server Backend Laravel):
```Bash
php artisan serve
```
Terminal 2 (Menjalankan Server Frontend Vite):
```Bash
npm run dev
```
