<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ClassroomController extends Controller
{
    /**
     * Menampilkan halaman "Kelas Saya" (Daftar kelas yang diikuti siswa)
     */
    public function index(Request $request)
    {
        // Menarik data kelas yang HANYA diikuti oleh siswa yang sedang login
        $classrooms = $request->user()
            ->joinedClasses()
            ->withCount('topics')
            ->orderByPivot('created_at', 'desc')
            ->get();

        // Merender komponen Vue di resources/js/pages/Siswa/Classes/Index.vue
        return Inertia::render('Siswa/Classes/Index', [
            'classrooms' => $classrooms
        ]);
    }

    /**
     * Memproses permintaan siswa untuk bergabung ke kelas via kode.
     */
    public function join(Request $request): RedirectResponse
    {
        // 1. Validasi Input (Kode kelas wajib 6 karakter)
        $request->validate([
            'class_code' => ['required', 'string', 'size:6'],
        ], [
            'class_code.required' => 'Kode kelas wajib diisi.',
            'class_code.size' => 'Kode kelas harus berisi tepat 6 karakter.',
        ]);

        // 2. Cari kelas berdasarkan kode (Otomatis kapital agar case-insensitive)
        $classroom = Classroom::where('class_code', strtoupper($request->class_code))->first();

        // 3. Jika kelas tidak ditemukan
        if (!$classroom) {
            return back()->withErrors(['class_code' => 'Kelas tidak ditemukan. Periksa kembali kode Anda.']);
        }

        $user = $request->user();

        // 4. Mencegah Duplicate Join (Siswa tidak bisa gabung kelas yang sama 2x)
        if ($user->joinedClasses()->where('class_id', $classroom->id)->exists()) {
            return back()->withErrors(['class_code' => 'Anda sudah terdaftar di kelas ini.']);
        }

        // 5. Masukkan siswa ke dalam kelas (Attach ke pivot table class_members)
        $user->joinedClasses()->attach($classroom->id);

        // 6. Kembalikan ke dashboard dengan pesan sukses
        return back()->with('success');
    }

    public function show(Request $request, Classroom $classroom)
    {
        $user = $request->user();
        
        // Pastikan siswa member kelas dan ambil pivot
        $classroomMember = $user->joinedClasses()->where('class_id', $classroom->id)->first();
        if (!$classroomMember) {
            abort(403, 'Akses ditolak.');
        }
        
        $isEvaluationSent = $classroomMember->pivot->is_evaluation_sent ?? false;

        // Memuat topik yang sudah dipublish di kelas ini
        $classroom->load(['teacher', 'topics' => function ($query) {
            $query->where('topics.is_published', true)
                  ->with(['phases' => function($q) {
                      $q->orderBy('order', 'asc');
                  }]);
        }]);

        return inertia('Siswa/Classes/Show', [
            'classroom' => $classroom,
            'isEvaluationSent' => $isEvaluationSent
        ]);
    }

    public function evaluationResult(Request $request, Classroom $classroom)
    {
        $user = $request->user();
        
        // Pastikan siswa member kelas
        $classroomMember = $user->joinedClasses()->where('class_id', $classroom->id)->first();
        if (!$classroomMember) {
            abort(403, 'Akses ditolak.');
        }

        $isEvaluationSent = $classroomMember->pivot->is_evaluation_sent ?? false;

        // Ambil semua topik yang sudah dipublish di kelas ini
        $topics = $classroom->topics()->where('topics.is_published', true)->with(['phases' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->orderBy('topics.id', 'asc')->get();

        $phaseIds = $topics->flatMap->phases->pluck('id');

        // Ambil semua jawaban siswa
        $answers = \App\Models\StudentAnswer::where('user_id', $user->id)
            ->whereIn('phase_id', $phaseIds)
            ->with(['content' => function ($query) {
                $query->select('id', 'topic_phase_id', 'type', 'content_data', 'correct_answers');
            }])
            ->get();

        return inertia('Siswa/Classes/EvaluationResult', [
            'classroom' => $classroom->load('teacher'),
            'topics' => $topics,
            'answers' => $answers,
            'isEvaluationSent' => $isEvaluationSent
        ]);
    }
}