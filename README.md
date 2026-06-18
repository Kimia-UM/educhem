# EduChem LMS 🧪

EduChem adalah sebuah Platform Learning Management System (LMS) interaktif yang dirancang khusus untuk pembelajaran Kimia menggunakan metode pedagogi **LC5E (Predict-Observe-Explain)**. Sistem ini dilengkapi dengan _Dynamic Content Builder_ untuk Guru, antarmuka lembar kerja interaktif untuk Siswa, dan **AI Tutor Chatbot**.

## 🚀 Teknologi yang Digunakan

Sistem ini dibangun di atas arsitektur _Monolith Modern_ (Server-Driven SPA):

- **Backend:** Laravel 13, PHP 8.2+
- **Frontend:** Vue.js 3 (Composition API) + Inertia.js
- **Styling & UI:** Tailwind CSS, shadcn-vue (Radix Vue), Lucide Icons
- **AI & Integrasi:** Google Gemini AI (Agentic AI), KaTeX (Scientific Math/Chemistry Renderer)
- **Database & Task:** MySQL, Laravel Queue (Background Processing)
- **Role Management:** Spatie Laravel Permission

---

## 📋 Prasyarat Sistem

Pastikan perangkat lunak berikut sudah terinstal di komputer Anda:

- **PHP** >= 8.2
- **Composer** (Package Manager untuk PHP)
- **Node.js** (Disarankan v18 LTS ke atas) & **NPM**
- **MySQL** atau MariaDB

---

## 🛠️ Panduan Instalasi (Langkah-demi-Langkah)

Ikuti langkah di bawah ini untuk menjalankan _project_ ini di komputer Anda.

**1. Clone Repositori**
Buka terminal dan jalankan perintah berikut:

```bash
git clone [https://github.com/Fadhelnaufal/pope.git](https://github.com/Fadhelnaufal/pope.git)
cd pope
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

Buka file .env menggunakan code editor Anda. Sesuaikan bagian kredensial database dan pastikan Anda mengaktifkan antrean database serta memasukkan API Key Gemini:
Code snippet

# Konfigurasi Database (Pastikan buat database kosong bernama lms_educhem di phpMyAdmin)

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_educhem
DB_USERNAME=root
DB_PASSWORD=
```

# Konfigurasi Queue untuk Background Job Chatbot AI

```
QUEUE_CONNECTION=database
```

# Konfigurasi Google Gemini AI

```
GEMINI_API_KEY=masukkan_api_key_gemini_anda_di_sini
```

5. Generate Application Key & Tautkan Folder Storage

```Bash
php artisan key:generate
php artisan storage:link
```

6. Jalankan Migrasi Database & Seeder
   (Catatan: Perintah ini akan membuat tabel baru dan mengisi data awal / dummy data termasuk akun pengguna).

```Bash
php artisan migrate:fresh --seed
```

💻 Cara Menjalankan Aplikasi (Development)
Untuk menjalankan aplikasi secara penuh beserta fitur AI Chatbot, Anda wajib membuka 3 tab terminal secara bersamaan:
Terminal 1 (Server Web Laravel):

```Bash
php artisan serve
```

Terminal 2 (Server Frontend / Vite):

```Bash
npm run dev
```

Terminal 3 (Server Antrean / Worker AI):

```Bash
php artisan queue:work --timeout=120
```
