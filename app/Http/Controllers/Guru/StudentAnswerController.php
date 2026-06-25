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
        $isEvaluationFinished = $pivot ? $pivot->is_evaluation_finished : false;

        return Inertia::render('Guru/StudentAnswers/StudentShow', [
            'classroom' => $classroom,
            'student' => $student->only(['id', 'name', 'email']),
            'topics' => $topics,
            'answers' => $answers,
            'isEvaluationSent' => $isEvaluationSent,
            'isEvaluationFinished' => $isEvaluationFinished,
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
            $classroom = $phase->topic->classroom;
            if ($classroom->teacher_id !== $request->user()->id) {
                abort(403, 'Akses ditolak. Anda bukan pengajar untuk kelas ini.');
            }

            // Pastikan status evaluation belum selesai
            $pivot = $classroom->students()->where('user_id', $answer->user_id)->first()?->pivot;
            if ($pivot && $pivot->is_evaluation_finished) {
                abort(400, 'Evaluasi telah ditandai selesai. Buka kunci edit terlebih dahulu.');
            }
        }

        $answer->update([
            'evaluation' => $request->evaluation,
        ]);

        return back()->with('success', 'Penilaian berhasil disimpan.');
    }

    /**
     * Menandai evaluasi siswa selesai (mengunci penilaian).
     */
    public function finishEvaluation(Request $request, Classroom $classroom, \App\Models\User $student)
    {
        // Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        $classroom->students()->updateExistingPivot($student->id, [
            'is_evaluation_finished' => true,
        ]);

        return back()->with('success', 'Evaluasi berhasil ditandai selesai dan dikunci.');
    }

    /**
     * Membuka kembali kunci evaluasi siswa untuk diedit.
     */
    public function editEvaluation(Request $request, Classroom $classroom, \App\Models\User $student)
    {
        // Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Buka kunci evaluasi dan sembunyikan sementara hasil dari siswa agar tidak membaca hasil setengah-jadi
        $classroom->students()->updateExistingPivot($student->id, [
            'is_evaluation_finished' => false,
            'is_evaluation_sent' => false,
        ]);

        return back()->with('success', 'Kunci evaluasi dibuka. Anda dapat mengedit kembali penilaian.');
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

    /**
     * Mengekspor nilai pre-test dan post-test siswa ke format Excel/CSV.
     */
    public function exportGrades(Classroom $classroom)
    {
        // Keamanan: Pastikan guru yang login adalah pemilik kelas ini
        if ($classroom->teacher_id !== auth()->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=Rekap_Nilai_" . str_replace(' ', '_', $classroom->class_name) . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($classroom) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan UTF-8 BOM untuk kompatibilitas Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header kolom
            fputcsv($file, ['No', 'Nama Siswa', 'Email', 'Nilai Awal (Pre-test)', 'Nilai Akhir (Post-test)']);

            // Ambil semua siswa terdaftar
            $students = $classroom->students()->orderBy('name', 'asc')->get();
            
            foreach ($students as $idx => $student) {
                fputcsv($file, [
                    $idx + 1,
                    $student->name,
                    $student->email,
                    $student->pivot->pre_test_score ?? '-',
                    $student->pivot->post_test_score ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Menampilkan halaman cetak hasil evaluasi dan jawaban siswa (PDF/Print).
     */
    public function printStudentAnswers(Classroom $classroom, \App\Models\User $student)
    {
        // Keamanan: Pastikan guru yang login adalah pemilik kelas
        if ($classroom->teacher_id !== auth()->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Pastikan siswa terdaftar
        if (!$classroom->students()->where('user_id', $student->id)->exists()) {
            abort(404, 'Siswa tidak ditemukan di kelas ini.');
        }

        // Ambil semua topik dan fase di kelas ini beserta relasi fase
        $topics = $classroom->topics()->with(['phases' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->orderBy('topics.id', 'asc')->get();

        $phaseIds = $topics->flatMap->phases->pluck('id');

        // Ambil jawaban siswa beserta relasi konten (soal)
        $answers = StudentAnswer::where('user_id', $student->id)
            ->whereIn('phase_id', $phaseIds)
            ->with(['content' => function ($query) {
                $query->select('id', 'topic_phase_id', 'type', 'content_data', 'correct_answers');
            }])
            ->get()
            ->keyBy('content_id');

        // Ambil status evaluasi dari pivot
        $pivot = $classroom->students()->where('user_id', $student->id)->first()?->pivot;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.student-answers', [
            'classroom' => $classroom,
            'student' => $student,
            'topics' => $topics,
            'answers' => $answers,
            'pivot' => $pivot,
        ]);

        return $pdf->stream('evaluasi-' . $student->name . '-' . $classroom->class_name . '.pdf');
    }
}
