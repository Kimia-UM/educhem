# Panduan mandiri k6 EduChem

Panduan ini menjalankan dua pengujian yang tersedia di `tests/load/educhem-staging.js`:

1. **Web/autosave:** naik bertahap sampai 50 siswa aktif.
2. **AI canary:** 5 siswa mengirim jawaban AI dan menunggu feedback Gemini.

Script memakai akun, kelas, topik, fase, dan jawaban sintetis yang terisolasi. Jangan mengganti email sintetis dengan akun siswa sungguhan.

## 1. Aturan keselamatan production

- Jalankan di luar jam kelas.
- Pastikan backup database terbaru tersedia.
- Buka grafik resource Hostinger selama tes.
- Mulai dari smoke test 1 VU; lanjutkan ke 50 VU hanya jika smoke test bersih.
- Jangan menutup terminal sebelum mencatat `run-id`, email prefix, ID data, dan password sintetis.
- Jangan menjalankan cleanup ketika k6 atau queue `ai-chat`, `ai-evaluation`, maupun legacy `ai` masih bekerja.
- Test AI memanggil Gemini dan dapat menimbulkan biaya API.

## 2. Install dan verifikasi k6 di Windows

Buka PowerShell:

```powershell
winget install k6 --source winget
k6 version
```

Jika command belum ditemukan setelah instalasi, tutup dan buka kembali PowerShell. Alternatifnya, gunakan Chocolatey atau binary resmi k6.

