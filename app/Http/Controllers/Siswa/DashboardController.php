<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Mengambil HANYA data kelas yang sudah DIIKUTI oleh siswa yang sedang login
        // melalui tabel pivot 'class_members' (relasi joinedClasses).
        $classrooms = $request->user()
    ->joinedClasses()
    ->withCount(['topics' => function($query) {
        // Hanya hitung topik yang is_open = true di tabel pivot
        $query->where('class_topic_accesses.is_open', true);
    }])
    ->orderByPivot('created_at', 'desc')
    ->get();

        // 3. Mengirimkan data ke komponen Vue (Dashboard Siswa)
        return Inertia::render('Siswa/Dashboard', [
            'classrooms' => $classrooms
        ]);
    }
}