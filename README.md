# EduChem LMS 🧪

EduChem adalah sebuah Platform Learning Management System (LMS) interaktif yang dirancang khusus untuk pembelajaran Kimia menggunakan model pembelajaran **LC5E** (Learning Cycle dengan 5 fase). Sistem ini dilengkapi dengan _Dynamic Content Builder_ untuk Guru, antarmuka lembar kerja interaktif untuk Siswa, sistem evaluasi otomatis dengan AI, dan **AI Tutor Chatbot**.

## 📌 Gambaran Umum

Aplikasi ini adalah platform pembelajaran modern yang memfasilitasi:

- **Guru**: Membuat konten pembelajaran, mengelola kelas, memonitor progres siswa, dan evaluasi hasil belajar
- **Siswa**: Mengakses materi pembelajaran, mengerjakan latihan soal, mendapat feedback AI, dan berinteraksi dengan chatbot pembelajaran
- **Superadmin**: Mengelola data pengguna, kelas, dan statistik pembelajaran secara menyeluruh

Fitur-fitur utama dirancang untuk mendukung pembelajaran interaktif dengan integrasi AI (Google Gemini) untuk evaluasi otomatis dan chatbot tutoring.

---

## ✨ Fitur Utama

- 📊 **Dashboard Multi-Role**: Dashboard khusus untuk Superadmin, Guru, dan Siswa dengan visualisasi data real-time
- 👥 **Manajemen Kelas & Siswa**: Pembuatan kelas, penambahan anggota, dan tracking status evaluasi
- 📚 **Konten Pembelajaran Dinamis**: Guru dapat membuat fase pembelajaran, video, dan topik dengan mudah
- ✍️ **Sistem Latihan Soal**: Siswa mengerjakan soal dengan otomatis dievaluasi oleh AI (Gemini)
- 🤖 **AI Tutor Chatbot**: Chat interaktif dengan AI untuk membantu menjawab pertanyaan siswa terkait materi
- 📊 **Progress Tracking**: Monitoring watch history video dan hasil evaluasi siswa secara detail
- 🔐 **Role-Based Access Control (RBAC)**: Sistem izin berbasis peran untuk keamanan aplikasi
- 💬 **Diskusi Fase**: Ruang diskusi interaktif per fase pembelajaran

---

## 🚀 Tech Stack

| Aspek              | Teknologi                              |
| ------------------ | -------------------------------------- |
| **Backend**        | Laravel 11, PHP 8.2+                   |
| **Frontend**       | Vue 3, TypeScript, Inertia.js          |
| **Styling**        | Tailwind CSS, Shadcn-vue, Lucide Icons |
| **Database**       | PostgreSQL / MySQL                     |
| **AI Integration** | Google Gemini 2.0 Flash SDK            |
| **Build Tools**    | Vite, PNPM                             |
| **Queue & Jobs**   | Laravel Queue (Background Processing)  |
| **Permission**     | Spatie Laravel Permission              |
| **Documentation**  | KaTeX (Math & Chemistry Renderer)      |

---

## 📋 Prasyarat Sistem

Pastikan perangkat lunak berikut sudah terinstal di komputer Anda:

- **PHP** >= 8.2
- **Composer** (Package Manager untuk PHP)
- **Node.js** (v18 LTS atau lebih baru) & **PNPM** (atau NPM)
- **MySQL** 8.0+ atau **PostgreSQL** 12+
- **Google Gemini API Key** (untuk fitur AI)

---

## 🛠️ Panduan Instalasi (Langkah-demi-Langkah)

### 1. Clone Repositori

Buka terminal dan jalankan perintah berikut:

```bash
git clone https://github.com/Kimia-UM/educhem.git
cd educhem
```

### 2. Install Dependensi

**Backend (PHP dengan Composer):**

```bash
composer install
```

**Frontend (JavaScript/Node.js):**

```bash
pnpm install
# atau menggunakan npm:
npm install
```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan konfigurasi database dengan database yang sudah Anda buat:

```env
# Database Configuration - Sesuaikan dengan database Anda
DB_CONNECTION=          # pgsql atau mysql
DB_HOST=                # localhost atau IP server database
DB_PORT=                # 5432 untuk PostgreSQL, 3306 untuk MySQL
DB_DATABASE=            # Nama database yang sudah dibuat
DB_USERNAME=            # Username database
DB_PASSWORD=            # Password database

# Queue Configuration (untuk background jobs)
QUEUE_CONNECTION=database

# AI Configuration (Dapatkan API key dari Google AI Studio)
AI_DEFAULT_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key_here
AI_GEMINI_MODEL=gemini-2.0-flash
```

