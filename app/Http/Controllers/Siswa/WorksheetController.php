<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluateStudentAnswerJob;
use App\Models\Classroom;
use App\Models\PhaseContent;
use App\Models\PhaseDiscussion;
use App\Models\StudentAnswer;
use App\Models\Topic;
use App\Models\TopicPhase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WorksheetController extends Controller
{
    /**
     * Menampilkan Halaman Belajar (Lembar Kerja) Fase LC5E kepada Siswa
     */
    public function show(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase)
    {
        // 1. Siswa harus terdaftar di kelas ini
        if (! $request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        // 2. Topic harus terdaftar di kelas ini dan sudah dipublish
        $topicAccess = $classroom->topics()->where('topic_id', $topic->id)->first();
        if (! $topicAccess || ! $topic->is_published) {
            abort(403, 'Materi ini masih berstatus DRAFT dan belum dipublikasikan oleh Guru.');
        }

        // 3. Phase harus milik topic yang diminta (cegah akses phase dari topic lain)
        if ($phase->topic_id !== $topic->id) {
            abort(403, 'Akses ditolak. Fase ini bukan bagian dari materi yang diminta.');
        }

        $phase->load(['contents' => function ($query) {
            $query->orderBy('order', 'asc');
        }]);

        // Ambil semua data jawaban beserta evaluasi AI-nya
        $studentData = StudentAnswer::where('user_id', $request->user()->id)
            ->where('phase_id', $phase->id)
            ->get();

        // Pisahkan menjadi array yang mudah dibaca Vue
        $studentAnswers = $studentData->pluck('answer_data', 'content_id')->toArray();
        $aiFeedbacks = $studentData->pluck('ai_feedback', 'content_id')->toArray();
        $aiStatuses = $studentData->mapWithKeys(fn (StudentAnswer $answer) => [
            $answer->content_id => [
                'status' => $answer->effectiveAiStatus(),
                'answer_version' => max(1, (int) ($answer->answer_version ?? 1)),
            ],
        ])->toArray();

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
        $isEvaluationSent = $classroomMember?->pivot?->is_evaluation_sent ?? false;

        $isLocked = $isEvaluationFinished || StudentAnswer::where('user_id', $request->user()->id)
            ->where('phase_id', $phase->id)
            ->where('is_locked', true)
            ->exists();

        $finalScore = null;
        $evaluations = [];
        $correctAnswersList = [];

        if ($isEvaluationSent) {
            $evaluations = $studentData->pluck('evaluation', 'content_id')->toArray();

            $totalScore = 0;
            $maxScore = 0;

            foreach ($phase->contents as $content) {
                if (! in_array($content->type, ['eval_mcq', 'eval_cmcq', 'eval_short', 'eval_essay', 'input_text', 'eval_file'])) {
                    continue;
                }

                $answer = $studentData->where('content_id', $content->id)->first();
                if (! $answer) {
                    continue;
                }

                // Jika guru mengecualikan dari penilaian
                if ($answer->evaluation === 'tidak_dinilai') {
                    continue;
                }

                if (in_array($content->type, ['eval_mcq', 'eval_cmcq'])) {
                    $correctIndices = $content->correct_answers ?? [];
                    $options = $content->content_data['options'] ?? [];

                    // Resolve indeks kunci jawaban menjadi teks opsi
                    $correctTexts = [];
                    foreach ($correctIndices as $idx) {
                        if (isset($options[(int) $idx])) {
                            $correctTexts[] = $options[(int) $idx];
                        }
                    }

                    // Kirim teks opsi yang benar ke frontend (bukan indeks)
                    $correctAnswersList[$content->id] = $correctTexts;
                    $studentAns = $answer->answer_data;

                    $isCorrect = false;
                    if ($content->type === 'eval_mcq') {
                        // PG biasa: jawaban siswa berupa teks opsi, bandingkan langsung
                        $isCorrect = in_array((string) $studentAns, $correctTexts);
                    } elseif ($content->type === 'eval_cmcq') {
                        // PG kompleks: jawaban siswa berupa JSON array teks opsi
                        $studentAnsArray = is_array($studentAns) ? $studentAns : json_decode($studentAns, true) ?? [];
                        if (is_array($studentAnsArray)) {
                            $isSameLength = count($studentAnsArray) === count($correctTexts);
                            $hasAllCorrect = collect($correctTexts)->every(fn ($c) => in_array((string) $c, array_map('strval', $studentAnsArray)));
                            $isCorrect = $isSameLength && $hasAllCorrect;
                        }
                    }

                    $maxScore += 1;
                    if ($isCorrect) {
                        $totalScore += 1;
                    }

                } else {
                    $maxScore += 2;
                    if ($answer->evaluation === 'benar') {
                        $totalScore += 2;
                    } elseif ($answer->evaluation === 'setengah_benar') {
                        $totalScore += 1;
                    }
                }
            }

            if ($maxScore > 0) {
                $finalScore = round(($totalScore / $maxScore) * 100);
            } else {
                $finalScore = 0;
            }
        }

        return Inertia::render('Siswa/Worksheet/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'studentAnswers' => (object) $studentAnswers,
            'aiFeedbacks' => (object) $aiFeedbacks,
            'aiStatuses' => (object) $aiStatuses,
            'discussions' => $discussions,
            'isLocked' => $isLocked,
            'isEvaluationSent' => $isEvaluationSent,
            'finalScore' => $finalScore,
            'evaluations' => (object) $evaluations,
            'correctAnswersList' => (object) $correctAnswersList,
        ]);
    }

    /**
     * Menyimpan Jawaban Siswa (Untuk Teks, Checkbox, dan Upload File)
     */
    public function storeAnswer(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'content_id' => 'required|integer',
            'answer_text' => 'nullable|string',
            'answer_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $userId = $request->user()->id;

        $classroomMember = $this->accessibleClassroomMember($request, $phase);
        $content = $phase->contents()->whereKey($validated['content_id'])->firstOrFail();

        if (! in_array($content->type, PhaseContent::ANSWERABLE_TYPES, true)) {
            throw ValidationException::withMessages([
                'content_id' => 'Konten ini tidak menerima jawaban siswa.',
            ]);
        }

        if ($content->type === 'eval_file' && ! $request->hasFile('answer_file')) {
            throw ValidationException::withMessages([
                'answer_file' => 'File jawaban wajib dipilih.',
            ]);
        }

        if ($content->type !== 'eval_file' && $request->hasFile('answer_file')) {
            throw ValidationException::withMessages([
                'answer_file' => 'Konten ini tidak menerima jawaban file.',
            ]);
        }

        if ($content->type !== 'eval_file' && ! $request->filled('answer_text')) {
            throw ValidationException::withMessages([
                'answer_text' => 'Jawaban wajib diisi.',
            ]);
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
            $answerData = '/storage/'.$path;
        } else {
            $answerData = $request->input('answer_text');
        }

        $shouldEvaluateWithAi = $phase->is_ai_enabled && $content->supportsAiEvaluation();

        $answer = DB::transaction(function () use (
            $answerData,
            $content,
            $phase,
            $shouldEvaluateWithAi,
            $userId,
        ) {
            $answer = StudentAnswer::query()
                ->where('user_id', $userId)
                ->where('content_id', $content->id)
                ->lockForUpdate()
                ->first();

            $answerVersion = $answer
                ? max(1, (int) ($answer->answer_version ?? 1)) + 1
                : 1;
            $answerChanged = ! $answer || $answer->answer_data !== $answerData;

            $answer ??= new StudentAnswer([
                'user_id' => $userId,
                'content_id' => $content->id,
            ]);

            $answer->fill([
                'phase_id' => $phase->id,
                'answer_data' => $answerData,
                'answer_version' => $answerVersion,
                'ai_feedback' => null,
                'ai_status' => $shouldEvaluateWithAi
                    ? StudentAnswer::AI_STATUS_QUEUED
                    : StudentAnswer::AI_STATUS_NOT_REQUIRED,
                'ai_requested_at' => $shouldEvaluateWithAi ? now() : null,
                'ai_completed_at' => null,
                'ai_error_code' => null,
            ]);

            if ($answerChanged) {
                $answer->evaluation = null;
            }

            $answer->save();

            if ($shouldEvaluateWithAi) {
                EvaluateStudentAnswerJob::dispatch(
                    $answer,
                    $phase->ai_prompt_setting,
                    $answerVersion,
                )->afterCommit();
            }

            return $answer;
        });

        $payload = [
            'saved' => true,
            'content_id' => $answer->content_id,
            'answer_data' => $answer->answer_data,
            'ai_status' => $answer->effectiveAiStatus(),
            'answer_version' => max(1, (int) $answer->answer_version),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, $shouldEvaluateWithAi ? 202 : 200);
        }

        return back()->with('success', 'Jawaban berhasil disimpan!');
    }

    /**
     * Endpoint ringan untuk memeriksa status evaluasi AI tanpa reload Inertia.
     */
    public function aiFeedbackStatus(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'content_ids' => 'nullable|array|max:50',
            'content_ids.*' => 'integer|distinct',
        ]);

        $this->accessibleClassroomMember($request, $phase);

        $answers = StudentAnswer::query()
            ->where('user_id', $request->user()->id)
            ->where('phase_id', $phase->id)
            ->when(
                ! empty($validated['content_ids']),
                fn ($query) => $query->whereIn('content_id', $validated['content_ids']),
            )
            ->get([
                'content_id',
                'ai_status',
                'ai_feedback',
                'answer_version',
                'ai_error_code',
                'updated_at',
            ]);

        $items = $answers->mapWithKeys(fn (StudentAnswer $answer) => [
            $answer->content_id => [
                'status' => $answer->effectiveAiStatus(),
                'feedback' => $answer->ai_feedback,
                'answer_version' => max(1, (int) ($answer->answer_version ?? 1)),
                'error_code' => $answer->ai_error_code,
                'updated_at' => $answer->updated_at?->toISOString(),
            ],
        ]);

        return response()
            ->json([
                'items' => (object) $items->all(),
                'retry_after_seconds' => 15,
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    /**
     * Mengunci seluruh jawaban siswa pada fase ini
     */
    public function completePhase(Request $request, Classroom $classroom, TopicPhase $phase)
    {
        $userId = $request->user()->id;

        // 1. Siswa harus terdaftar di kelas ini
        if (! $request->user()->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar di kelas ini.');
        }

        // 2. Phase harus milik topic yang terdaftar dan sudah dipublish di kelas ini
        $topic = $phase->topic;
        $topicBelongsToClass = $classroom->topics()
            ->where('topic_id', $topic?->id)
            ->where('topics.is_published', true)
            ->exists();

        if (! $topic || ! $topicBelongsToClass) {
            abort(403, 'Akses ditolak. Fase ini bukan bagian dari kelas ini.');
        }

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

    private function accessibleClassroomMember(Request $request, TopicPhase $phase): object
    {
        $topic = $phase->topic;

        if (! $topic) {
            abort(404, 'Topik tidak ditemukan.');
        }

        if (! $topic->is_published) {
            abort(403, 'Akses ditolak. Materi ini belum dipublikasikan.');
        }

        $classroomMember = DB::table('class_members')
            ->join('class_topic_accesses', 'class_topic_accesses.class_id', '=', 'class_members.class_id')
            ->where('class_members.user_id', $request->user()->id)
            ->where('class_topic_accesses.topic_id', $topic->id)
            ->select('class_members.class_id', 'class_members.is_evaluation_finished')
            ->first();

        if (! $classroomMember) {
            abort(403, 'Akses ditolak. Anda tidak memiliki akses ke materi ini.');
        }

        return $classroomMember;
    }
}