Referensi: [dokumentasi instalasi resmi Grafana k6](https://grafana.com/docs/k6/latest/set-up/install-k6/).

Masuk ke root repository:

```powershell
Set-Location 'D:\Downloads\kimia-educhem\educhem'
Test-Path 'tests\load\educhem-staging.js'
```

Hasil `Test-Path` harus `True`.

## 3. Tentukan identitas test run

Di PowerShell lokal, buat `run-id` unik. Nilainya harus 1–20 karakter dan hanya berisi huruf kecil, angka, atau tanda hubung.

```powershell
$RunId = 'manual' + (Get-Date -Format 'yyMMddHHmm')
$EmailPrefix = "load.$RunId"
$EmailDomain = 'load.educhem.test'

$RunId
$EmailPrefix
```

Jangan mengubah ketiga nilai tersebut sampai cleanup selesai.

## 4. Siapkan 50 akun dan worksheet sintetis

Jalankan command berikut dari PowerShell lokal. SSH akan meminta password server. Artisan kemudian meminta **password sintetis siswa** minimal 12 karakter dan konfirmasi pembuatan data.

```powershell
ssh -t -p 65002 u191387983@153.92.11.33 "cd /home/u191387983/educhem-current && /usr/bin/php artisan load-test:prepare --students=50 --email-prefix=$EmailPrefix --email-domain=$EmailDomain --run-id=$RunId --allow-production"
```

Simpan output berikut:

```text
CLASSROOM_ID
TOPIC_ID
WEB_PHASE_ID
MCQ_CONTENT_ID
ESSAY_CONTENT_ID
AI_PHASE_ID
AI_CONTENT_ID
```

Command aman untuk diulang dengan `run-id` yang sama, tetapi akan mereset password 50 akun sintetis tersebut.

## 5. Isi konfigurasi k6 di PowerShell

Gunakan angka yang dicetak command sebelumnya:

```powershell
$env:BASE_URL = 'https://educhem.id'
$env:STUDENT_COUNT = '50'
$env:EMAIL_PREFIX = $EmailPrefix
$env:EMAIL_DOMAIN = $EmailDomain

$env:CLASSROOM_ID = '<nilai CLASSROOM_ID>'
$env:TOPIC_ID = '<nilai TOPIC_ID>'
$env:WEB_PHASE_ID = '<nilai WEB_PHASE_ID>'
$env:MCQ_CONTENT_ID = '<nilai MCQ_CONTENT_ID>'
$env:ESSAY_CONTENT_ID = '<nilai ESSAY_CONTENT_ID>'
$env:AI_PHASE_ID = '<nilai AI_PHASE_ID>'
$env:AI_CONTENT_ID = '<nilai AI_CONTENT_ID>'

$SyntheticPassword = Read-Host 'Password sintetis yang sama seperti saat prepare' -AsSecureString
$env:K6_PASSWORD = [System.Net.NetworkCredential]::new('', $SyntheticPassword).Password
```

Jangan menaruh password di file `.ps1`, Git, chat, atau command history.

## 6. Jalankan smoke test 1 siswa

Smoke test berlangsung sekitar satu menit:

```powershell
$env:MODE = 'web'
$env:MAX_VUS = '1'
$env:WEB_STAGE_10 = '10s'
$env:WEB_STAGE_25 = '10s'
$env:WEB_STAGE_50 = '10s'
$env:WEB_HOLD_50 = '20s'
$env:WEB_RAMP_DOWN = '10s'

$SmokeSummary = Join-Path $env:TEMP "educhem-k6-smoke-$RunId.json"
k6 run --summary-export $SmokeSummary tests/load/educhem-staging.js
$LASTEXITCODE
$SmokeSummary
```

Lanjutkan hanya jika:

- `$LASTEXITCODE` bernilai `0`.
- `checks_succeeded` 100%.
- `flow_errors` bernilai `0`.
- `http_req_failed` bernilai 0%.
- Login, worksheet, jawaban PG, dan jawaban esai semuanya berhasil.

## 7. Jalankan test web 50 siswa

Profil penuh berlangsung sekitar 10 menit: naik ke 10 siswa, 25 siswa, 50 siswa, menahan 50 siswa selama 5 menit, lalu turun.

```powershell
$env:MODE = 'web'
$env:MAX_VUS = '50'
$env:WEB_STAGE_10 = '1m'
$env:WEB_STAGE_25 = '1m'
$env:WEB_STAGE_50 = '2m'
$env:WEB_HOLD_50 = '5m'
$env:WEB_RAMP_DOWN = '1m'

$WebSummary = Join-Path $env:TEMP "educhem-k6-web-$RunId.json"
k6 run --summary-export $WebSummary tests/load/educhem-staging.js
$WebExitCode = $LASTEXITCODE

$WebExitCode
$WebSummary
```

Threshold bawaan:

| Metrik                     | Syarat lulus          |
| -------------------------- | --------------------- |
| `checks`                   | lebih dari 99%        |
| `flow_errors`              | 0                     |
| `http_req_failed`          | kurang dari 1%        |
| `worksheet_duration` p95   | kurang dari 2,5 detik |
| `answer_save_duration` p95 | kurang dari 3 detik   |

Exit code `0` berarti seluruh threshold lulus. Exit code nonzero harus dibaca bersama bagian `THRESHOLDS`, `checks_failed`, `flow_errors`, dan log Laravel; jangan hanya melihat rata-rata waktu respons.

## 8. Monitoring selama test

### Terminal SSH kedua

```bash
ssh -p 65002 u191387983@153.92.11.33
cd /home/u191387983/educhem-current
tail -f storage/logs/laravel.log
```

Hentikan `tail` dengan `Ctrl+C`.

Untuk mengecek queue dan failed job:

```bash
/usr/bin/php artisan queue:monitor database:ai-chat,database:ai-evaluation,database:ai --max=1 --no-ansi
/usr/bin/php artisan queue:failed --no-ansi
```

Di hPanel, pantau CPU, RAM, I/O, Entry Processes, dan respons 503/504. Hentikan k6 dengan `Ctrl+C` jika banyak request gagal, siswa nyata terdampak, atau resource terus menyentuh batas.

## 9. Jalankan canary AI 5 siswa

Jalankan hanya setelah test web selesai. Pastikan cron berikut sudah aktif setiap menit di hPanel:

```bash
/usr/bin/php /home/u191387983/educhem-current/artisan schedule:run
```

Kemudian di PowerShell lokal:

```powershell
$env:MODE = 'ai'
$env:AI_VUS = '5'
$env:AI_POLL_ATTEMPTS = '16'

$AiSummary = Join-Path $env:TEMP "educhem-k6-ai-$RunId.json"
k6 run --summary-export $AiSummary tests/load/educhem-staging.js
$AiExitCode = $LASTEXITCODE

$AiExitCode
$AiSummary
```

Test dinyatakan berhasil secara fungsional jika kelima alur menunjukkan:

- `student login succeeds`.
- `AI worksheet opens`.
- `ai answer saves`.
- `AI status request succeeds`.
- `AI feedback is present`.
- `flow_errors` bernilai `0`.
- Queue `ai` kembali `0` dan tidak ada failed job.

Threshold p95 endpoint status AI adalah 2 detik. Exit code bisa nonzero jika ada satu lonjakan latency meskipun seluruh feedback berhasil; catat kondisi ini sebagai kegagalan performa, bukan kegagalan fungsi.

Jika cron belum aktif, setelah k6 dimulai jalankan worker berikut sekali dari terminal SSH kedua:

```bash
cd /home/u191387983/educhem-current
/usr/bin/php artisan queue:work database --queue=ai-chat,ai-evaluation,ai --stop-when-empty --max-jobs=10 --max-time=300 --sleep=1 --tries=3 --timeout=120 --no-ansi
```

Jangan menjalankan worker manual bersamaan dengan cron yang sudah terbukti aktif.

## 10. Jalankan test hybrid chatbot 50 siswa

Test ini mengirim satu pertanyaan unik dari setiap siswa. Maksimal empat panggilan Gemini berjalan langsung; sisanya otomatis masuk queue prioritas chatbot dan dipantau melalui endpoint status per `log_id`.

```powershell
$env:MODE = 'chat'
$env:CHAT_VUS = '50'
$env:CHAT_RUN_ID = $RunId
$env:CHAT_POLL_ATTEMPTS = '16'
$env:CHAT_RAMP_SECONDS = '10'

$ChatSummary = Join-Path $env:TEMP "educhem-k6-chat-$RunId.json"
k6 run --summary-export $ChatSummary tests/load/educhem-staging.js
$ChatExitCode = $LASTEXITCODE

$ChatExitCode
$ChatSummary
```

Threshold bawaan:

| Metrik                     | Syarat lulus         |
| -------------------------- | -------------------- |
| `checks`                   | lebih dari 99%       |
| `flow_errors`              | 0                    |
| `http_req_failed`          | kurang dari 1%       |
| `chat_submit_duration` p95 | kurang dari 15 detik |
| `chat_status_duration` p95 | kurang dari 2 detik  |
| `chat_total_duration` p95  | kurang dari 90 detik |

Laporan juga memuat `chat_direct_result` dan `chat_queued_result` untuk menunjukkan proporsi jalur direct dibanding fallback queue.

`CHAT_RAMP_SECONDS=10` menyebarkan login awal 50 siswa selama 10 detik. Semua VU tetap aktif bersamaan saat menunggu respons, tetapi server tidak menerima 50 pembukaan koneksi MySQL pada milidetik yang sama. Gunakan `0` hanya untuk stress test lonjakan ekstrem yang terpisah dari uji kapasitas kelas normal.

## 11. Cleanup wajib

Pastikan k6 sudah berhenti dan queue `ai` kosong:

```powershell
ssh -t -p 65002 u191387983@153.92.11.33 "cd /home/u191387983/educhem-current && /usr/bin/php artisan queue:monitor database:ai-chat,database:ai-evaluation,database:ai --max=1 --no-ansi"
```

Kemudian hapus hanya data sintetis dari run ini:

```powershell
ssh -t -p 65002 u191387983@153.92.11.33 "cd /home/u191387983/educhem-current && /usr/bin/php artisan load-test:cleanup --email-prefix=$EmailPrefix --email-domain=$EmailDomain --run-id=$RunId --allow-production"
```

Konfirmasikan penghapusan ketika diminta. Output normal untuk 50 siswa adalah 51 users, 1 class, dan 1 topic terhapus. Jawaban serta konten sintetis ikut terhapus melalui relasi database.

Bersihkan password dan variabel dari PowerShell:

```powershell
$env:K6_PASSWORD = $null
$SyntheticPassword = $null

$env:BASE_URL = $null
$env:MODE = $null
$env:STUDENT_COUNT = $null
$env:MAX_VUS = $null
$env:EMAIL_PREFIX = $null
$env:EMAIL_DOMAIN = $null
$env:CLASSROOM_ID = $null
$env:TOPIC_ID = $null
$env:WEB_PHASE_ID = $null
$env:MCQ_CONTENT_ID = $null
$env:ESSAY_CONTENT_ID = $null
$env:AI_PHASE_ID = $null
$env:AI_CONTENT_ID = $null
$env:AI_VUS = $null
$env:AI_POLL_ATTEMPTS = $null
$env:CHAT_VUS = $null
$env:CHAT_RUN_ID = $null
$env:CHAT_POLL_ATTEMPTS = $null
$env:CHAT_RAMP_SECONDS = $null
$env:WEB_STAGE_10 = $null
$env:WEB_STAGE_25 = $null
$env:WEB_STAGE_50 = $null
$env:WEB_HOLD_50 = $null
$env:WEB_RAMP_DOWN = $null
```

## 12. Masalah umum

| Gejala                    | Pemeriksaan                                                                                 |
| ------------------------- | ------------------------------------------------------------------------------------------- |
| Login k6 gagal            | Password, `EMAIL_PREFIX`, `EMAIL_DOMAIN`, dan `run-id` prepare tidak cocok                  |
| k6 menolak mulai          | Ada ID yang masih berupa placeholder atau `K6_PASSWORD` kosong                              |
| Jawaban AI terus `queued` | Cron/worker `ai-chat,ai-evaluation,ai` tidak berjalan                                       |
| Banyak 419                | Cookie/CSRF gagal; periksa domain dan apakah login memberi `XSRF-TOKEN`                     |
| Banyak 503/504            | Batas resource shared hosting; hentikan tes dan periksa hPanel                              |
| Cleanup menolak berjalan  | Prefix/domain/run-id berbeda atau marker class tidak cocok; jangan paksa penghapusan manual |

Raw summary disimpan di `%TEMP%` dengan nama yang mengandung `run-id`. Simpan file tersebut jika ingin membandingkan hasil antar deployment.
