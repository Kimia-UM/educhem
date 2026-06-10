<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\StudentAnswer;
use App\Jobs\EvaluateStudentAnswerJob;
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
        if (!$request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        if (!$topic->is_published) {
            abort(403, 'Materi ini masih berstatus DRAFT dan belum dipublikasikan oleh Guru.');
        }

        $phase->load(['contents' => function($query) {
            $query->orderBy('order', 'asc');
        }]);

        // Ambil semua data jawaban beserta evaluasi AI-nya
        $studentData = StudentAnswer::where('user_id', $request->user()->id)
            ->where('phase_id', $phase->id)
            ->get();

        // Pisahkan menjadi array yang mudah dibaca Vue
        $studentAnswers = $studentData->pluck('answer_data', 'content_id')->toArray();
        $aiFeedbacks = $studentData->pluck('ai_feedback', 'content_id')->toArray();

        return Inertia::render('Siswa/Worksheet/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'studentAnswers' => (object) $studentAnswers,
            'aiFeedbacks' => (object) $aiFeedbacks, // KITA TAMBAHKAN INI
        ]);
    }

    /**
     * Menyimpan Jawaban Siswa (Untuk Teks, Checkbox, dan Upload File)
     */
    public function storeAnswer(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'content_id' => 'required|integer|exists:phase_contents,id',
            'answer_text' => 'nullable|string',
            'answer_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $userId = $request->user()->id;
        $answerData = null;

        if ($request->hasFile('answer_file')) {
            $path = $request->file('answer_file')->store('student_uploads', 'public');
            $answerData = '/storage/' . $path;
        } else {
            $answerData = $request->input('answer_text');
        }

        // 3. SIMPAN ATAU UPDATE KE DATABASE
        $answer = StudentAnswer::updateOrCreate(
            ['user_id' => $userId, 'content_id' => $validated['content_id']],
            [
                'phase_id' => $phase->id, 
                'answer_data' => $answerData,
                'ai_feedback' => null // <-- TAMBAHKAN INI UNTUK MERESET FEEDBACK LAMA
            ]
        );

        // --- PENTING: Load relasi sebelum Dispatch Job ---
        // Ini memastikan Job AI memiliki data 'content' untuk dibaca (pertanyaan)
        $answer->load('content');

        if ($phase->is_ai_enabled && in_array($answer->content->type, ['eval_essay', 'eval_short'])) {
             EvaluateStudentAnswerJob::dispatch($answer, $phase->ai_prompt_setting);
        }

        return back()->with('success', 'Jawaban berhasil disimpan!');
    }
}