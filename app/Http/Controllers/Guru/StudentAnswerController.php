<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentAnswerController extends Controller
{
    /**
     * Menampilkan rekap jawaban seluruh siswa untuk sebuah fase LC5E.
     */
    public function index(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase)
    {
        // Keamanan: Pastikan guru yang login adalah pemilik kelas ini
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Ambil semua blok konten evaluasi (soal) di fase ini, urut berdasarkan order
        $phase->load(['contents' => function ($query) {
            $query->whereIn('type', [
                'eval_mcq', 'eval_cmcq', 'eval_short', 'eval_essay', 'eval_file', 'input_text',
            ])->orderBy('order', 'asc');
        }]);

        // Ambil semua jawaban siswa untuk fase ini, beserta data user
        $answers = StudentAnswer::where('phase_id', $phase->id)
            ->with('user:id,name,email')
            ->get()
            ->groupBy('content_id');

        // Hitung total siswa di kelas untuk persentase partisipasi
        $totalStudents = $classroom->students()->count();

        return Inertia::render('Guru/StudentAnswers/Index', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'answersGrouped' => $answers,
            'totalStudents' => $totalStudents,
        ]);
    }
    public function showStudentAnswers(Classroom $classroom, \App\Models\User $student)
    {
        // Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== request()->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Pastikan siswa terdaftar di kelas ini
        if (!$classroom->students()->where('user_id', $student->id)->exists()) {
            abort(404, 'Siswa tidak terdaftar di kelas ini.');
        }

        // Ambil semua topik dan fase di kelas ini
        $topics = $classroom->topics()->with(['phases' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->orderBy('topics.id', 'asc')->get();

        // Ambil ID semua fase untuk query jawaban
        $phaseIds = $topics->flatMap->phases->pluck('id');

        // Ambil semua jawaban siswa untuk fase-fase tersebut
        $answers = StudentAnswer::where('user_id', $student->id)
            ->whereIn('phase_id', $phaseIds)
            ->with(['content' => function ($query) {
                // Memastikan data konten yang dibutuhkan untuk evaluasi tersedia
                $query->select('id', 'topic_phase_id', 'type', 'content_data', 'correct_answers');
            }])
            ->get();

        // Ambil status pengiriman evaluasi dari pivot
        $pivot = $classroom->students()->where('user_id', $student->id)->first()?->pivot;
        $isEvaluationSent = $pivot ? $pivot->is_evaluation_sent : false;

        return Inertia::render('Guru/StudentAnswers/StudentShow', [
            'classroom' => $classroom,
            'student' => $student->only(['id', 'name', 'email']),
            'topics' => $topics,
            'answers' => $answers,
            'isEvaluationSent' => $isEvaluationSent,
        ]);
    }

    /**
     * Menyimpan evaluasi guru untuk jawaban uraian/singkat.
     */
    public function evaluateAnswer(Request $request, StudentAnswer $answer)
    {
        $request->validate([
            'evaluation' => 'required|string|in:benar,setengah_benar,salah',
        ]);

        // Validasi keamanan: Pastikan guru yang menilai adalah pengajar di kelas jawaban ini
        $phase = \App\Models\TopicPhase::find($answer->phase_id);
        if ($phase && $phase->topic && $phase->topic->classroom) {
            if ($phase->topic->classroom->teacher_id !== $request->user()->id) {
                abort(403, 'Akses ditolak. Anda bukan pengajar untuk kelas ini.');
            }
        }

        $answer->update([
            'evaluation' => $request->evaluation,
        ]);

        return back()->with('success', 'Penilaian berhasil disimpan.');
    }
    /**
     * Mengirimkan hasil evaluasi ke siswa (update pivot class_members).
     */
    public function sendEvaluation(Request $request, Classroom $classroom, \App\Models\User $student)
    {
        // Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Perbarui is_evaluation_sent di tabel pivot class_members
        $classroom->students()->updateExistingPivot($student->id, [
            'is_evaluation_sent' => true,
        ]);

        return back()->with('success', 'Hasil evaluasi berhasil dikirimkan ke siswa.');
    }

    /**
     * Memperbarui nilai Pre-test dan Post-test siswa.
     */
    public function updateScores(Request $request, Classroom $classroom, \App\Models\User $student)
    {
        // Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'pre_test_score' => 'nullable|integer|min:0|max:100',
            'post_test_score' => 'nullable|integer|min:0|max:100',
        ]);

        $classroom->students()->updateExistingPivot($student->id, $validated);

        return back()->with('success', 'Nilai berhasil disimpan.');
    }
}
