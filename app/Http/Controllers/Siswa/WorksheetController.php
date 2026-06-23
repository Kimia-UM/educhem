<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\StudentAnswer;
use App\Models\PhaseDiscussion;
use App\Jobs\EvaluateStudentAnswerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WorksheetController extends Controller
{
    /**
     * Menampilkan Halaman Belajar (Lembar Kerja) Fase LC5E kepada Siswa
     */
    public function show(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase)
    {
        // 1. Siswa harus terdaftar di kelas ini
        if (!$request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        // 2. Topic harus terdaftar di kelas ini dan sudah dipublish
        $topicAccess = $classroom->topics()->where('topic_id', $topic->id)->first();
        if (!$topicAccess || !$topicAccess->pivot->is_published) {
            abort(403, 'Materi ini masih berstatus DRAFT dan belum dipublikasikan oleh Guru.');
        }

        // 3. Phase harus milik topic yang diminta (cegah akses phase dari topic lain)
        if ($phase->topic_id !== $topic->id) {
            abort(403, 'Akses ditolak. Fase ini bukan bagian dari materi yang diminta.');
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

        // Ambil data diskusi untuk fase ini (komentar level atas + replies + user)
        $discussions = PhaseDiscussion::where('phase_id', $phase->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'replies' => function ($query) {
                    $query->with('user:id,name')->orderBy('created_at', 'asc');
                },
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // Tentukan apakah fase terkunci untuk siswa ini
        $classroomMember = $request->user()->joinedClasses()->where('class_id', $classroom->id)->first();
        $isEvaluationFinished = $classroomMember?->pivot?->is_evaluation_finished ?? false;

        $isLocked = $isEvaluationFinished || StudentAnswer::where('user_id', $request->user()->id)
            ->where('phase_id', $phase->id)
            ->where('is_locked', true)
            ->exists();

        return Inertia::render('Siswa/Worksheet/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'studentAnswers' => (object) $studentAnswers,
            'aiFeedbacks' => (object) $aiFeedbacks,
            'discussions' => $discussions,
            'isLocked' => $isLocked,
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

        // 1. Pastikan phase punya topic
        $topic = $phase->topic;
        if (!$topic) {
            abort(404, 'Topik tidak ditemukan.');
        }

<<<<<<< HEAD
        // 2. Pastikan siswa terdaftar di minimal satu kelas yang punya akses ke topic ini
        //    dan topic tersebut sudah dipublish di kelas itu
        $classroomMember = DB::table('class_members')
=======
        $classroom = $topic->classroom; // Asumsi relasi classroom ada (atau kita bisa ambil lewat classAccesses)
        
        // Cek penguncian fase untuk mencegah manipulasi
        $classroomMember = $request->user()->joinedClasses()->where('class_id', $classroom ? $classroom->id : null)->first();
        $isEvaluationFinished = $classroomMember?->pivot?->is_evaluation_finished ?? false;
        $isLocked = $isEvaluationFinished || StudentAnswer::where('user_id', $userId)
            ->where('phase_id', $phase->id)
            ->where('is_locked', true)
            ->exists();

        if ($isLocked) {
            abort(403, 'Fase ini sudah diselesaikan. Jawaban tidak dapat diubah.');
        }

        $hasAccess = DB::table('class_members')
>>>>>>> 724056a850b4163e5f99fdb3c6e9268ac3fd917c
            ->join('class_topic_accesses', 'class_topic_accesses.class_id', '=', 'class_members.class_id')
            ->where('class_members.user_id', $userId)
            ->where('class_topic_accesses.topic_id', $topic->id)
            ->where('class_topic_accesses.is_published', true)
            ->select('class_members.class_id', 'class_members.is_evaluation_finished')
            ->first();

        if (!$classroomMember) {
            abort(403, 'Akses ditolak. Anda tidak memiliki akses ke materi ini.');
        }

        // 3. Cek penguncian fase
        $isEvaluationFinished = $classroomMember->is_evaluation_finished ?? false;
        $isLocked = $isEvaluationFinished || StudentAnswer::where('user_id', $userId)
            ->where('phase_id', $phase->id)
            ->where('is_locked', true)
            ->exists();

        if ($isLocked) {
            abort(403, 'Fase ini sudah diselesaikan. Jawaban tidak dapat diubah.');
        }

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

    /**
     * Mengunci seluruh jawaban siswa pada fase ini
     */
    public function completePhase(Request $request, Classroom $classroom, TopicPhase $phase)
    {
        $userId = $request->user()->id;

<<<<<<< HEAD
        // 1. Siswa harus terdaftar di kelas ini
        if (!$request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        // 2. Phase harus milik topic yang terdaftar dan sudah dipublish di kelas ini
        $topic = $phase->topic;
        $topicBelongsToClass = $classroom->topics()
            ->where('topic_id', $topic?->id)
            ->wherePivot('is_published', true)
            ->exists();

        if (!$topic || !$topicBelongsToClass) {
            abort(403, 'Akses ditolak. Fase ini bukan bagian dari kelas ini.');
        }

=======
>>>>>>> 724056a850b4163e5f99fdb3c6e9268ac3fd917c
        // Ambil semua konten bertipe evaluasi/input soal di fase ini
        $contents = $phase->contents()
            ->whereIn('type', ['eval_mcq', 'eval_cmcq', 'eval_short', 'eval_essay', 'eval_file', 'input_text'])
            ->get();

        foreach ($contents as $content) {
            StudentAnswer::updateOrCreate(
                ['user_id' => $userId, 'content_id' => $content->id],
                [
                    'phase_id' => $phase->id,
                    'is_locked' => true,
                ]
            );
        }

        // Jika tidak ada soal sama sekali, lock dengan konten apa saja yang ada agar status isLocked terbaca true
        if ($contents->isEmpty()) {
            $anyContent = $phase->contents()->first();
            if ($anyContent) {
                StudentAnswer::updateOrCreate(
                    ['user_id' => $userId, 'content_id' => $anyContent->id],
                    [
                        'phase_id' => $phase->id,
                        'is_locked' => true,
                    ]
                );
            }
        }

        return redirect()->route('siswa.classes.show', $classroom->id)
            ->with('success', 'Fase berhasil diselesaikan!');
    }
}