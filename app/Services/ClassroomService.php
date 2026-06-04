<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Support\Str;

class ClassroomService
{
    /**
     * Mengambil daftar kelas milik seorang guru
     */
    public function getTeacherClasses(int $teacherId)
    {
        return Classroom::where('teacher_id', $teacherId)
            ->withCount('students') // MENGHITUNG JUMLAH SISWA UNTUK DASHBOARD GURU
            ->latest()
            ->get();
    }

    /**
     * Membuat kelas baru beserta kode unik 6 karakter
     */
    public function createClass(array $data, int $teacherId)
    {
        $data['teacher_id'] = $teacherId;
        $data['class_code'] = $this->generateUniqueCode();
        
        return Classroom::create($data);
    }

    /**
     * Menghasilkan string acak 6 karakter yang dipastikan unik di database
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Classroom::where('class_code', $code)->exists());

        return $code;
    }

    public function updateClass(Classroom $classroom, array $data)
    {
        return $classroom->update($data);
    }

    public function deleteClass(Classroom $classroom)
    {
        return $classroom->delete();
    }

    /**
     * Memunculkan detail kelas berdasarkan ID, dengan pengecekan kepemilikan
     */
    public function getClassDetail(string $id, int $teacherId)
    {
        return Classroom::with([
                // Mengambil relasi topik
                'topics' => function ($query) {
                    $query->latest(); // Mengurutkan topik dari yang terbaru
                },
                // Mengambil relasi siswa yang bergabung
                'students' => function ($query) {
                    $query->orderBy('name', 'asc'); // Mengurutkan siswa sesuai abjad (A-Z)
                }
            ])
            ->where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();
    }
}