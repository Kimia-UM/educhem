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
        // Mengambil daftar kelas milik guru yang sedang login
        $classes = $this->classroomService->getTeacherClasses($request->user()->id);

        // LOGIKA PENENTU HALAMAN
        if ($request->routeIs('guru.classes.index')) {
            return Inertia::render('Guru/Classes/Index', [
                'classes' => $classes
            ]);
        }

        return Inertia::render('Guru/Dashboard', [
            'classes' => $classes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000'
        ]);

        $this->classroomService->createClass($validated, $request->user()->id);

        return back()->with('success', 'Kelas baru berhasil dibuat!');
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

        return back()->with('success', 'Kelas berhasil dihapus permanen!');
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
}