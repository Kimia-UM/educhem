# Gemini model availability and benchmark

Command `gemini:benchmark-models` memakai `GEMINI_API_KEY` dari environment Laravel. API key dan teks respons model tidak pernah dicetak atau disimpan.

## Melihat model yang tersedia

Command ini hanya memanggil `models.list` dan tidak melakukan text generation:

```bash
php artisan gemini:benchmark-models --list-only
```

Tanda `*` menunjukkan model yang sedang dipakai oleh `AI_GEMINI_MODEL`. Kolom `generateContent` menunjukkan apakah model dapat menerima pengujian text generation.

## Canary benchmark production

Secara default command memilih model Gemini text-generation stable, mengecualikan model image, audio, TTS, live, embedding, preview, experimental, dan alias `latest`.

```bash
php artisan gemini:benchmark-models \
  --attempts=3 \
  --delay-ms=750 \
  --max-output-tokens=512 \
  --max-models=12 \
  --allow-production \
  --json=storage/app/private/ai-benchmarks/gemini-canary.json
```

Command menampilkan jumlah request berbayar sebelum benchmark dimulai. Prompt dan output dibatasi untuk jawaban kimia yang sangat pendek.

Tiga attempt per model hanya cocok untuk canary. Untuk bukti stabilitas yang lebih baik, ulangi pada waktu berbeda dengan 10–20 attempt:

```bash
php artisan gemini:benchmark-models \
  --attempts=10 \
  --delay-ms=1000 \
  --max-output-tokens=512 \
  --max-models=12 \
  --allow-production \
  --json=storage/app/private/ai-benchmarks/gemini-10-attempts.json
```

## Membandingkan model tertentu

Gunakan ID persis dari output `--list-only`:

```bash
php artisan gemini:benchmark-models \
  --models=gemini-3.8-flash,gemini-3.5-flash-lite \
  --attempts=10 \
  --delay-ms=1000 \
  --max-output-tokens=512 \
  --allow-production
```

Gunakan `--include-preview` hanya jika memang ingin membandingkan model preview/experimental. Model tersebut tidak disertakan secara default karena lifecycle dan rate limit-nya lebih mudah berubah.

## Arti hasil

- **Success:** HTTP berhasil, respons tidak kosong, dan jawaban mengandung rumus `H2O`/`H₂O`.
- **Success rate:** indikator stabilitas dasar selama test.
- **Median:** latency tipikal.
- **P95:** latency ekor/lonjakan; lebih relevan untuk pengalaman siswa saat traffic ramai.
- **429:** rate limit atau quota pressure.
- **5xx:** gangguan provider/server.
- **Avg tokens:** indikator kasar konsumsi token, bukan perhitungan biaya final.

`Most stable` diurutkan berdasarkan success rate, kemudian p95 dan median. `Fastest` dipilih hanya dari tier success rate tertinggi. Jangan mengganti `AI_GEMINI_MODEL` berdasarkan satu canary singkat; bandingkan beberapa run pada jam berbeda dan uji kualitas jawaban chatbot nyata sebelum perubahan production.

## Menjalankan melalui SSH

```bash
ssh -p 65002 u191387983@153.92.11.33
cd /home/u191387983/educhem-current
php artisan gemini:benchmark-models --list-only
```

Tambahkan `--allow-production` hanya untuk command yang benar-benar melakukan generation benchmark.
