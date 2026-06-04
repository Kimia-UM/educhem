<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WorksheetController extends Controller
{
    /**
     * Menampilkan Halaman Belajar (Lembar Kerja) Fase POE kepada Siswa
     */
    public function show(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase)
    {
        // 1. Keamanan Lapis 1: Pastikan siswa benar-benar tergabung di kelas ini
        if (!$request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        // 2. Keamanan Lapis 2: Pastikan topik sudah di-publish (dirilis) oleh Guru
        if (!$topic->is_published) {
            abort(403, 'Materi ini masih berstatus DRAFT dan belum dipublikasikan oleh Guru.');
        }

        // 3. Load konten fase secara berurutan
        $phase->load(['contents' => function($query) {
            $query->orderBy('order', 'asc');
        }]);

        // 4. Ambil jawaban siswa yang sudah ada agar tidak hilang saat halaman di-refresh
        // Sementara kita beri array kosong sampai tabel student_answers dibuat
        $studentAnswers = []; 

        // INI KUNCINYA: Harus merender folder Worksheet, bukan Classes!
        return Inertia::render('Siswa/Worksheet/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'studentAnswers' => (object) $studentAnswers
        ]);
    }

    /**
     * Menyimpan Jawaban Siswa (Untuk komponen 'input_text')
     */
    public function storeAnswer(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'content_id' => 'required|integer',
            'answer_text' => 'required|string',
        ]);

        $userId = $request->user()->id;

        // LOGIKA PENYIMPANAN JAWABAN NANTI AKAN MASUK KE SINI
        // ...

        return back()->with('success', 'Jawaban berhasil disimpan!');
    }
}