### 4. Generate Application Key & Link Storage

```bash
php artisan key:generate
php artisan storage:link
```

### 5. Jalankan Migrasi Database & Seeder

```bash
php artisan migrate:fresh --seed
```

Perintah ini akan membuat tabel dan mengisi data awal termasuk akun test:

- **Guru**: guru@test.com
- **Siswa**: siswa1@test.com
- **Admin**: admin@test.com

---

## 💻 Menjalankan Aplikasi (Development)

Buka 3 terminal secara bersamaan:

**Terminal 1 - Laravel Server:**

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

**Terminal 2 - Frontend Development Server:**

```bash
pnpm run dev
# atau: npm run dev
```

**Terminal 3 - Queue Worker (untuk background jobs):**

```bash
php artisan queue:work --timeout=120
```

---

## 📁 Struktur Proyek

```
pope/
├── app/
│   ├── Actions/              # Action classes untuk autentikasi
│   ├── Concerns/             # Reusable concerns (traits)
│   ├── Http/
│   │   ├── Controllers/      # Application controllers
│   │   ├── Middleware/       # HTTP middleware
│   │   └── Requests/         # Form request validations
│   ├── Jobs/                 # Queued jobs (AI evaluation, chat processing)
│   ├── Models/               # Eloquent models
│   ├── Providers/            # Service providers
│   └── Services/             # Business logic services
├── config/                   # Configuration files
├── database/
│   ├── migrations/           # Database migrations
│   ├── seeders/              # Database seeders
│   └── factories/            # Model factories
├── resources/
│   ├── js/                   # Vue components & TypeScript
│   ├── css/                  # Tailwind styles
│   └── views/                # Blade templates
├── routes/
│   ├── web.php              # Web routes
│   ├── api.php              # API routes (jika ada)
│   └── console.php          # Console commands
├── storage/                  # File storage
├── tests/                    # Unit & Feature tests
├── public/                   # Public assets
├── vendor/                   # Composer dependencies (gitignored)
└── node_modules/            # NPM dependencies (gitignored)
```

**Model Utama:**

- `User` - Data pengguna dengan roles (admin, guru, siswa)
- `Classroom` - Kelas pembelajaran
- `Topic` - Topik/materi pembelajaran
- `TopicPhase` - Fase pembelajaran (berdasarkan LC5E)
- `Question` - Soal latihan
- `StudentAnswer` - Jawaban siswa terhadap soal
- `ExerciseAttempt` - Tracking attempt latihan
- `AiChatLog` - Logging chat AI
- `ClassMember` - Hubungan siswa dengan kelas

---

## 🧪 Testing

Jalankan test suite:

```bash
# Menjalankan semua test
php artisan test

# Menjalankan test spesifik
php artisan test --filter=NamaTest

# Dengan coverage report
php artisan test --coverage
```

---

## 📝 Code Quality & Linting

Ensure kode berkualitas tinggi:

```bash
# Lint JavaScript/TypeScript
npm run lint

# Format kode dengan Pint (PHP)
php artisan pint

# ESLint fix
npm run lint:fix
```

---

## 🤝 Kontribusi

Kontribusi hanya diterima dari anggota tim resmi. Jika Anda ingin berkontribusi:

1. **Commit Message**: Ikuti format [Conventional Commits](https://www.conventionalcommits.org/)
    - Contoh: `feat: tambah fitur chat AI`, `fix: perbaiki bug di dashboard`
2. **Pull Request**: Buat PR dengan deskripsi yang jelas tentang perubahan
3. **Testing**: Pastikan semua test lolos sebelum push
4. **Code Quality**: Jalankan linter dan formatter sebelum commit

Format pesan commit:

```
<type>(<scope>): <subject>

<body>

<footer>
```

Tipe commit yang umum:

- `feat` - Fitur baru
- `fix` - Perbaikan bug
- `docs` - Dokumentasi
- `style` - Perubahan style (formatting, missing semicolons, dll)
- `refactor` - Refactoring kode tanpa menambah fitur
- `perf` - Optimasi performa
- `test` - Menambah atau memperbaiki test

---

## 📄 Lisensi

Proyek ini adalah bagian dari penelitian Skripsi dan hanya boleh digunakan oleh tim resmi.

---

## 📧 Kontak & Support

Untuk pertanyaan atau masalah, hubungi pengembang utama atau tim peneliti.
