<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\ClassroomService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected ClassroomService $classroomService) {}

    public function index(Request $request)
    {
        $teacherId = $request->user()->id;

        // Mengambil daftar kelas milik guru yang sedang login
        $classes = $this->classroomService->getTeacherClasses($teacherId);

        // LOGIKA PENENTU HALAMAN
        if ($request->routeIs('guru.classes.index')) {
            return Inertia::render('Guru/Classes/Index', [
                'classes' => $classes
            ]);
        }

        // Ambil ID semua kelas guru untuk menghitung statistik
        $classIds = $classes->pluck('id');

        // 1. Total unik siswa aktif di seluruh kelas milik guru
        $totalStudents = \DB::table('class_members')
            ->whereIn('class_id', $classIds)
            ->distinct('user_id')
            ->count('user_id');

        // 2. Jumlah siswa yang perlu dievaluasi (is_evaluation_finished = false dan memiliki minimal 1 jawaban di kelas tersebut)
        $pendingReviews = \DB::table('class_members')
            ->whereIn('class_members.class_id', $classIds)
            ->where('class_members.is_evaluation_finished', false)
            ->whereExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('student_answers')
                    ->join('topic_phases', 'student_answers.phase_id', '=', 'topic_phases.id')
                    ->join('class_topic_accesses', 'topic_phases.topic_id', '=', 'class_topic_accesses.topic_id')
                    ->whereColumn('student_answers.user_id', 'class_members.user_id')
                    ->whereColumn('class_topic_accesses.class_id', 'class_members.class_id');
            })
            ->count();

        return Inertia::render('Guru/Dashboard', [
            'classes' => $classes,
            'stats' => [
                'total_students' => $totalStudents,
                'pending_reviews' => $pendingReviews,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        $this->classroomService->createClass($validated, $request->user()->id);

        return back()->with('success');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        // 1. Cari kelas berdasarkan ID
        $classroom = Classroom::findOrFail($id);

        // 2. Lapisan Keamanan: Pastikan kelas ini benar-benar milik Guru yang sedang login
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengubah kelas ini.');
        }

        // 3. Oper model kelas ke Service (Sesuai dengan parameter di ClassroomService Anda)
        $this->classroomService->updateClass($classroom, $validated);

        return back()->with('success', 'Informasi kelas berhasil diperbarui!');
    }

    public function destroy(Request $request, string $id)
    {
        // 1. Cari kelas berdasarkan ID
        $classroom = Classroom::findOrFail($id);

        // 2. Lapisan Keamanan: Mencegah penghapusan kelas oleh guru lain
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus kelas ini.');
        }

        // 3. Eksekusi penghapusan via Service
        $this->classroomService->deleteClass($classroom);

        return back()->with('success');
    }


    public function show(Request $request, string $id)
    {
        // 1. Ambil detail kelas dengan pengecekan kepemilikan
        $classroom = $this->classroomService->getClassDetail($id, $request->user()->id);

        // 2. Render halaman detail kelas (Anda bisa membuat halaman ini sesuai kebutuhan)
        return Inertia::render('Guru/Classes/Show', [
            'classroom' => $classroom,
            'defaultTab' => $request->query('tab', 'topik'),
        ]);
    }

    public function kickStudent(Request $request, Classroom $classroom, \App\Models\User $student)
    {
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        $classroom->students()->detach($student->id);

        $phaseIds = $classroom->topics()->with('phases')->get()->flatMap->phases->pluck('id');
        \App\Models\StudentAnswer::where('user_id', $student->id)
            ->whereIn('phase_id', $phaseIds)
            ->delete();

        return back()->with('success');
    }
}